<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StudioSetting;
use App\Models\StudioTheme;
use App\Models\StudioThemeVersion;
use App\Services\AIEngine;
use App\Services\ThemeStepEngine;
use App\Support\ThemeSettingsRecommender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use ZipArchive;

class ThemeController extends Controller
{
    // ──────────────────────────────────────────────
    //  CRUD
    // ──────────────────────────────────────────────

    public function index(): Response
    {
        return Inertia::render('Themes/Index', [
            'themes' => StudioTheme::latest()->get([
                'id', 'uuid', 'name', 'label', 'version',
                'status', 'is_published', 'preview_url', 'created_at',
            ]),
        ]);
    }

    public function create(): RedirectResponse
    {
        // Cria automaticamente um rascunho e redireciona para o editor completo (10 abas)
        $counter = StudioTheme::withTrashed()->count() + 1;
        $theme = StudioTheme::create([
            'name'    => 'novo-tema-' . $counter,
            'label'   => 'Novo Tema ' . $counter,
            'version' => '1.0.0',
            'status'  => 'draft',
        ]);

        return redirect()->route('themes.edit', $theme->uuid)
            ->with('success', 'Tema criado — edita os detalhes abaixo.');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'label'       => 'required|string|max:200',
            'description' => 'nullable|string|max:1000',
            'version'     => 'nullable|string|max:20',
        ]);

        $theme = StudioTheme::create([
            'name'        => self::uniqueSlug($data['label']),
            'label'       => $data['label'],
            'description' => $data['description'] ?? null,
            'version'     => ($data['version'] ?? '') ?: '1.0.0',
            'status'      => 'draft',
        ]);

        return redirect()->route('themes.edit', $theme->uuid)
            ->with('success', 'Tema criado — edita os detalhes abaixo.');
    }

    /** Generate a unique, schema-valid slug (name) from a human label. */
    private static function uniqueSlug(string $label): string
    {
        $base = substr(\Illuminate\Support\Str::slug($label), 0, 40) ?: 'tema';
        $slug = $base;
        $i = 2;
        while (StudioTheme::withTrashed()->where('name', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    public function edit(string $uuid): Response
    {
        $theme = StudioTheme::where('uuid', $uuid)->firstOrFail();

        // O espelho leve (sem snapshots pesados) vai num prop separado; o
        // 'before' completo nunca é enviado ao browser.
        $journal = ThemeStepEngine::publicJournal($theme->step_journal);

        return Inertia::render('Themes/Edit', [
            'theme'       => $theme->makeHidden('step_journal'),
            'themeAgents' => AIEngine::themeAgents(),
            'stepJournal' => $journal,
        ]);
    }

    public function update(Request $request, string $uuid): RedirectResponse
    {
        $theme = StudioTheme::where('uuid', $uuid)->firstOrFail();

        $data = $request->validate([
            // Details
            'name'        => 'sometimes|string|regex:/^[a-z0-9][a-z0-9\-_]{0,49}$/|unique:studio_themes,name,' . $theme->id,
            'label'       => 'sometimes|string|max:200',
            'description' => 'nullable|string|max:1000',
            'version'     => 'nullable|string|max:20',
            'status'      => 'nullable|in:draft,ready,published',
            'preview_url' => 'nullable|url|max:500',
            // Design
            'colors'   => 'nullable|array',
            'fonts'    => 'nullable|array',
            'sections' => 'nullable|array',
            // Layout
            'layout_config' => 'nullable|array',
            // Capabilities
            'capabilities' => 'nullable|array',
            // Components (Blade overrides)
            'components' => 'nullable|array',
            // Custom code
            'custom_css' => 'nullable|string',
            'custom_js'  => 'nullable|string',
            // Variants
            'variants' => 'nullable|array',
            // Definições do site (schema configurável pelo criador no AnimusFlow)
            'theme_settings' => 'nullable|array',
        ]);

        // Snapshot dos campos do espelho ANTES de gravar (para detectar mudanças reais)
        $journalFields = array_keys(array_intersect_key($data, ThemeStepEngine::FIELD_STEP));
        $before = [];
        foreach ($journalFields as $f) {
            $before[$f] = $theme->$f;
        }

        $theme->update($data);

        // Regista no espelho apenas os campos que mudaram de facto (origem: manual)
        $changed = [];
        foreach ($before as $f => $prev) {
            if (json_encode($prev) !== json_encode($theme->$f)) {
                $changed[] = $f;
            }
        }
        if (!empty($changed)) {
            ThemeStepEngine::record($theme, $changed, 'manual', 'Edição manual nos campos: ' . implode(', ', $changed), $before);
        }

        return back()->with('success', 'Theme saved.');
    }

    /**
     * Repõe o schema de "Definições do site" para os valores recomendados,
     * derivados do design atual do tema (layout_config / colors / fonts /
     * capabilities). Guarda e devolve o novo schema para o editor aplicar.
     */
    public function recommendSettings(string $uuid): JsonResponse
    {
        $theme = StudioTheme::where('uuid', $uuid)->firstOrFail();

        $settings = ThemeSettingsRecommender::recommend($theme);
        $theme->update(['theme_settings' => $settings]);

        return response()->json([
            'success'        => true,
            'theme_settings' => $settings,
            'count'          => count($settings),
        ]);
    }

    // ──────────────────────────────────────────────
    //  Asset Upload
    // ──────────────────────────────────────────────

    public function uploadAsset(Request $request, string $uuid): JsonResponse
    {
        $theme = StudioTheme::where('uuid', $uuid)->firstOrFail();

        $allowedSlots = [
            // Identidade
            'logo', 'logo_dark', 'favicon',
            // Fundo global
            'bg_image', 'bg_video', 'bg_pattern',
            // Hero
            'hero_image', 'hero_video', 'hero_poster',
            // Slideshow
            'slide_1', 'slide_2', 'slide_3',
            // Fundos de secções
            'about_bg', 'features_bg', 'cta_bg', 'testimonials_bg', 'pricing_bg', 'footer_bg',
            // Social / SEO
            'og_image', 'twitter_card', 'apple_touch',
            // Fundo HUD / Screensaver (temas tipo AeroSpace — layout_config.hud_*)
            'hud_bg_video', 'hud_bg_photo', 'hud_gallery_1', 'hud_gallery_2', 'hud_gallery_3',
        ];

        $request->validate([
            'file' => 'required|file|max:51200', // 50 MB (vídeos)
            'slot' => 'required|in:' . implode(',', $allowedSlots),
        ]);

        $disk = StudioSetting::get('media_storage_disk', 'public');
        $slot = $request->input('slot');
        $dir  = "themes/{$theme->uuid}";
        $path = $request->file('file')->store($dir, $disk);
        
        $url  = $disk === 'public' 
            ? '/storage/' . $path 
            : Storage::disk($disk)->url($path);

        $assets = $theme->assets ?? [];
        $assets[$slot] = $url;
        $theme->update(['assets' => $assets]);

        return response()->json(['success' => true, 'url' => $url, 'slot' => $slot]);
    }

    public function deleteAsset(Request $request, string $uuid): JsonResponse
    {
        $theme = StudioTheme::where('uuid', $uuid)->firstOrFail();
        $slot  = $request->validate(['slot' => 'required|string'])['slot'];

        $assets = $theme->assets ?? [];
        if (isset($assets[$slot])) {
            $url  = $assets[$slot];
            $disk = StudioSetting::get('media_storage_disk', 'public');

            if (preg_match('#themes/' . $theme->uuid . '/[^/]+#', $url, $m)) {
                Storage::disk($disk)->delete($m[0]);
            } else {
                $localPath = public_path(str_replace('/storage/', 'storage/', $url));
                if (file_exists($localPath)) {
                    @unlink($localPath);
                }
            }

            unset($assets[$slot]);
            $theme->update(['assets' => $assets]);
        }

        return response()->json(['success' => true]);
    }

    public function destroy(string $uuid): RedirectResponse
    {
        StudioTheme::where('uuid', $uuid)->firstOrFail()->delete();

        return redirect()->route('themes.index')->with('success', 'Theme deleted.');
    }

    // ──────────────────────────────────────────────
    //  AI Generation
    // ──────────────────────────────────────────────

    public function generateAi(Request $request, string $uuid): JsonResponse
    {
        $theme = StudioTheme::where('uuid', $uuid)->firstOrFail();

        $request->validate(['prompt' => 'required|string|min:5|max:500']);

        try {
            $result = AIEngine::generateTheme($request->input('prompt'));
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        // Merge — keep existing sections not returned by AI
        $newSections = array_merge($theme->sections ?? [], $result['sections'] ?? []);

        $theme->update([
            'colors'   => $result['colors']  ?? $theme->colors,
            'fonts'    => $result['fonts']   ?? $theme->fonts,
            'sections' => $newSections,
        ]);

        $fresh = $theme->fresh();

        return response()->json([
            'success'  => true,
            'colors'   => $fresh->colors,
            'fonts'    => $fresh->fonts,
            'sections' => $fresh->sections,
        ]);
    }

    // ──────────────────────────────────────────────
    //  Category-based Theme Inspiration
    // ──────────────────────────────────────────────

    public function inspire(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category' => 'required|string|max:100',
            'style'    => 'nullable|string|max:50',
        ]);

        $category = $data['category'];
        $style    = $data['style'] ?? 'moderno';

        try {
            $generated = AIEngine::generateThemeFromCategory($category, $style);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        // Build a slug from category + style
        $slug = \Illuminate\Support\Str::slug($category . '-' . $style . '-' . uniqid());

        // Create a draft theme with the generated data
        $theme = StudioTheme::create([
            'name'          => $slug,
            'label'         => $generated['label'] ?? ucfirst($category) . ' Theme',
            'description'   => ($generated['description'] ?? '') . "\n\n" . ($generated['inspiration'] ?? ''),
            'version'       => '1.0.0',
            'status'        => 'draft',
            'colors'        => $generated['colors'] ?? ['light' => [], 'dark' => []],
            'fonts'         => $generated['fonts'] ?? ['heading' => 'Inter', 'body' => 'Inter'],
            'layout_config' => $generated['layout_config'] ?? [],
            'capabilities'  => $generated['capabilities'] ?? [],
            'sections'      => $generated['sections'] ?? [],
            'custom_css'    => $generated['custom_css'] ?? '',
        ]);

        return response()->json([
            'success'     => true,
            'theme_uuid'  => $theme->uuid,
            'preview_url' => route('themes.preview', $theme->uuid),
            'edit_url'    => route('themes.edit', $theme->uuid),
            'label'       => $theme->label,
            'inspiration' => $generated['inspiration'] ?? '',
            'colors'      => $theme->colors ?? ['light' => [], 'dark' => []],
        ]);
    }

    // ──────────────────────────────────────────────
    //  Versionamento
    // ──────────────────────────────────────────────

    /** Lista todas as versões de um tema */
    public function listVersions(string $uuid): JsonResponse
    {
        $theme    = StudioTheme::where('uuid', $uuid)->firstOrFail();
        $versions = $theme->versions()
            ->select(['id', 'uuid', 'version', 'label', 'changelog', 'snapshot_type', 'created_at'])
            ->get();

        return response()->json(['versions' => $versions]);
    }

    /** Lista todas as receitas dinâmicas de temas */
    public function recipes(string $uuid): JsonResponse
    {
        $recipes = \App\Models\StudioAiRecipe::where('recipe_type', 'theme')
            ->select(['id', 'name', 'description', 'prompt_pattern', 'placeholder_types'])
            ->get();

        return response()->json(['recipes' => $recipes]);
    }

    /** Cria um snapshot manual da versão actual */
    public function createVersion(Request $request, string $uuid): JsonResponse
    {
        $theme = StudioTheme::where('uuid', $uuid)->firstOrFail();

        $data = $request->validate([
            'changelog' => 'nullable|string|max:1000',
        ]);

        $version = StudioThemeVersion::snapshot(
            $theme,
            $data['changelog'] ?? '',
            'manual'
        );

        return response()->json([
            'success' => true,
            'version' => [
                'uuid'          => $version->uuid,
                'version'       => $version->version,
                'label'         => $version->label,
                'changelog'     => $version->changelog,
                'snapshot_type' => $version->snapshot_type,
                'created_at'    => $version->created_at,
            ],
        ]);
    }

    /** Restaura o tema para o estado de uma versão anterior */
    public function restoreVersion(Request $request, string $uuid, string $versionUuid): JsonResponse
    {
        $theme   = StudioTheme::where('uuid', $uuid)->firstOrFail();
        $version = StudioThemeVersion::where('uuid', $versionUuid)
            ->where('studio_theme_id', $theme->id)
            ->firstOrFail();

        // Guardar snapshot automático do estado actual antes de restaurar
        StudioThemeVersion::snapshot($theme, 'Antes de restaurar v' . $version->version, 'auto');

        // Restaurar campos
        $theme->update([
            'version'       => $version->version,
            'label'         => $version->label,
            'description'   => $version->description,
            'colors'        => $version->colors,
            'fonts'         => $version->fonts,
            'sections'      => $version->sections,
            'layout_config' => $version->layout_config,
            'capabilities'  => $version->capabilities,
            'assets'        => $version->assets,
            'components'    => $version->components,
            'variants'      => $version->variants,
            'custom_css'    => $version->custom_css,
            'custom_js'     => $version->custom_js,
        ]);

        $theme->refresh();

        return response()->json([
            'success' => true,
            'message' => "Tema restaurado para v{$version->version}",
            'theme'   => $theme->toArray(),
        ]);
    }

    /** Elimina uma versão específica */
    public function deleteVersion(string $uuid, string $versionUuid): JsonResponse
    {
        $theme   = StudioTheme::where('uuid', $uuid)->firstOrFail();
        $version = StudioThemeVersion::where('uuid', $versionUuid)
            ->where('studio_theme_id', $theme->id)
            ->firstOrFail();

        $version->delete();

        return response()->json(['success' => true]);
    }

    // ──────────────────────────────────────────────
    //  Multimodal Chat
    // ──────────────────────────────────────────────

    public function chat(Request $request, string $uuid): JsonResponse
    {
        $theme = StudioTheme::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'message'   => 'required|string|min:1|max:4000',
            'history'   => 'nullable|array',
            'history.*.role'    => 'required|in:user,assistant',
            'history.*.content' => 'required|string',
            'files'     => 'nullable|array|max:5',
            'files.*'   => 'file|max:20480', // 20 MB per file
        ]);

        $history = $request->input('history', []);
        $history[] = ['role' => 'user', 'content' => $request->input('message')];

        // Process uploaded files into attachment descriptors
        $attachments = [];
        foreach ($request->file('files', []) as $file) {
            $mime = $file->getMimeType() ?? '';
            $name = $file->getClientOriginalName();
            $size = $file->getSize();

            if (in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                $attachments[] = [
                    'type' => 'image',
                    'mime' => $mime,
                    'data' => base64_encode(file_get_contents($file->getRealPath())),
                ];
            } elseif ($mime === 'application/pdf') {
                $attachments[] = [
                    'type' => 'document',
                    'data' => base64_encode(file_get_contents($file->getRealPath())),
                ];
            } elseif (str_starts_with($mime, 'audio/')) {
                $dur = $size > 0 ? round($size / 1024) . ' KB' : 'desconhecida';
                $attachments[] = [
                    'type'        => 'text_description',
                    'description' => "[Ficheiro de áudio anexado: {$name}, tipo: {$mime}, tamanho: {$dur}. Analisa este contexto para sugestões de design sonoro ou ambiente do tema.]",
                ];
            } elseif (str_starts_with($mime, 'video/')) {
                $attachments[] = [
                    'type'        => 'text_description',
                    'description' => "[Vídeo anexado: {$name}, tipo: {$mime}, tamanho: " . round($size / 1024 / 1024, 1) . " MB. Usa este contexto para sugestões de tema visual, motion design e atmosfera do site.]",
                ];
            } else {
                // Generic text/document
                $preview = '';
                if ($size < 100000) {
                    $content = @file_get_contents($file->getRealPath());
                    if ($content !== false) {
                        $preview = substr($content, 0, 2000);
                    }
                }
                $attachments[] = [
                    'type'        => 'text_description',
                    'description' => "[Documento anexado: {$name}" . ($preview ? "\n\nConteúdo:\n{$preview}" : '') . "]",
                ];
            }
        }

        // Build compact theme JSON for context (omit heavy html sections)
        $themeData = $theme->toArray();
        unset($themeData['sections'], $themeData['components']);
        $themeJson = json_encode($themeData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $message = $request->input('message');
        
        // 1. Try to match a parameterized recipe first (Camada A + B)
        if (empty($attachments)) {
            $recipeResult = \App\Models\StudioAiRecipe::matchAndResolve('theme', $message);
            if ($recipeResult) {
                $applied = $this->applyThemeUpdates($theme, $recipeResult['updates'], 'chat', $message);
                $cls = $this->classifyStep($message, $recipeResult['updates']);
                return response()->json([
                    'reply'   => $recipeResult['reply'],
                    'updates' => $recipeResult['updates'],
                    'applied' => $applied,
                    'theme'   => $applied ? $theme->fresh()->makeHidden('step_journal') : null,
                    'build'   => null,
                    'cached'  => true,
                    'step'        => $cls['step'],
                    'step_label'  => $cls['step'] ? ThemeStepEngine::label($cls['step']) : null,
                    'step_method' => $cls['method'],
                    'step_journal' => ThemeStepEngine::publicJournal($theme->fresh()->step_journal),
                ]);
            }
        }

        $cached = null;
        if (empty($attachments)) {
            $cached = \App\Models\StudioAiCommandCache::getResolution('theme', $message);
        }

        if ($cached) {
            $cached->increment('hits');
            $applied = $this->applyThemeUpdates($theme, $cached->updates, 'chat', $message);
            $cls = $this->classifyStep($message, $cached->updates);
            return response()->json([
                'reply'   => $cached->reply,
                'updates' => $cached->updates,
                'applied' => $applied,
                'theme'   => $applied ? $theme->fresh()->makeHidden('step_journal') : null,
                'build'   => $cached->build,
                'cached'  => true,
                'step'        => $cls['step'],
                'step_label'  => $cls['step'] ? ThemeStepEngine::label($cls['step']) : null,
                'step_method' => $cls['method'],
                'step_journal' => ThemeStepEngine::publicJournal($theme->fresh()->step_journal),
            ]);
        }

        try {
            $result = AIEngine::chatTheme($history, $themeJson, $attachments);
        } catch (\Throwable $e) {
            return response()->json([
                'error'    => $e->getMessage(),
                'is_fatal' => self::isFatalAiError($e),
            ], 422);
        }

        // If AI returned theme updates, apply them with deep-merge for nested fields
        $applied = $this->applyThemeUpdates($theme, $result['updates'] ?? null, 'chat', $message);
        $cls = $this->classifyStep($message, $result['updates'] ?? []);

        // Cache resolution for future reuse
        if (empty($attachments)) {
            \App\Models\StudioAiCommandCache::cacheResolution(
                'theme',
                $message,
                $result['reply'],
                $result['updates'] ?? null,
                $result['build'] ?? null
            );
        }

        return response()->json([
            'reply'   => $result['reply'],
            'updates' => $result['updates'] ?? null,
            'applied' => $applied,
            'theme'   => $applied ? $theme->fresh()->makeHidden('step_journal') : null,
            'build'   => $result['build'] ?? null,
            'step'        => $cls['step'],
            'step_label'  => $cls['step'] ? ThemeStepEngine::label($cls['step']) : null,
            'step_method' => $cls['method'],
            'step_journal' => ThemeStepEngine::publicJournal($theme->fresh()->step_journal),
        ]);
    }

    /**
     * Persiste o histórico do Chat IA (mensagens + cartões de build) do editor.
     * Chamado pelo frontend (debounced) sempre que a conversa muda, para que ao
     * reentrar no tema a conversa e as tarefas feitas não se percam.
     */
    public function saveChatHistory(Request $request, string $uuid): JsonResponse
    {
        $theme = StudioTheme::where('uuid', $uuid)->firstOrFail();

        $data = $request->validate([
            'messages'   => 'present|array|max:200',
            'messages.*' => 'array',
        ]);

        // Guarda apenas as últimas 200 mensagens (limite defensivo de tamanho).
        $messages = array_slice($data['messages'], -200);

        $theme->update(['chat_history' => $messages]);

        return response()->json(['saved' => true, 'count' => count($messages)]);
    }

    /**
     * Apply AI updates to a theme with deep-merge for nested array fields.
     * Regista cada alteração no schema espelho (step_journal) com a origem
     * indicada (chat | build | manual) e snapshot do valor anterior.
     */
    private function applyThemeUpdates(StudioTheme $theme, ?array $updates, string $source = 'chat', string $summary = ''): bool
    {
        if (empty($updates)) {
            return false;
        }

        $allowed = [
            'label', 'description', 'version', 'status',
            'colors', 'fonts', 'layout_config', 'capabilities',
            'sections', 'components', 'custom_css', 'custom_js',
            'variants', 'assets',
        ];
        $updates = array_intersect_key($updates, array_flip($allowed));

        // Campos relevantes para o espelho + snapshot do valor anterior (para revert)
        $journalFields = array_keys(array_intersect_key($updates, ThemeStepEngine::FIELD_STEP));
        $before = [];
        foreach ($journalFields as $f) {
            $before[$f] = $theme->$f;
        }

        foreach (['colors', 'layout_config', 'capabilities', 'fonts', 'assets', 'sections', 'components'] as $field) {
            if (isset($updates[$field]) && is_array($updates[$field])) {
                $existing = is_array($theme->$field) ? $theme->$field : [];
                $updates[$field] = array_replace_recursive($existing, $updates[$field]);
            }
        }

        if (empty($updates)) {
            return false;
        }

        $theme->update($updates);

        if (!empty($journalFields)) {
            ThemeStepEngine::record($theme, $journalFields, $source, $summary, $before);
        }

        return true;
    }

    /**
     * Classifica a que passo pertence um pedido (híbrido: campos → palavras-chave
     * → IA quando ambíguo). A IA só é consultada se não houver campos alterados.
     */
    private function classifyStep(string $message, ?array $updates): array
    {
        $fields = is_array($updates)
            ? array_keys(array_intersect_key($updates, ThemeStepEngine::FIELD_STEP))
            : [];
        return ThemeStepEngine::classify($message, $fields, allowAi: empty($fields));
    }

    // ──────────────────────────────────────────────
    //  Modo Construção — multi-agent theme builder
    // ──────────────────────────────────────────────

    /**
     * Planner: turns a brief (+ optional skill) into an ordered agent plan.
     * Se o cliente já enviar um plano inline (agents + direction) — vindo da
     * própria deteção de intenção do chat — saltamos a chamada de IA do
     * planeador (poupa tokens e latência), mantendo na mesma o snapshot.
     */
    public function buildPlan(Request $request, string $uuid): JsonResponse
    {
        $theme = StudioTheme::where('uuid', $uuid)->firstOrFail();

        $validIds = array_column(AIEngine::themeAgents(), 'id');
        $data = $request->validate([
            'brief'     => 'required|string|min:1|max:4000',
            'skill'     => 'nullable|string|max:60000',
            'direction' => 'nullable|string|max:2000',
            'agents'    => 'nullable|array',
            'agents.*'  => ['string', Rule::in($validIds)],
        ]);

        $snapshot = null;
        try {
            $snapshotModel = StudioThemeVersion::snapshot(
                $theme,
                "Auto-snapshot antes do Chat IA (Modo Construção) para: " . substr($data['brief'], 0, 80),
                'system'
            );
            $snapshot = [
                'uuid' => $snapshotModel->uuid,
                'version' => $snapshotModel->version,
            ];
        } catch (\Throwable $e) {
            \Log::warning("Falha ao criar snapshot do tema: " . $e->getMessage());
        }

        // Plano inline já fornecido → sem chamada de IA.
        $inlineAgents = array_values(array_intersect($data['agents'] ?? [], $validIds));
        if (!empty($inlineAgents)) {
            return response()->json([
                'direction' => $data['direction'] ?? '',
                'agents'    => $inlineAgents,
                'snapshot'  => $snapshot,
                'planned_inline' => true,
            ]);
        }

        try {
            $plan = AIEngine::buildThemePlan($data['brief'], $data['skill'] ?? '');
            $plan['snapshot'] = $snapshot;
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage(), 'is_fatal' => self::isFatalAiError($e)], 422);
        }

        return response()->json($plan);
    }

    /** Run one specialised agent and apply its updates to the theme. */
    public function buildStep(Request $request, string $uuid): JsonResponse
    {
        $theme = StudioTheme::where('uuid', $uuid)->firstOrFail();

        $validIds = array_column(AIEngine::themeAgents(), 'id');
        $data = $request->validate([
            'agent'     => ['required', 'string', Rule::in($validIds)],
            'brief'     => 'nullable|string|max:4000',
            'direction' => 'nullable|string|max:2000',
            'note'      => 'nullable|string|max:1000',
            'skill'     => 'nullable|string|max:60000',
        ]);

        // Compact context — omit heavy sections/components
        $themeData = $theme->toArray();
        unset($themeData['sections'], $themeData['components']);
        $themeJson = json_encode($themeData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        try {
            $result = AIEngine::runThemeAgent(
                $data['agent'],
                $data['brief'] ?? '',
                $data['direction'] ?? '',
                $themeJson,
                [],
                $data['note'] ?? '',
                $data['skill'] ?? '',
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage(), 'is_fatal' => self::isFatalAiError($e)], 422);
        }

        $applied = $this->applyThemeUpdates($theme, $result['updates'] ?? null, 'build', $result['reply'] ?? ('Agente ' . $data['agent']));

        return response()->json([
            'agent'   => $result['agent'],
            'reply'   => $result['reply'],
            'updates' => $result['updates'] ?? null,
            'applied' => $applied,
            'theme'   => $applied ? $theme->fresh()->makeHidden('step_journal') : null,
            'step_journal' => ThemeStepEngine::publicJournal($theme->fresh()->step_journal),
        ]);
    }

    /** Verifier: reviews the current theme and returns agents to re-run. */
    public function buildVerify(Request $request, string $uuid): JsonResponse
    {
        $theme = StudioTheme::where('uuid', $uuid)->firstOrFail();

        $data = $request->validate([
            'brief'     => 'nullable|string|max:4000',
            'direction' => 'nullable|string|max:2000',
            'skill'     => 'nullable|string|max:60000',
        ]);

        // Context: keep section keys (presence) but drop their heavy HTML bodies
        $arr      = $theme->toArray();
        $sections = is_array($arr['sections'] ?? null) ? array_keys($arr['sections']) : [];
        unset($arr['sections'], $arr['components']);
        $arr['sections_present'] = $sections;
        $themeJson = json_encode($arr, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        try {
            $result = AIEngine::verifyTheme($data['brief'] ?? '', $data['direction'] ?? '', $themeJson, $data['skill'] ?? '');
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage(), 'is_fatal' => self::isFatalAiError($e)], 422);
        }

        return response()->json($result);
    }

    // ──────────────────────────────────────────────
    //  Schema espelho — diário de passos
    // ──────────────────────────────────────────────

    /** Devolve o espelho do processo (estado + histórico por passo). */
    public function journal(string $uuid): JsonResponse
    {
        $theme = StudioTheme::where('uuid', $uuid)->firstOrFail();

        return response()->json([
            'journal' => ThemeStepEngine::publicJournal($theme->step_journal),
            'labels'  => ThemeStepEngine::STEP_LABELS,
        ]);
    }

    /** Classifica um pedido (sem aplicar) → a que passo pertence. */
    public function classifyRequest(Request $request, string $uuid): JsonResponse
    {
        StudioTheme::where('uuid', $uuid)->firstOrFail();
        $data = $request->validate(['message' => 'required|string|max:4000']);

        $cls = ThemeStepEngine::classify($data['message'], [], allowAi: true);
        return response()->json([
            'step'        => $cls['step'],
            'step_label'  => $cls['step'] ? ThemeStepEngine::label($cls['step']) : null,
            'step_method' => $cls['method'],
        ]);
    }

    /** Reverte a última alteração registada de um passo (restaura o snapshot). */
    public function revertStep(Request $request, string $uuid): JsonResponse
    {
        $theme = StudioTheme::where('uuid', $uuid)->firstOrFail();
        $data  = $request->validate([
            'step' => ['required', 'string', Rule::in(ThemeStepEngine::steps())],
        ]);

        $reverted = ThemeStepEngine::revertStep($theme, $data['step']);

        return response()->json([
            'reverted' => $reverted,
            'theme'    => $reverted ? $theme->fresh()->makeHidden('step_journal') : null,
            'journal'  => ThemeStepEngine::publicJournal($theme->fresh()->step_journal),
        ]);
    }

    // ──────────────────────────────────────────────
    //  Live Preview (Phase 2)
    // ──────────────────────────────────────────────

    public function preview(string $uuid)
    {
        $theme = StudioTheme::where('uuid', $uuid)->firstOrFail();
        session(['preview_theme_uuid' => $uuid]);

        return view('preview.theme', compact('theme'));
    }

    public function previewPage(Request $request)
    {
        $uuid = session('preview_theme_uuid');
        if (!$uuid) {
            $theme = StudioTheme::where('name', 'aero-space')->first();
            if ($theme) {
                $uuid = $theme->uuid;
            } else {
                return redirect()->route('dashboard');
            }
        }
        $theme = StudioTheme::where('uuid', $uuid)->firstOrFail();
        return view('preview.theme', compact('theme'));
    }


    // ──────────────────────────────────────────────
    //  Install directly in a local CMS instance
    // ──────────────────────────────────────────────

    public function installInCms(string $uuid): JsonResponse
    {
        $theme = StudioTheme::where('uuid', $uuid)->firstOrFail();

        $cmsUrl        = rtrim((string) StudioSetting::get('cms_url', ''), '/');
        $cmsKeyRaw     = StudioSetting::get('cms_api_key', '');
        try { $cmsKey = decrypt($cmsKeyRaw); } catch (\Throwable) { $cmsKey = $cmsKeyRaw; }

        if (empty($cmsUrl) || empty($cmsKey)) {
            return response()->json(['error' => 'CMS URL e API key não configurados em Definições.'], 422);
        }

        $zipPath = $this->buildThemeZip($theme);

        try {
            $response = Http::withToken($cmsKey)
                ->attach('package', file_get_contents($zipPath), "{$theme->name}.zip")
                ->post("{$cmsUrl}/api/v1/studio/install-theme");

            @unlink($zipPath);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => $response->json('message') ?? 'Tema instalado no CMS.',
                ]);
            }

            return response()->json([
                'error' => "CMS respondeu {$response->status()}: " . substr($response->body(), 0, 300),
            ], 422);

        } catch (\Throwable $e) {
            @unlink($zipPath);
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    // ──────────────────────────────────────────────
    //  Publish to Marketplace (Phase 2)
    // ──────────────────────────────────────────────

    public function publish(string $uuid): JsonResponse
    {
        $theme = StudioTheme::where('uuid', $uuid)->firstOrFail();

        $animusUrl = rtrim((string) StudioSetting::get('animus_api_url', 'https://animus.kwantoe.com'), '/');
        $animusKey = StudioSetting::get('animusflow_api_key', '');

        if (empty($animusKey)) {
            return response()->json(['error' => 'AnimusFlow API key not configured in Settings.'], 422);
        }

        $zipPath = $this->buildThemeZip($theme);

        try {
            $response = Http::withToken($animusKey)
                ->attach('package', file_get_contents($zipPath), "{$theme->name}.zip")
                ->post("{$animusUrl}/api/marketplace/publish", [
                    'type'        => 'theme',
                    'name'        => $theme->name,
                    'label'       => $theme->label,
                    'version'     => $theme->version ?? '1.0.0',
                    'description' => $theme->description ?? '',
                ]);

            @unlink($zipPath);

            if ($response->successful()) {
                $packageUuid = $response->json('uuid') ?? $response->json('id');
                $theme->update([
                    'is_published'        => true,
                    'status'              => 'published',
                    'animus_package_uuid' => $packageUuid,
                ]);

                // Snapshot automático ao publicar
                StudioThemeVersion::snapshot($theme, 'Publicado no Marketplace', 'publish');

                return response()->json(['success' => true, 'package_uuid' => $packageUuid]);
            }

            return response()->json([
                'error' => "Marketplace error {$response->status()}: " . substr($response->body(), 0, 200),
            ], 422);

        } catch (\Throwable $e) {
            @unlink($zipPath);
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    // ──────────────────────────────────────────────
    //  Theme Form Endpoints (preview simulation)
    // ──────────────────────────────────────────────

    /**
     * Handle the CTA / Contact form submission in preview mode.
     * In production (AnimusFlow), this route is replaced by the site's
     * own contact controller. Here we simulate a successful send and
     * return JSON so the frontend handler can show inline feedback.
     */
    public function handleContactForm(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nome'     => 'required|string|min:2|max:255',
            'email'    => 'required|email|max:255',
            'mensagem' => 'required|string|min:10|max:5000',
        ], [
            'nome.required'     => 'Por favor, introduza o nome da empresa ou entidade.',
            'nome.min'          => 'O nome deve ter pelo menos 2 caracteres.',
            'email.required'    => 'Por favor, introduza um e-mail de contacto válido.',
            'email.email'       => 'O endereço de e-mail introduzido não é válido.',
            'mensagem.required' => 'Por favor, descreva a sua missão.',
            'mensagem.min'      => 'A descrição da missão deve ter pelo menos 10 caracteres.',
        ]);

        // In preview mode we log the submission and return success.
        \Log::info('AeroSpace theme contact form (preview)', [
            'nome'    => $validated['nome'],
            'email'   => $validated['email'],
            'preview' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => '✅ Missão submetida com sucesso! A nossa equipa de operações entrará em contacto em breve.',
        ]);
    }

    /**
     * Handle the newsletter subscription form in preview mode.
     */
    public function handleNewsletterForm(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ], [
            'email.required' => 'Por favor, introduza o seu endereço de e-mail.',
            'email.email'    => 'O endereço de e-mail introduzido não é válido.',
        ]);

        \Log::info('AeroSpace newsletter subscription (preview)', [
            'email'   => $request->input('email'),
            'preview' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => '📡 Subscrição efectuada! Receberá o próximo boletim operacional em breve.',
        ]);
    }

    // ──────────────────────────────────────────────
    //  Export ZIP
    // ──────────────────────────────────────────────

    public function export(string $uuid)
    {
        $theme = StudioTheme::where('uuid', $uuid)->firstOrFail();
        $zip   = $this->buildThemeZip($theme);

        return response()->streamDownload(
            fn () => print(file_get_contents($zip)),
            "{$theme->name}.zip",
            ['Content-Type' => 'application/zip']
        );
    }

    // ──────────────────────────────────────────────
    //  Theme Prompt Export
    // ──────────────────────────────────────────────

    public function exportPrompt(string $uuid)
    {
        $theme = StudioTheme::where('uuid', $uuid)->firstOrFail();

        $author    = StudioSetting::get('studio_author', '');
        $authorUrl = StudioSetting::get('studio_author_url', '');
        $minVer    = StudioSetting::get('export_animusflow_min_ver', '1.0.0');

        // Build mapped AnimusFlow-native colors and layout settings
        $afColors  = $this->mapStudioColors($theme->colors ?? []);
        $afLayout  = $this->mapStudioLayout($theme->layout_config ?? []);

        // Map font to AnimusFlow enum (inter, poppins, dm-sans, outfit, plus-jakarta, playfair, fraunces, sora)
        $afFontMap    = ['Inter'=>'inter','Poppins'=>'poppins','DM Sans'=>'dm-sans','Outfit'=>'outfit',
                         'Plus Jakarta Sans'=>'plus-jakarta','Playfair Display'=>'playfair','Fraunces'=>'fraunces','Sora'=>'sora'];
        $headingFont  = $theme->fonts['heading'] ?? '';
        $afFont       = $afFontMap[$headingFont] ?? 'inter';

        // Derive brand_primary and brand_accent from mapped colors
        $afBrandPrimary = $afColors['light']['--primary'] ?? '#6366f1';
        $afBrandAccent  = $afColors['light']['--accent']  ?? '#8b5cf6';

        // Merge derived settings into af_settings
        $afSettings = array_merge($afLayout, [
            'layout_brand_primary' => $afBrandPrimary,
            'layout_brand_accent'  => $afBrandAccent,
            'layout_font_family'   => $afFont,
            'layout_shape'         => 'normal',
        ]);

        // Apply capabilities → AnimusFlow settings
        $caps = $theme->capabilities ?? [];
        if (!empty($caps['animations']))      $afSettings['layout_content_animations'] = '1';
        if (!empty($caps['back_to_top']))     $afSettings['layout_content_animations'] = $afSettings['layout_content_animations'] ?? '1';

        // Physical assets the theme references — emitted as a machine-readable
        // manifest so the CMS importPrompt() can auto-download them on install.
        $assetManifest = $this->collectThemeAssets($theme);

        // Build the full theme payload
        $payload = [
            'af_prompt_version' => '1.0',
            'generated_at'      => now()->toIso8601String(),
            'generator'         => 'AnimusFlowStudio',
            'meta' => [
                'uuid'        => $theme->uuid,
                'name'        => $theme->name,
                'label'       => $theme->label,
                'description' => $theme->description ?? '',
                'version'     => $theme->version ?? '1.0.0',
                'status'      => $theme->status,
                'requires'    => $minVer,
                'author'      => $author,
                'author_url'  => $authorUrl,
            ],
            // Studio-native format (for Studio re-import)
            'design' => [
                'colors'   => $theme->colors   ?? [],
                'fonts'    => $theme->fonts    ?? [],
                'variants' => $theme->variants ?? [],
            ],
            'layout'       => $theme->layout_config ?? [],
            'capabilities' => $theme->capabilities  ?? [],
            'structure'    => [
                'sections'   => $theme->sections   ?? [],
                'components' => $theme->components ?? [],
            ],
            'assets'  => $theme->assets ?? [],
            'code'    => [
                'css' => $theme->custom_css ?? '',
                'js'  => $theme->custom_js  ?? '',
            ],
            // AnimusFlow-native format (for direct installation)
            'af_install' => [
                'theme_json' => [
                    'name'        => $theme->name,
                    'label'       => $theme->label,
                    'description' => $theme->description ?? '',
                    'version'     => $theme->version ?? '1.0.0',
                    'author'      => $author,
                    'author_url'  => $authorUrl,
                    'settings'    => $theme->theme_settings ?? [],   // schema "Definições do Tema"
                    'blocks'      => $this->allBlockTypes(),
                ],
                'colors'     => $afColors,   // mapped to --primary, --accent, etc.
                'font'       => [
                    'family'     => $headingFont ?: 'Inter',
                    'google_url' => $headingFont ? "https://fonts.googleapis.com/css2?family=" . rawurlencode($headingFont) . ":wght@400;500;600;700&display=swap" : null,
                ],
                'extra_css'  => $theme->custom_css ?? '',
                'extra_js'   => $theme->custom_js  ?? '',
                'settings'   => $afSettings,   // ready to write to AnimusFlow settings table
                'assets'     => $assetManifest, // physical files to auto-download on install
            ],
        ];

        // Compute a simple checksum for integrity verification
        $json     = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $checksum = hash('sha256', $json);

        // Capabilities summary for the human-readable header
        $caps = collect($theme->capabilities ?? [])
            ->filter(fn ($v) => $v === true)
            ->keys()
            ->map(fn ($k) => str_replace('_', ' ', $k))
            ->join(', ');

        $sections = implode(', ', array_keys($theme->sections   ?? []));
        $comps    = implode(', ', array_keys($theme->components ?? []));

        $assetsNotice = '';
        if (!empty($assetManifest)) {
            $assetsNotice = "\nRecursos Físicos (descarregados AUTOMATICAMENTE na importação):\n"
                . "  Este prompt é texto e não embute binários. Ao importar, o AnimusFlow descarrega\n"
                . "  cada ficheiro abaixo do repositório oficial e coloca-o em public/ no destino indicado:\n";
            foreach ($assetManifest as $a) {
                $assetsNotice .= "  - public/{$a['dest']}  ←  {$a['url']}\n";
            }
        }

        $divider = str_repeat('━', 60);

        $prompt = <<<PROMPT
{$divider}
 ANIMUSFLOW THEME PROMPT  v1.0
 Gerado por: AnimusFlowStudio
 Tema: {$theme->label}  ({$theme->name})
 Versão: {$theme->version}   |   AnimusFlow >= {$minVer}
 Gerado em: {$payload['generated_at']}
{$divider}

Para instalar este tema no AnimusFlow:
  1. Vai a AnimusFlow Admin → Temas → Importar Prompt
  2. Cola este bloco completo (incluindo as marcações [AF:THEME:BEGIN] e [AF:THEME:END])
  3. Clica em "Instalar Tema"

O AnimusFlow irá:
  ✓ Criar o tema com todas as configurações
  ✓ Registar as cores, tipografia e variantes
  ✓ Configurar o layout, header e footer
  ✓ Ativar as capacidades: {$caps}
  ✓ Registar as secções: {$sections}
  ✓ Registar os componentes: {$comps}
  ✓ Injetar o CSS e JS customizados
  ✓ Associar os assets (imagens/vídeos)
{$assetsNotice}
{$divider}
[AF:THEME:BEGIN]
{$json}
[AF:THEME:END]
{$divider}
CHECKSUM: sha256:{$checksum}
{$divider}
PROMPT;

        return response($prompt, 200, [
            'Content-Type'        => 'text/plain; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$theme->name}.afprompt\"",
        ]);
    }

    /**
     * Collect the physical assets a theme references (videos/images/docs) into a
     * machine-readable manifest the CMS importPrompt() can auto-download.
     * Each entry: { url (raw GitHub), dest (path relative to public/) }.
     */
    private function collectThemeAssets(StudioTheme $theme): array
    {
        $base = rtrim((string) StudioSetting::get(
            'export_asset_raw_base',
            'https://raw.githubusercontent.com/samsoft111/AnimusFlow/main/core/public'
        ), '/');

        // Gather every string that may contain a /videos|/images|/docs path
        $haystacks = [(string) $theme->custom_css];
        foreach ((array) $theme->sections as $blade)  { if (is_string($blade)) $haystacks[] = $blade; }
        foreach ((array) $theme->layout_config as $v) { if (is_string($v))     $haystacks[] = $v; }
        foreach ((array) $theme->theme_settings as $f) {
            $d = $f['default'] ?? null;
            if (is_string($d)) { $haystacks[] = $d; }
            if (is_array($d))  { foreach ($d as $dv) { if (is_string($dv)) $haystacks[] = $dv; } }
        }
        // PDF guide (bundled in the ZIP; linked here for prompt installs)
        $haystacks[] = '/docs/' . strtolower($theme->name) . '_guide.pdf';

        $allowedExt = ['mp4','webm','ogg','jpg','jpeg','png','webp','gif','svg','pdf','ico'];
        $paths = [];
        foreach ($haystacks as $h) {
            if (preg_match_all('#/(?:videos|images|docs)/[A-Za-z0-9._\-/]+\.[A-Za-z0-9]+#', $h, $mm)) {
                foreach ($mm[0] as $p) {
                    $ext = strtolower(pathinfo($p, PATHINFO_EXTENSION));
                    if (in_array($ext, $allowedExt, true)) { $paths[$p] = true; }
                }
            }
        }

        $manifest = [];
        foreach (array_keys($paths) as $p) {
            $dest = ltrim($p, '/');
            $manifest[] = ['url' => $base . '/' . $dest, 'dest' => $dest];
        }
        return $manifest;
    }

    // ──────────────────────────────────────────────
    //  ZIP builder
    // ──────────────────────────────────────────────

    private function buildThemeZip(StudioTheme $theme): string
    {
        $tmpDir   = storage_path("app/export-{$theme->uuid}");
        $themeDir = "{$tmpDir}/{$theme->name}";
        File::ensureDirectoryExists($themeDir . '/sections');

        // theme.json manifest
        file_put_contents("{$themeDir}/theme.json", json_encode([
            'name'        => $theme->name,
            'label'       => $theme->label,
            'description' => $theme->description,
            'version'     => $theme->version ?? '1.0.0',
            'fonts'       => $theme->fonts   ?? [],
            'blocks'      => $this->allBlockTypes(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Layout — inject colour tokens + fonts
        $defaultLayout = $this->animusFlowDefaultPath('layout.blade.php');
        if (file_exists($defaultLayout)) {
            $layout = file_get_contents($defaultLayout);
            $layout = $this->injectColors($layout, $theme->colors ?? []);
            if (!empty($theme->fonts)) {
                $layout = $this->injectFonts($layout, $theme->fonts);
            }
            file_put_contents("{$themeDir}/layout.blade.php", $layout);
        }

        // page.blade.php
        $defaultPage = $this->animusFlowDefaultPath('page.blade.php');
        if (file_exists($defaultPage)) {
            $page = str_replace('theme.default', "theme.{$theme->name}", file_get_contents($defaultPage));
            file_put_contents("{$themeDir}/page.blade.php", $page);
        }

        // Sections — copy defaults, overlay AI-generated
        foreach (glob($this->animusFlowDefaultPath('sections') . '/*.blade.php') ?: [] as $f) {
            File::copy($f, "{$themeDir}/sections/" . basename($f));
        }
        foreach ($theme->sections ?? [] as $type => $blade) {
            file_put_contents("{$themeDir}/sections/{$type}.blade.php", $blade);
        }

        // Component overrides (header / footer / nav)
        $components = $theme->components ?? [];
        if (!empty($components)) {
            File::ensureDirectoryExists("{$themeDir}/components");
            foreach ($components as $name => $blade) {
                if (!empty($blade)) {
                    file_put_contents("{$themeDir}/components/{$name}.blade.php", $blade);
                }
            }
        }

        // Custom CSS
        if (!empty($theme->custom_css)) {
            $minify = (bool) StudioSetting::get('export_minify_html', '0');
            $css = $minify
                ? preg_replace(['/\/\*.*?\*\//s', '/\s+/', '/\s*([{:;,}])\s*/'], ['', ' ', '$1'], $theme->custom_css)
                : $theme->custom_css;
            file_put_contents("{$themeDir}/custom.css", $css);
        }

        // Custom JS
        if (!empty($theme->custom_js)) {
            file_put_contents("{$themeDir}/custom.js", $theme->custom_js);
        }

        // Copy PDF guide if it exists
        $pdfFile = public_path("docs/" . strtolower($theme->name) . "_guide.pdf");
        if (file_exists($pdfFile)) {
            File::ensureDirectoryExists("{$themeDir}/docs");
            File::copy($pdfFile, "{$themeDir}/docs/" . basename($pdfFile));
        }

        // Assets — copy uploaded files
        File::ensureDirectoryExists("{$themeDir}/assets");
        foreach ($theme->assets ?? [] as $slot => $url) {
            $localPath = public_path(str_replace('/storage/', 'storage/', $url));
            if (file_exists($localPath)) {
                File::copy($localPath, "{$themeDir}/assets/" . basename($localPath));
            }
        }

        // README.md
        if (StudioSetting::get('export_include_readme', '1') === '1') {
            $author    = StudioSetting::get('studio_author', '');
            $authorUrl = StudioSetting::get('studio_author_url', '');
            $minVer    = StudioSetting::get('export_animusflow_min_ver', '1.0.0');
            $readme    = "# {$theme->label}\n\n"
                . ($theme->description ? "{$theme->description}\n\n" : '')
                . "**Version:** {$theme->version}\n"
                . "**Requires AnimusFlow:** {$minVer}+\n"
                . ($author    ? "**Author:** {$author}" . ($authorUrl ? " <{$authorUrl}>" : '') . "\n" : '')
                . "\n## Installation\n\n"
                . "Upload this ZIP via AnimusFlow Admin → Extensions → Themes → Upload ZIP.\n"
                . "\n## Capabilities\n\n"
                . implode("\n", array_map(
                    fn ($k, $v) => "- **{$k}**: " . ($v ? '✅' : '❌'),
                    array_keys($theme->capabilities ?? []),
                    array_values($theme->capabilities ?? [])
                )) . "\n";

            // Append theme-specific guide if it exists
            $guideFile = base_path("skills/themes/" . strtolower($theme->name) . "_guide.md");
            if (file_exists($guideFile)) {
                $readme .= "\n" . file_get_contents($guideFile);
            }

            file_put_contents("{$themeDir}/README.md", $readme);
        }

        // Extended theme.json with layout + capabilities
        file_put_contents("{$themeDir}/theme.json", json_encode([
            'name'          => $theme->name,
            'label'         => $theme->label,
            'description'   => $theme->description,
            'version'       => $theme->version ?? '1.0.0',
            'requires'      => StudioSetting::get('export_animusflow_min_ver', '1.0.0'),
            'author'        => StudioSetting::get('studio_author', ''),
            'author_url'    => StudioSetting::get('studio_author_url', ''),
            'fonts'         => $theme->fonts   ?? [],
            'layout'        => $theme->layout_config,
            'capabilities'  => $theme->capabilities,
            'settings'      => $theme->theme_settings ?? [],
            'blocks'        => $this->allBlockTypes(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Build ZIP
        $zipPath = storage_path("app/{$theme->name}.zip");
        $zip     = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        // Normalise prefix to forward slashes for cross-platform comparison
        $tmpDirNorm = rtrim(str_replace('\\', '/', $tmpDir), '/') . '/';
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($tmpDir)) as $file) {
            if (!$file->isDir()) {
                $pathNorm  = str_replace('\\', '/', $file->getPathname());
                $entryName = ltrim(str_replace($tmpDirNorm, '', $pathNorm), '/');
                $zip->addFile($file->getPathname(), $entryName);
            }
        }
        $zip->close();
        File::deleteDirectory($tmpDir);

        return $zipPath;
    }

    // ──────────────────────────────────────────────
    //  Helpers
    // ──────────────────────────────────────────────

    private function animusFlowDefaultPath(string $relative): string
    {
        $corePath = rtrim((string) StudioSetting::get('theme_animusflow_path', '../animusFlow/core'), '/\\');
        return base_path("{$corePath}/resources/views/theme/default/{$relative}");
    }

    private function allBlockTypes(): array
    {
        $themeJson = $this->animusFlowDefaultPath('theme.json');
        if (file_exists($themeJson)) {
            $data = json_decode(file_get_contents($themeJson), true);
            return $data['blocks'] ?? [];
        }
        return [
            'hero', 'features', 'text', 'cta', 'testimonials', 'pricing', 'gallery', 'faq',
            'ai_chatbox', 'ai_recommendations', 'ai_summary', 'ai_faq', 'ai_search', 'ai_personalized'
        ];
    }

    // ──────────────────────────────────────────────
    //  Token mapping: Studio → AnimusFlow CSS vars
    // ──────────────────────────────────────────────

    /**
     * The Studio stores 11 Tailwind-style tokens (--color-primary, etc.).
     * AnimusFlow's layout.blade.php uses 2-word tokens (--primary, --accent, --bg, etc.).
     * This map translates Studio → AnimusFlow before injecting into layout.
     */
    private const TOKEN_MAP = [
        '--color-primary'     => '--primary',
        '--color-secondary'   => '--primary-h',   // closest equivalent: darker shade
        '--color-accent'      => '--accent',
        '--color-background'  => '--bg',
        '--color-foreground'  => '--text',
        '--color-card'        => '--bg-subtle',
        '--color-muted'       => '--bg-muted',
        '--color-border'      => '--border',
        '--color-success'     => '--success',
        '--color-destructive' => '--danger',
        // --color-warning has no direct equivalent; injected as extra CSS var
    ];

    /**
     * Layout config field mapping: Studio → AnimusFlow settings keys.
     * Used by exportPrompt() to build af_settings payload.
     */
    private const LAYOUT_MAP = [
        'header_type'          => 'layout_header_bg',
        'nav_position'         => 'layout_header_menu',
        'max_width'            => 'layout_content_max_width',
        'spacing'              => 'layout_content_spacing',
        'show_dark_toggle'     => 'layout_header_show_toggle',
        'header_sticky'        => 'layout_header_sticky',
        'header_cta_text'      => 'layout_header_cta_text',
        'header_cta_url'       => 'layout_header_cta_url',
        'footer_copyright'     => 'layout_footer_copyright',
        'menu_layout'          => 'layout_menu_layout',
        'normal_menu_position' => 'layout_normal_menu_position',
        'back_to_top'          => null,   // handled separately via capabilities
    ];

    private function mapStudioColors(array $colors): array
    {
        $mapped = ['light' => [], 'dark' => []];

        foreach (['light', 'dark'] as $mode) {
            foreach ($colors[$mode] ?? [] as $studioVar => $value) {
                $afVar = self::TOKEN_MAP[$studioVar] ?? null;
                if ($afVar !== null) {
                    $mapped[$mode][$afVar] = $value;
                }
            }
            // Always pass --color-warning through as an extra var (not in AnimusFlow's default)
            if (isset($colors[$mode]['--color-warning'])) {
                $mapped[$mode]['--warning'] = $colors[$mode]['--color-warning'];
            }
        }

        return $mapped;
    }

    private function mapStudioLayout(array $layoutConfig): array
    {
        $af = [];
        foreach (self::LAYOUT_MAP as $studioKey => $afKey) {
            if ($afKey === null || !array_key_exists($studioKey, $layoutConfig)) {
                continue;
            }
            $value = $layoutConfig[$studioKey];
            // Convert booleans to '1'/'0' strings (AnimusFlow stores settings as strings)
            $af[$afKey] = is_bool($value) ? ($value ? '1' : '0') : (string) $value;
        }
        return $af;
    }

    private function injectColors(string $layout, array $colors): string
    {
        // Map Studio token names → AnimusFlow token names before injecting
        $mapped = $this->mapStudioColors($colors);

        $light = $mapped['light'];
        $dark  = $mapped['dark'];

        // Split at [data-theme="dark"] to apply light and dark independently
        if (preg_match('/\[data-theme="dark"\]\s*\{/', $layout, $m, PREG_OFFSET_CAPTURE)) {
            $selectorStart = $m[0][1];
            $openBrace     = strpos($layout, '{', $selectorStart);
            $closeBrace    = strpos($layout, '}', $openBrace + 1);

            $lightSection = substr($layout, 0, $selectorStart);
            $darkHeader   = substr($layout, $selectorStart, $openBrace - $selectorStart + 1);
            $darkContent  = substr($layout, $openBrace + 1, $closeBrace - $openBrace - 1);
            $afterDark    = substr($layout, $closeBrace);

            foreach ($light as $var => $value) {
                $lightSection = $this->replaceCssVar($lightSection, $var, $value);
            }
            foreach ($dark as $var => $value) {
                $darkContent = $this->replaceCssVar($darkContent, $var, $value);
            }

            $layout = $lightSection . $darkHeader . $darkContent . $afterDark;
        } else {
            // Fallback: replace in whole file
            foreach ($light as $var => $value) {
                $layout = $this->replaceCssVar($layout, $var, $value);
            }
        }

        return $layout;
    }

    private function replaceCssVar(string $css, string $var, string $value): string
    {
        return preg_replace(
            '/(' . preg_quote($var, '/') . '\s*:\s*)[^;]+;/',
            '${1}' . $value . ';',
            $css,
            1
        ) ?? $css;
    }

    private function injectFonts(string $layout, array $fonts): string
    {
        $heading = $fonts['heading'] ?? '';
        $body    = $fonts['body']    ?? '';

        $families = array_filter([$heading, $body]);
        if (empty($families)) {
            return $layout;
        }

        $googleUrl = 'https://fonts.googleapis.com/css2?'
            . implode('&', array_map(
                fn ($f) => 'family=' . rawurlencode($f) . ':wght@400;500;600;700',
                $families
            )) . '&display=swap';

        $fontLink = "<link rel=\"preconnect\" href=\"https://fonts.googleapis.com\">\n"
            . "    <link rel=\"preconnect\" href=\"https://fonts.gstatic.com\" crossorigin>\n"
            . "    <link href=\"{$googleUrl}\" rel=\"stylesheet\">";

        return str_replace('</head>', "    {$fontLink}\n</head>", $layout);
    }

    /** Determine if an AI exception represents a systemic/fatal error (e.g. auth, quota, network). */
    private static function isFatalAiError(\Throwable $e): bool
    {
        $msg = $e->getMessage();

        // 1. Chave não configurada / Provedor em falta
        if (str_contains($msg, 'Chave AI não configurada') || str_contains($msg, 'No AI API key configured')) {
            return true;
        }

        // 2. Erros de rede ou SSL do cURL
        if (str_contains($msg, 'cURL error') || str_contains($msg, 'SSL certificate') || str_contains($msg, 'Could not resolve host') || str_contains($msg, 'Connection refused')) {
            return true;
        }

        // 3. Erros HTTP das APIs (401 Unauthorized, 403 Forbidden, 429 Rate Limit/Quota, 500/503 Down)
        if (str_contains($msg, 'API error:')) {
            if (stripos($msg, 'rate_limit') !== false || stripos($msg, 'quota') !== false || stripos($msg, 'exhausted') !== false || str_contains($msg, '429')) {
                return true;
            }
            if (stripos($msg, 'unauthorized') !== false || stripos($msg, 'invalid_key') !== false || stripos($msg, 'invalid_api_key') !== false || str_contains($msg, '401') || str_contains($msg, '403')) {
                return true;
            }
            if (str_contains($msg, '500') || str_contains($msg, '503') || stripos($msg, 'overloaded') !== false || stripos($msg, 'down') !== false) {
                return true;
            }
        }

        return false;
    }
}
