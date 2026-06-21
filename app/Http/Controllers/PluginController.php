<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StudioPlugin;
use App\Models\StudioPluginVersion;
use App\Models\StudioSetting;
use App\Services\AIEngine;
use App\Services\PluginStepEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use ZipArchive;

class PluginController extends Controller
{
    // ──────────────────────────────────────────────
    //  CRUD
    // ──────────────────────────────────────────────

    public function index(): Response
    {
        return Inertia::render('Plugins/Index', [
            'plugins' => StudioPlugin::latest()->get([
                'id', 'uuid', 'name', 'label', 'version',
                'status', 'is_published', 'hooks', 'created_at',
            ]),
        ]);
    }

    public function create(): RedirectResponse
    {
        $counter = StudioPlugin::withTrashed()->count() + 1;
        $plugin  = StudioPlugin::create([
            'name'   => 'novo-plugin-' . $counter,
            'label'  => 'Novo Plugin ' . $counter,
            'version' => '1.0.0',
            'status' => 'draft',
            'hooks'  => ['page.render'],
        ]);

        return redirect()->route('plugins.edit', $plugin->uuid)
            ->with('success', 'Plugin criado — edita os detalhes abaixo.');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'label'       => 'required|string|max:200',
            'description' => 'nullable|string|max:1000',
            'version'     => 'nullable|string|max:20',
        ]);

        $plugin = StudioPlugin::create([
            'name'        => self::uniqueSlug($data['label']),
            'label'       => $data['label'],
            'description' => $data['description'] ?? null,
            'version'     => ($data['version'] ?? '') ?: '1.0.0',
            'status'      => 'draft',
            'hooks'       => ['page.render'],
        ]);

        return redirect()->route('plugins.edit', $plugin->uuid)
            ->with('success', 'Plugin criado — edita os detalhes abaixo.');
    }

    /** Generate a unique, schema-valid slug (name) from a human label. */
    private static function uniqueSlug(string $label): string
    {
        $base = substr(\Illuminate\Support\Str::slug($label), 0, 40) ?: 'plugin';
        $slug = $base;
        $i = 2;
        while (StudioPlugin::withTrashed()->where('name', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    public function edit(string $uuid): Response
    {
        $plugin = StudioPlugin::where('uuid', $uuid)->firstOrFail();

        $journal = PluginStepEngine::publicJournal($plugin->step_journal);

        return Inertia::render('Plugins/Edit', [
            'plugin'       => $plugin->makeHidden('step_journal'),
            'pluginAgents' => AIEngine::pluginAgents(),
            'stepJournal'  => $journal,
        ]);
    }

    public function update(Request $request, string $uuid): RedirectResponse
    {
        $plugin = StudioPlugin::where('uuid', $uuid)->firstOrFail();

        $data = $request->validate([
            'label'                  => 'sometimes|string|max:200',
            'description'            => 'nullable|string|max:1000',
            'version'                => 'nullable|string|max:20',
            'author'                 => 'nullable|string|max:200',
            'author_url'             => 'nullable|url|max:500',
            'category'               => 'nullable|string|max:100',
            'tags'                   => 'nullable|array',
            'license'                => 'nullable|string|max:100',
            'min_animusflow_version' => 'nullable|string|max:20',
            'homepage_url'           => 'nullable|url|max:500',
            'hooks'                  => 'nullable|array',
            'settings_schema'        => 'nullable|array',
            'plugin_php'             => 'nullable|string',
            'widget_blade'           => 'nullable|string',
            'widget_js'              => 'nullable|string',
            'custom_css'             => 'nullable|string',
            'readme'                 => 'nullable|string',
            'status'                 => 'nullable|in:draft,ready,published',
        ]);

        // Snapshot dos campos do espelho ANTES de gravar (para detectar mudanças reais)
        $journalFields = array_keys(array_intersect_key($data, PluginStepEngine::FIELD_STEP));
        $before = [];
        foreach ($journalFields as $f) {
            $before[$f] = $plugin->$f;
        }

        $plugin->update($data);

        // Regista no espelho apenas os campos que mudaram de facto (origem: manual)
        $changed = [];
        foreach ($before as $f => $prev) {
            if (json_encode($prev) !== json_encode($plugin->$f)) {
                $changed[] = $f;
            }
        }
        if (!empty($changed)) {
            PluginStepEngine::record($plugin, $changed, 'manual', 'Edição manual nos campos: ' . implode(', ', $changed), $before);
        }

        return back()->with('success', 'Plugin saved.');
    }

    public function destroy(string $uuid): RedirectResponse
    {
        StudioPlugin::where('uuid', $uuid)->firstOrFail()->delete();

        return redirect()->route('plugins.index')->with('success', 'Plugin deleted.');
    }

    // ──────────────────────────────────────────────
    //  AI Generation
    // ──────────────────────────────────────────────

    public function generateAi(Request $request, string $uuid): JsonResponse
    {
        $plugin = StudioPlugin::where('uuid', $uuid)->firstOrFail();

        $request->validate(['prompt' => 'required|string|min:5|max:500']);

        try {
            $result = AIEngine::generatePlugin(
                $request->input('prompt'),
                $plugin->hooks ?? ['page.render'],
                $plugin->label
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $updates = [
            'plugin_php'      => $result['plugin_php']      ?? $plugin->plugin_php,
            'widget_blade'    => $result['widget_blade']    ?? $plugin->widget_blade,
            'widget_js'       => $result['widget_js']       ?? $plugin->widget_js,
            'settings_schema' => $result['settings_schema'] ?? $plugin->settings_schema,
        ];

        $applied = $this->applyPluginUpdates($plugin, $updates, 'chat', $request->input('prompt'));

        $fresh = $plugin->fresh();

        return response()->json([
            'success'         => true,
            'plugin_php'      => $fresh->plugin_php,
            'widget_blade'    => $fresh->widget_blade,
            'widget_js'       => $fresh->widget_js,
            'settings_schema' => $fresh->settings_schema,
            'step_journal'    => PluginStepEngine::publicJournal($fresh->step_journal),
        ]);
    }

    // ──────────────────────────────────────────────
    //  Plugin Inspiration (category-based examples)
    // ──────────────────────────────────────────────

    public function inspire(Request $request, string $uuid): JsonResponse
    {
        $plugin = StudioPlugin::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'category' => ['required', 'string', 'max:60'],
        ]);

        try {
            $result = AIEngine::inspirePlugin(
                category:    $request->input('category'),
                pluginName:  $plugin->name,
                pluginLabel: $plugin->label,
                hooks:       $plugin->hooks ?? ['page.render'],
                description: $plugin->description ?? '',
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json($result);
    }

    // ──────────────────────────────────────────────
    //  Multimodal Chat
    // ──────────────────────────────────────────────

    public function chat(Request $request, string $uuid): JsonResponse
    {
        $plugin = StudioPlugin::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'message'           => 'required|string|min:1|max:4000',
            'history'           => 'nullable|array',
            'history.*.role'    => 'required|in:user,assistant',
            'history.*.content' => 'required|string',
            'files'             => 'nullable|array|max:5',
            'files.*'           => 'file|max:20480',
        ]);

        $history   = $request->input('history', []);
        $history[] = ['role' => 'user', 'content' => $request->input('message')];

        $attachments = [];
        foreach ($request->file('files', []) as $file) {
            $mime = $file->getMimeType() ?? '';
            $name = $file->getClientOriginalName();
            $size = $file->getSize();

            if (in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                $attachments[] = ['type' => 'image', 'mime' => $mime, 'data' => base64_encode(file_get_contents($file->getRealPath()))];
            } elseif ($mime === 'application/pdf') {
                $attachments[] = ['type' => 'document', 'data' => base64_encode(file_get_contents($file->getRealPath()))];
            } elseif (str_starts_with($mime, 'audio/')) {
                $attachments[] = ['type' => 'text_description', 'description' => "[Ficheiro de áudio: {$name}, {$mime}, " . round($size / 1024) . " KB. Usa para inspiração sonora/ambiente do widget.]"];
            } elseif (str_starts_with($mime, 'video/')) {
                $attachments[] = ['type' => 'text_description', 'description' => "[Vídeo: {$name}, " . round($size / 1024 / 1024, 1) . " MB. Usa para inspiração visual do plugin.]"];
            } else {
                $preview = '';
                if ($size < 100000) {
                    $content = @file_get_contents($file->getRealPath());
                    if ($content !== false) $preview = substr($content, 0, 2000);
                }
                $attachments[] = ['type' => 'text_description', 'description' => "[Documento: {$name}" . ($preview ? "\n\nConteúdo:\n{$preview}" : '') . "]"];
            }
        }

        $pluginData = $plugin->toArray();
        unset($pluginData['plugin_php'], $pluginData['widget_blade'], $pluginData['widget_js']);
        $pluginJson = json_encode($pluginData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $message = $request->input('message');
        
        // 1. Try to match a parameterized recipe first (Camada A + B)
        if (empty($attachments)) {
            $recipeResult = \App\Models\StudioAiRecipe::matchAndResolve('plugin', $message);
            if ($recipeResult) {
                $applied = $this->applyPluginUpdates($plugin, $recipeResult['updates'], 'chat', $message);
                $cls = $this->classifyStep($message, $recipeResult['updates']);
                return response()->json([
                    'reply'   => $recipeResult['reply'],
                    'updates' => $recipeResult['updates'],
                    'applied' => $applied,
                    'plugin'  => $applied ? $plugin->fresh()->makeHidden('step_journal') : null,
                    'build'   => null,
                    'cached'  => true,
                    'step'        => $cls['step'],
                    'step_label'  => $cls['step'] ? PluginStepEngine::label($cls['step']) : null,
                    'step_method' => $cls['method'],
                    'step_journal' => PluginStepEngine::publicJournal($plugin->fresh()->step_journal),
                ]);
            }
        }

        $cached = null;
        if (empty($attachments)) {
            $cached = \App\Models\StudioAiCommandCache::getResolution('plugin', $message);
        }

        if ($cached) {
            $cached->increment('hits');
            $applied = $this->applyPluginUpdates($plugin, $cached->updates, 'chat', $message);
            $cls = $this->classifyStep($message, $cached->updates);
            return response()->json([
                'reply'   => $cached->reply,
                'updates' => $cached->updates,
                'applied' => $applied,
                'plugin'  => $applied ? $plugin->fresh()->makeHidden('step_journal') : null,
                'build'   => $cached->build,
                'cached'  => true,
                'step'        => $cls['step'],
                'step_label'  => $cls['step'] ? PluginStepEngine::label($cls['step']) : null,
                'step_method' => $cls['method'],
                'step_journal' => PluginStepEngine::publicJournal($plugin->fresh()->step_journal),
            ]);
        }

        try {
            $result = AIEngine::chatPlugin($history, $pluginJson, $attachments);
        } catch (\Throwable $e) {
            return response()->json([
                'error'    => $e->getMessage(),
                'is_fatal' => self::isFatalAiError($e),
            ], 422);
        }

        $applied = $this->applyPluginUpdates($plugin, $result['updates'] ?? null, 'chat', $message);
        $cls = $this->classifyStep($message, $result['updates'] ?? []);

        // Cache resolution for future reuse
        if (empty($attachments)) {
            \App\Models\StudioAiCommandCache::cacheResolution(
                'plugin',
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
            'plugin'  => $applied ? $plugin->fresh()->makeHidden('step_journal') : null,
            'build'   => $result['build'] ?? null,
            'step'        => $cls['step'],
            'step_label'  => $cls['step'] ? PluginStepEngine::label($cls['step']) : null,
            'step_method' => $cls['method'],
            'step_journal' => PluginStepEngine::publicJournal($plugin->fresh()->step_journal),
        ]);
    }

    /**
     * Apply AI updates to a plugin (auto-save), filtered to the allowed fields.
     * Regista cada alteração no schema espelho (step_journal) com a origem
     * indicada (chat | build | manual) e snapshot do valor anterior.
     */
    private function applyPluginUpdates(StudioPlugin $plugin, ?array $updates, string $source = 'chat', string $summary = ''): bool
    {
        if (empty($updates)) {
            return false;
        }

        $allowed = ['label', 'description', 'version', 'status', 'hooks', 'plugin_php', 'widget_blade', 'widget_js', 'custom_css', 'settings_schema', 'readme'];
        $updates = array_intersect_key($updates, array_flip($allowed));

        if (empty($updates)) {
            return false;
        }

        // Campos relevantes para o espelho + snapshot do valor anterior (para revert)
        $journalFields = array_keys(array_intersect_key($updates, PluginStepEngine::FIELD_STEP));
        $before = [];
        foreach ($journalFields as $f) {
            $before[$f] = $plugin->$f;
        }

        $plugin->update($updates);

        if (!empty($journalFields)) {
            PluginStepEngine::record($plugin, $journalFields, $source, $summary, $before);
        }

        return true;
    }

    /**
     * Classifica a que passo pertence um pedido de plugin (híbrido: campos →
     * palavras-chave → IA quando ambíguo). A IA só é consultada se não houver
     * campos alterados.
     */
    private function classifyStep(string $message, ?array $updates): array
    {
        $fields = is_array($updates)
            ? array_keys(array_intersect_key($updates, PluginStepEngine::FIELD_STEP))
            : [];
        return PluginStepEngine::classify($message, $fields, allowAi: empty($fields));
    }

    // ──────────────────────────────────────────────
    //  Modo Construção — multi-agent plugin builder
    // ──────────────────────────────────────────────

    /** Planner: turns a brief into an ordered agent plan. */
    public function buildPlan(Request $request, string $uuid): JsonResponse
    {
        $plugin = StudioPlugin::where('uuid', $uuid)->firstOrFail();

        $data = $request->validate([
            'brief' => 'required|string|min:1|max:4000',
            'skill' => 'nullable|string|max:60000',
        ]);

        $snapshot = null;
        try {
            $this->saveVersionSnapshot($plugin, "Auto-snapshot antes do Chat IA (Modo Construção) para: " . substr($data['brief'], 0, 80));
            $snapshotModel = StudioPluginVersion::where('studio_plugin_id', $plugin->id)
                ->where('version', $plugin->version)->first();
            if ($snapshotModel) {
                $snapshot = [
                    'id' => $snapshotModel->id,
                    'version' => $snapshotModel->version,
                ];
            }
        } catch (\Throwable $e) {
            \Log::warning("Falha ao criar snapshot do plugin: " . $e->getMessage());
        }

        try {
            $plan = AIEngine::buildPluginPlan($data['brief'], $data['skill'] ?? '');
            $plan['snapshot'] = $snapshot;
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage(), 'is_fatal' => self::isFatalAiError($e)], 422);
        }

        return response()->json($plan);
    }

    /** Run one specialised agent and apply its updates to the plugin. */
    public function buildStep(Request $request, string $uuid): JsonResponse
    {
        $plugin = StudioPlugin::where('uuid', $uuid)->firstOrFail();

        $validIds = array_column(AIEngine::pluginAgents(), 'id');
        $data = $request->validate([
            'agent'     => ['required', 'string', Rule::in($validIds)],
            'brief'     => 'nullable|string|max:4000',
            'direction' => 'nullable|string|max:2000',
            'note'      => 'nullable|string|max:1000',
            'skill'     => 'nullable|string|max:60000',
        ]);

        $pluginData = $plugin->toArray();
        $pluginJson = json_encode($pluginData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        try {
            $result = AIEngine::runPluginAgent(
                $data['agent'],
                $data['brief'] ?? '',
                $data['direction'] ?? '',
                $pluginJson,
                [],
                $data['note'] ?? '',
                $data['skill'] ?? '',
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage(), 'is_fatal' => self::isFatalAiError($e)], 422);
        }

        $applied = $this->applyPluginUpdates($plugin, $result['updates'] ?? null, 'build', $result['reply'] ?? ('Agente ' . $data['agent']));

        return response()->json([
            'agent'   => $result['agent'],
            'reply'   => $result['reply'],
            'updates' => $result['updates'] ?? null,
            'applied' => $applied,
            'plugin'  => $applied ? $plugin->fresh()->makeHidden('step_journal') : null,
            'step_journal' => PluginStepEngine::publicJournal($plugin->fresh()->step_journal),
        ]);
    }

    /** Verifier: reviews the current plugin and returns agents to re-run. */
    public function buildVerify(Request $request, string $uuid): JsonResponse
    {
        $plugin = StudioPlugin::where('uuid', $uuid)->firstOrFail();

        $data = $request->validate([
            'brief'     => 'nullable|string|max:4000',
            'direction' => 'nullable|string|max:2000',
            'skill'     => 'nullable|string|max:60000',
        ]);

        $pluginJson = json_encode($plugin->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        try {
            $result = AIEngine::verifyPlugin($data['brief'] ?? '', $data['direction'] ?? '', $pluginJson, $data['skill'] ?? '');
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage(), 'is_fatal' => self::isFatalAiError($e)], 422);
        }

        return response()->json($result);
    }

    /** Classify whether an AI error is systemic (fatal) — aborts the build loop. */
    private static function isFatalAiError(\Throwable $e): bool
    {
        $msg = $e->getMessage();

        if (str_contains($msg, 'Chave AI não configurada') || str_contains($msg, 'No AI API key configured')) {
            return true;
        }
        if (str_contains($msg, 'cURL error') || str_contains($msg, 'SSL certificate') || str_contains($msg, 'Could not resolve host') || str_contains($msg, 'Connection refused')) {
            return true;
        }
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

    // ──────────────────────────────────────────────
    //  Install directly in a local CMS instance
    // ──────────────────────────────────────────────

    public function installInCms(string $uuid): JsonResponse
    {
        $plugin = StudioPlugin::where('uuid', $uuid)->firstOrFail();

        $cmsUrl    = rtrim((string) StudioSetting::get('cms_url', ''), '/');
        $cmsKeyRaw = StudioSetting::get('cms_api_key', '');
        try { $cmsKey = decrypt($cmsKeyRaw); } catch (\Throwable) { $cmsKey = $cmsKeyRaw; }

        if (empty($cmsUrl) || empty($cmsKey)) {
            return response()->json(['error' => 'CMS URL e API key não configurados em Definições.'], 422);
        }

        $zipPath = $this->buildPluginZip($plugin);

        try {
            $response = Http::withToken($cmsKey)
                ->attach('package', file_get_contents($zipPath), "{$plugin->name}.zip")
                ->post("{$cmsUrl}/api/v1/studio/install-plugin");

            @unlink($zipPath);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => $response->json('message') ?? 'Plugin instalado no CMS.',
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
        $plugin = StudioPlugin::where('uuid', $uuid)->firstOrFail();

        $animusUrl = rtrim((string) StudioSetting::get('animus_api_url', 'https://animus.kwantoe.com'), '/');
        $animusKey = StudioSetting::get('animusflow_api_key', '');

        if (empty($animusKey)) {
            return response()->json(['error' => 'AnimusFlow API key not configured in Settings.'], 422);
        }

        $zipPath = $this->buildPluginZip($plugin);

        try {
            $response = Http::withToken($animusKey)
                ->attach('package', file_get_contents($zipPath), "{$plugin->name}.zip")
                ->post("{$animusUrl}/api/marketplace/publish", [
                    'type'        => 'plugin',
                    'name'        => $plugin->name,
                    'label'       => $plugin->label,
                    'version'     => $plugin->version ?? '1.0.0',
                    'description' => $plugin->description ?? '',
                ]);

            @unlink($zipPath);

            if ($response->successful()) {
                $packageUuid = $response->json('uuid') ?? $response->json('id');
                $plugin->update([
                    'is_published'        => true,
                    'status'              => 'published',
                    'animus_package_uuid' => $packageUuid,
                ]);

                // Auto-snapshot on successful publish
                $this->saveVersionSnapshot($plugin, "Publicado v{$plugin->version}", true, $packageUuid);

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
    //  Plugin Prompt Export (.afprompt)
    // ──────────────────────────────────────────────

    public function exportPrompt(string $uuid)
    {
        $plugin = StudioPlugin::where('uuid', $uuid)->firstOrFail();

        $author    = $plugin->author     ?? StudioSetting::get('studio_author', '');
        $authorUrl = $plugin->author_url ?? StudioSetting::get('studio_author_url', '');
        $minVer    = $plugin->min_animusflow_version ?? StudioSetting::get('export_animusflow_min_ver', '1.0.0');

        // Build the full plugin payload
        $payload = [
            'af_prompt_version' => '1.0',
            'generated_at'      => now()->toIso8601String(),
            'generator'         => 'AnimusFlowStudio',
            'type'              => 'plugin',
            // Studio-native metadata (for Studio re-import)
            'meta' => [
                'uuid'        => $plugin->uuid,
                'name'        => $plugin->name,
                'label'       => $plugin->label,
                'description' => $plugin->description ?? '',
                'version'     => $plugin->version ?? '1.0.0',
                'status'      => $plugin->status,
                'requires'    => $minVer,
                'author'      => $author,
                'author_url'  => $authorUrl,
                'category'    => $plugin->category ?? '',
                'tags'        => $plugin->tags ?? [],
                'license'     => $plugin->license ?? 'MIT',
                'homepage'    => $plugin->homepage_url ?? '',
                'hooks'       => $plugin->hooks ?? [],
            ],
            'code' => [
                'plugin_php'   => $plugin->plugin_php   ?? '',
                'widget_blade' => $plugin->widget_blade ?? '',
                'widget_js'    => $plugin->widget_js    ?? '',
                'custom_css'   => $plugin->custom_css   ?? '',
            ],
            'settings_schema' => $plugin->settings_schema ?? [],
            // AnimusFlow-native format (for direct installation via CMS import)
            'af_install' => [
                'manifest' => [
                    'name'        => $plugin->name,
                    'label'       => $plugin->label,
                    'description' => $plugin->description ?? '',
                    'version'     => $plugin->version ?? '1.0.0',
                    'author'      => $author,
                    'author_url'  => $authorUrl,
                    'category'    => $plugin->category ?? '',
                    'tags'        => $plugin->tags ?? [],
                    'license'     => $plugin->license ?? 'MIT',
                    'requires'    => $minVer,
                    'homepage'    => $plugin->homepage_url ?? '',
                    'hooks'       => $plugin->hooks ?? [],
                    'settings'    => $plugin->settings_schema ?? [],
                ],
                'plugin_php'   => $plugin->plugin_php   ?? '',
                'widget_blade' => $plugin->widget_blade ?? '',
                'widget_js'    => $plugin->widget_js    ?? '',
                'custom_css'   => $plugin->custom_css   ?? '',
            ],
        ];

        $json     = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $checksum = hash('sha256', $json);

        // Human-readable header
        $hooks      = implode(', ', $plugin->hooks ?? []) ?: '—';
        $schemaCount = count($plugin->settings_schema ?? []);
        $hasPhp     = !empty($plugin->plugin_php)   ? '✓' : '✗';
        $hasBlade   = !empty($plugin->widget_blade) ? '✓' : '✗';
        $hasJs      = !empty($plugin->widget_js)    ? '✓' : '✗';
        $hasCss     = !empty($plugin->custom_css)   ? '✓' : '✗';

        $divider = str_repeat('━', 60);

        $prompt = <<<PROMPT
{$divider}
 ANIMUSFLOW PLUGIN PROMPT  v1.0
 Gerado por: AnimusFlowStudio
 Plugin: {$plugin->label}  ({$plugin->name})
 Versão: {$plugin->version}   |   AnimusFlow >= {$minVer}
 Gerado em: {$payload['generated_at']}
{$divider}

Para instalar este plugin no AnimusFlow:
  1. Vai a AnimusFlow Admin → Extensões → Plugins → Importar Prompt
  2. Cola este bloco completo (incluindo as marcações de início e fim do bloco abaixo)
  3. Clica em "Instalar Plugin"

O AnimusFlow irá:
  {$hasPhp}   Instalar a classe Plugin.php
  {$hasBlade} Registar o widget Blade (hook: page.render)
  {$hasJs}    Carregar o JavaScript do widget
  {$hasCss}   Injectar o CSS personalizado
  ✓   Registar os hooks: {$hooks}
  ✓   Configurar {$schemaCount} campo(s) de configuração

{$divider}
[AF:PLUGIN:BEGIN]
{$json}
[AF:PLUGIN:END]
{$divider}
CHECKSUM: sha256:{$checksum}
{$divider}
PROMPT;

        return response($prompt, 200, [
            'Content-Type'        => 'text/plain; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$plugin->name}.afprompt\"",
        ]);
    }

    // ──────────────────────────────────────────────
    //  Preview widget (iframe srcdoc server-side)
    // ──────────────────────────────────────────────

    public function previewWidget(string $uuid)
    {
        $plugin = StudioPlugin::where('uuid', $uuid)->firstOrFail();

        $css    = e($plugin->custom_css   ?? '');
        $widget = $plugin->widget_blade   ?? '';
        $js     = $plugin->widget_js      ?? '';
        $label  = e($plugin->label);

        // Strip Blade directives for raw preview — keep HTML structure intact
        $widgetHtml = preg_replace('/\{\{--.*?--\}\}/s', '', $widget);
        $widgetHtml = preg_replace('/@\w+(\(.*?\))?/', '', $widgetHtml);
        $widgetHtml = preg_replace('/\{!!\s*(.*?)\s*!!\}/s', '$1', $widgetHtml);
        $widgetHtml = preg_replace('/\{\{\s*(.*?)\s*\}\}/s', '<span class="af-preview-var">{{ $1 }}</span>', $widgetHtml);

        $hooks = $plugin->hooks ?? [];

        $html = <<<HTML
<!DOCTYPE html>
<html lang="pt" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Preview — {$label}</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f8f9fa; color: #1a1a2e; line-height: 1.6; }

    /* ── Mock site ── */
    .mock-nav { background:#fff; border-bottom:1px solid #e5e7eb; padding:0 2rem; display:flex; align-items:center; justify-content:space-between; height:60px; position:sticky; top:0; z-index:50; box-shadow:0 1px 3px rgba(0,0,0,.06); }
    .mock-nav-logo { font-weight:700; font-size:1.1rem; color:#4f46e5; display:flex; align-items:center; gap:.5rem; }
    .mock-nav-links { display:flex; gap:1.5rem; }
    .mock-nav-links a { color:#6b7280; text-decoration:none; font-size:.875rem; }
    .mock-nav-btn { background:#4f46e5; color:#fff; border:none; padding:.45rem 1.1rem; border-radius:.5rem; font-size:.875rem; font-weight:600; cursor:pointer; }

    .mock-hero { background:linear-gradient(135deg,#4f46e5 0%,#7c3aed 100%); color:#fff; padding:5rem 2rem; text-align:center; }
    .mock-hero h1 { font-size:2.5rem; font-weight:800; margin-bottom:1rem; }
    .mock-hero p { font-size:1.125rem; opacity:.85; max-width:600px; margin:0 auto 2rem; }
    .mock-hero-cta { display:inline-flex; gap:1rem; }
    .mock-btn-primary { background:#fff; color:#4f46e5; padding:.65rem 1.75rem; border-radius:.5rem; font-weight:700; text-decoration:none; font-size:.95rem; }
    .mock-btn-outline { border:2px solid rgba(255,255,255,.6); color:#fff; padding:.65rem 1.75rem; border-radius:.5rem; font-weight:600; text-decoration:none; font-size:.95rem; }

    .mock-content { max-width:1100px; margin:0 auto; padding:4rem 2rem; display:grid; grid-template-columns:2fr 1fr; gap:3rem; }
    .mock-article h2 { font-size:1.5rem; font-weight:700; margin-bottom:1rem; color:#111827; }
    .mock-article p { color:#4b5563; margin-bottom:1rem; }
    .mock-article-meta { display:flex; gap:1rem; font-size:.8rem; color:#9ca3af; margin-bottom:1.5rem; }
    .mock-sidebar h3 { font-size:1rem; font-weight:700; margin-bottom:.75rem; color:#111827; }
    .mock-sidebar-card { background:#fff; border:1px solid #e5e7eb; border-radius:.75rem; padding:1.25rem; margin-bottom:1rem; }
    .mock-sidebar-card p { font-size:.875rem; color:#4b5563; margin-bottom:.75rem; }

    .mock-footer { background:#1f2937; color:#9ca3af; padding:2rem; text-align:center; font-size:.875rem; }

    /* ── Preview helpers ── */
    .af-preview-banner { background:rgba(79,70,229,.08); border:1px dashed rgba(79,70,229,.3); border-radius:.5rem; padding:.5rem .75rem; font-size:.7rem; color:rgba(79,70,229,.7); font-family:monospace; text-align:center; margin:.5rem 1rem; }
    .af-preview-var { background:rgba(245,158,11,.15); color:#d97706; border-radius:.2rem; padding:0 .25rem; font-size:.85em; font-family:monospace; }

    /* ── Plugin custom CSS ── */
    {$css}
  </style>
</head>
<body>

  <!-- Mock Navigation -->
  <nav class="mock-nav">
    <div class="mock-nav-logo">🌐 MySite</div>
    <div class="mock-nav-links">
      <a href="#">Início</a>
      <a href="#">Blog</a>
      <a href="#">Sobre</a>
      <a href="#">Contacto</a>
    </div>
    <button class="mock-nav-btn">Começar</button>
  </nav>

  <!-- Mock Hero -->
  <section class="mock-hero">
    <h1>Bem-vindo ao MySite</h1>
    <p>Uma plataforma moderna para o teu negócio digital. Rápida, segura e personalizável.</p>
    <div class="mock-hero-cta">
      <a href="#" class="mock-btn-primary">Explorar</a>
      <a href="#" class="mock-btn-outline">Saber mais</a>
    </div>
  </section>

  <!-- Mock Content -->
  <div class="mock-content">
    <main class="mock-article">
      <div class="mock-article-meta">
        <span>📅 1 Jun 2025</span><span>👤 Admin</span><span>🏷️ Tutorial</span>
      </div>
      <h2>Como optimizar o teu site para SEO</h2>
      <p>O Search Engine Optimization (SEO) é fundamental para qualquer negócio online. Com as técnicas correctas, podes aumentar significativamente o tráfego orgânico do teu site e alcançar mais potenciais clientes.</p>
      <p>Existem várias estratégias eficazes: desde a optimização de conteúdo até à melhoria da velocidade de carregamento das páginas. Cada detalhe conta quando se trata de rankings nos motores de busca.</p>
      <p>Neste artigo, exploramos as melhores práticas para 2025 e como implementá-las rapidamente no teu site AnimusFlow.</p>
    </main>
    <aside>
      <div class="mock-sidebar-card">
        <h3>📌 Destaques</h3>
        <p>Descobre os artigos mais lidos desta semana na nossa plataforma.</p>
        <button class="mock-nav-btn" style="width:100%">Ver todos</button>
      </div>
      <div class="mock-sidebar-card">
        <h3>📧 Newsletter</h3>
        <p>Subscreve e recebe conteúdo exclusivo directamente na tua caixa.</p>
        <input type="email" placeholder="email@exemplo.com" style="width:100%;padding:.5rem;border:1px solid #e5e7eb;border-radius:.375rem;margin-bottom:.5rem;font-size:.875rem;">
        <button class="mock-nav-btn" style="width:100%">Subscrever</button>
      </div>
    </aside>
  </div>

  <!-- Mock Footer -->
  <footer class="mock-footer">
    <p>© 2025 MySite · Desenvolvido com AnimusFlow · Todos os direitos reservados</p>
  </footer>

  <!-- ─────────────────────────────────────────────
       PLUGIN WIDGET INJECTED HERE (hook: page.render)
       ───────────────────────────────────────────── -->
  <div class="af-preview-banner">⬇ Plugin «{$label}» injectado via hook page.render ⬇</div>

HTML;

        if (!empty($widgetHtml)) {
            $html .= "\n  <!-- Plugin widget HTML -->\n  {$widgetHtml}\n";
        } else {
            $html .= <<<EMPTY
  <div style="margin:1rem;padding:1.5rem;background:#f3f4f6;border:2px dashed #d1d5db;border-radius:.75rem;text-align:center;color:#9ca3af;font-size:.875rem;">
    <div style="font-size:2rem;margin-bottom:.5rem;">🔌</div>
    <strong style="display:block;margin-bottom:.25rem;">Widget ainda vazio</strong>
    Preenche o conteúdo na tab <em>Widget Blade</em> para ver o preview aqui.
  </div>
EMPTY;
        }

        if (!empty($js)) {
            $html .= "\n  <!-- Plugin JavaScript -->\n  <script>\n" . htmlspecialchars($js, ENT_NOQUOTES) . "\n  </script>\n";
        }

        $html .= "\n</body>\n</html>";

        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    // ──────────────────────────────────────────────
    //  Export Documentation (standalone HTML)
    // ──────────────────────────────────────────────

    public function exportDoc(string $uuid)
    {
        $plugin    = StudioPlugin::where('uuid', $uuid)->firstOrFail();
        $author    = $plugin->author     ?? StudioSetting::get('studio_author', '');
        $authorUrl = $plugin->author_url ?? StudioSetting::get('studio_author_url', '');
        $minVer    = $plugin->min_animusflow_version ?? '1.0.0';
        $hooks     = $plugin->hooks ?? [];
        $schema    = $plugin->settings_schema ?? [];
        $tags      = $plugin->tags ?? [];

        $schemaRows = '';
        foreach ($schema as $f) {
            $type    = htmlspecialchars($f['type']        ?? 'text');
            $key     = htmlspecialchars($f['key']         ?? '');
            $lbl     = htmlspecialchars($f['label']       ?? '');
            $default = htmlspecialchars((string)($f['default'] ?? ''));
            $hint    = htmlspecialchars($f['hint']        ?? $f['placeholder'] ?? '');
            $schemaRows .= "<tr><td><code>{$key}</code></td><td>{$lbl}</td><td><span class=\"badge\">{$type}</span></td><td><code>{$default}</code></td><td>{$hint}</td></tr>\n";
        }

        $hooksRows = '';
        $hookMeta  = [
            'page.render'     => ['onPageRender($page): string',  'Retorna HTML injectado antes de </body> em todas as páginas'],
            'content.publish' => ['onContentPublish($page): void','Disparado quando uma página é publicada'],
            'admin.sidebar'   => ['onAdminSidebar(): array',      'Adiciona link ao sidebar do painel de administração'],
        ];
        foreach ($hooks as $h) {
            $sig  = htmlspecialchars($hookMeta[$h][0] ?? $h);
            $desc = htmlspecialchars($hookMeta[$h][1] ?? '');
            $hooksRows .= "<tr><td><code>{$h}</code></td><td><code>{$sig}</code></td><td>{$desc}</td></tr>\n";
        }

        $tagsHtml = implode(' ', array_map(fn($t) => '<span class="badge">' . e($t) . '</span>', $tags));

        $phpCode    = e($plugin->plugin_php   ?? '');
        $widgetCode = e($plugin->widget_blade ?? '');
        $jsCode     = e($plugin->widget_js    ?? '');
        $cssCode    = e($plugin->custom_css   ?? '');
        $readmeHtml = nl2br(e($plugin->readme ?? ''));

        $generatedAt = now()->format('d/m/Y H:i');
        $authorLink  = $authorUrl ? "<a href=\"{$authorUrl}\" target=\"_blank\">" . e($author) . "</a>" : e($author);

        $doc = <<<HTML
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Documentação — {$plugin->label}</title>
  <style>
    :root {
      --primary: #4f46e5; --primary-light: #eef2ff; --success: #10b981;
      --text: #111827; --muted: #6b7280; --border: #e5e7eb; --bg: #f9fafb;
      --code-bg: #1e1e2e; --code-fg: #cdd6f4;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: var(--bg); color: var(--text); line-height: 1.7; }

    /* Layout */
    .wrap { max-width: 960px; margin: 0 auto; padding: 2.5rem 1.5rem 4rem; }

    /* Header */
    .doc-header { background: linear-gradient(135deg, var(--primary) 0%, #7c3aed 100%); color: #fff; border-radius: 1.25rem; padding: 2.5rem; margin-bottom: 2.5rem; }
    .doc-header h1 { font-size: 2rem; font-weight: 800; margin-bottom: .5rem; }
    .doc-header .sub { opacity: .8; font-size: 1rem; margin-bottom: 1.25rem; }
    .doc-header .meta-row { display: flex; flex-wrap: wrap; gap: .75rem; font-size: .85rem; }
    .meta-item { background: rgba(255,255,255,.15); border-radius: .375rem; padding: .25rem .75rem; display:flex; align-items:center; gap:.35rem; }

    /* Sections */
    .section { background: #fff; border: 1px solid var(--border); border-radius: 1rem; padding: 1.75rem; margin-bottom: 1.5rem; }
    .section h2 { font-size: 1.15rem; font-weight: 700; color: var(--text); margin-bottom: 1.25rem; display:flex; align-items:center; gap:.5rem; border-bottom: 2px solid var(--primary-light); padding-bottom:.75rem; }
    .section h3 { font-size: .95rem; font-weight: 600; margin: 1.25rem 0 .5rem; color: var(--text); }
    .section p { color: #374151; margin-bottom: .75rem; }
    .section ul, .section ol { padding-left: 1.5rem; margin-bottom: .75rem; }
    .section li { margin-bottom: .3rem; color: #374151; }

    /* Badges */
    .badge { display:inline-block; background: var(--primary-light); color: var(--primary); font-size: .75rem; font-weight: 600; padding: .2rem .6rem; border-radius: .375rem; }
    .badge.green { background: #d1fae5; color: #065f46; }
    .badge.orange { background: #fef3c7; color: #92400e; }

    /* Tables */
    table { width:100%; border-collapse:collapse; font-size:.875rem; margin: .75rem 0; }
    th { background: var(--bg); font-weight:600; text-align:left; padding:.6rem .9rem; border:1px solid var(--border); color: var(--muted); text-transform:uppercase; font-size:.75rem; letter-spacing:.05em; }
    td { padding:.6rem .9rem; border:1px solid var(--border); vertical-align:top; }
    tr:nth-child(even) td { background: #fafafa; }
    td code { background: var(--bg); border:1px solid var(--border); padding:.15rem .4rem; border-radius:.25rem; font-size:.8rem; font-family:monospace; color: var(--primary); }

    /* Code blocks */
    .code-block { position:relative; background: var(--code-bg); border-radius: .75rem; overflow:hidden; margin: .75rem 0; }
    .code-block-header { background: rgba(255,255,255,.07); padding: .5rem 1rem; font-size:.75rem; color: rgba(255,255,255,.5); display:flex; align-items:center; justify-content:space-between; font-family:monospace; }
    .code-block pre { padding: 1rem 1.25rem; overflow-x:auto; color: var(--code-fg); font-size:.8rem; font-family:'JetBrains Mono','Fira Code',monospace; line-height:1.6; white-space:pre-wrap; word-break:break-all; }
    .code-block.empty { background: var(--bg); border: 2px dashed var(--border); }
    .code-block.empty pre { color: var(--muted); font-style:italic; text-align:center; padding: 2rem; }

    /* Steps */
    .steps { counter-reset: step; list-style:none; padding:0; }
    .steps li { counter-increment: step; display:flex; gap:1rem; margin-bottom:1rem; padding:1rem 1.25rem; background: var(--bg); border-radius:.75rem; border:1px solid var(--border); align-items:flex-start; }
    .steps li::before { content: counter(step); min-width:2rem; height:2rem; background: var(--primary); color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:.85rem; flex-shrink:0; margin-top:-.1rem; }
    .steps li .step-content strong { display:block; font-size:.9rem; margin-bottom:.2rem; }
    .steps li .step-content p { font-size:.875rem; color: var(--muted); margin:0; }

    /* Footer */
    .doc-footer { text-align:center; padding:2rem 0 0; color: var(--muted); font-size:.8rem; border-top:1px solid var(--border); margin-top:2rem; }
    .doc-footer strong { color: var(--primary); }

    @media print { body { background:#fff; } .section { box-shadow:none; } }
    @media (max-width:640px) { .doc-header { padding:1.5rem; } .doc-header h1 { font-size:1.5rem; } }
  </style>
</head>
<body>
<div class="wrap">

  <!-- Header -->
  <div class="doc-header">
    <div style="font-size:2.5rem;margin-bottom:.75rem;">🔌</div>
    <h1>{$plugin->label}</h1>
    <p class="sub">{$plugin->description}</p>
    <div class="meta-row">
      <div class="meta-item">📦 v{$plugin->version}</div>
      <div class="meta-item">⚖️ {$plugin->license}</div>
      <div class="meta-item">🗂️ {$plugin->category}</div>
      <div class="meta-item">🔧 AnimusFlow &geq; {$minVer}</div>
      {$tagsHtml}
    </div>
  </div>

  <!-- Description -->
  <div class="section">
    <h2>📝 Descrição</h2>
    <p>{$plugin->description}</p>
    <p><strong>Slug:</strong> <code>{$plugin->name}</code> &nbsp;|&nbsp; <strong>Autor:</strong> {$authorLink}</p>
  </div>

  <!-- Installation -->
  <div class="section">
    <h2>🚀 Instalação</h2>
    <h3>Método 1 — Upload ZIP (recomendado)</h3>
    <ol class="steps">
      <li><div class="step-content"><strong>Descarrega o ZIP</strong><p>Vai ao AnimusFlowStudio → Plugin → Exportar → Descarregar ZIP</p></div></li>
      <li><div class="step-content"><strong>Acede ao painel AnimusFlow</strong><p>Admin → Extensões → Plugins → Carregar Plugin</p></div></li>
      <li><div class="step-content"><strong>Faz upload do ficheiro</strong><p>Selecciona o ficheiro <code>{$plugin->name}.zip</code></p></div></li>
      <li><div class="step-content"><strong>Activa o plugin</strong><p>Clica em "Activar" na lista de plugins</p></div></li>
      <li><div class="step-content"><strong>Configura</strong><p>Vai a Extensões → {$plugin->label} → Definições e configura conforme necessário</p></div></li>
    </ol>
    <h3>Método 2 — Instalar via Studio</h3>
    <ol class="steps">
      <li><div class="step-content"><strong>Configura a ligação ao CMS</strong><p>Studio → Definições → CMS URL e API Key</p></div></li>
      <li><div class="step-content"><strong>Clica em "Instalar no CMS"</strong><p>No editor do plugin, botão ⚡ no topo</p></div></li>
    </ol>
  </div>

  <!-- Hooks -->
  <div class="section">
    <h2>🪝 Hooks Activos</h2>
    <p>Este plugin responde aos seguintes eventos do AnimusFlow CMS:</p>
    <table>
      <thead><tr><th>Hook</th><th>Método</th><th>Descrição</th></tr></thead>
      <tbody>{$hooksRows}</tbody>
    </table>
  </div>

  <!-- Settings -->
  <div class="section">
    <h2>⚙️ Campos de Configuração</h2>
HTML;

        if (empty($schema)) {
            $doc .= '<p class="text-muted" style="color:#9ca3af;font-style:italic">Este plugin não tem campos de configuração configuráveis.</p>';
        } else {
            $doc .= <<<TABLE
    <table>
      <thead><tr><th>Key</th><th>Label</th><th>Tipo</th><th>Padrão</th><th>Descrição</th></tr></thead>
      <tbody>{$schemaRows}</tbody>
    </table>
    <p style="font-size:.8rem;color:#9ca3af;margin-top:.5rem">Acede a estes valores no PHP com <code>Setting::get('{$plugin->name}.key')</code></p>
TABLE;
        }

        $doc .= <<<HTML

  </div>

  <!-- Plugin.php -->
  <div class="section">
    <h2>🐘 Plugin.php</h2>
    <p>Classe principal do plugin. Instalada em <code>plugins/{$plugin->name}/Plugin.php</code>.</p>
HTML;

        if (!empty($phpCode)) {
            $doc .= "<div class=\"code-block\"><div class=\"code-block-header\"><span>PHP</span><span>Plugin.php</span></div><pre>{$phpCode}</pre></div>";
        } else {
            $doc .= "<div class=\"code-block empty\"><pre>// Plugin.php ainda não foi definido</pre></div>";
        }

        $doc .= <<<HTML
  </div>

  <!-- Widget -->
  <div class="section">
    <h2>🖼️ Widget Blade (page.render)</h2>
    <p>Template HTML injectado antes de <code>&lt;/body&gt;</code> em todas as páginas. Guardado em <code>plugins/{$plugin->name}/views/widget.blade.php</code>.</p>
HTML;

        if (!empty($widgetCode)) {
            $doc .= "<div class=\"code-block\"><div class=\"code-block-header\"><span>HTML / Blade</span><span>views/widget.blade.php</span></div><pre>{$widgetCode}</pre></div>";
        } else {
            $doc .= "<div class=\"code-block empty\"><pre>// Widget ainda não foi definido</pre></div>";
        }

        $doc .= <<<HTML
  </div>

  <!-- JavaScript -->
  <div class="section">
    <h2>⚡ JavaScript</h2>
    <p>Script carregado automaticamente nas páginas onde o plugin está activo. Guardado em <code>plugins/{$plugin->name}/assets/widget.js</code>.</p>
HTML;

        if (!empty($jsCode)) {
            $doc .= "<div class=\"code-block\"><div class=\"code-block-header\"><span>JavaScript</span><span>assets/widget.js</span></div><pre>{$jsCode}</pre></div>";
        } else {
            $doc .= "<div class=\"code-block empty\"><pre>// JavaScript ainda não foi definido</pre></div>";
        }

        $doc .= <<<HTML
  </div>

  <!-- CSS -->
  <div class="section">
    <h2>🎨 CSS Personalizado</h2>
    <p>Estilos do plugin injectados globalmente. Guardado em <code>plugins/{$plugin->name}/assets/plugin.css</code>.</p>
HTML;

        if (!empty($cssCode)) {
            $doc .= "<div class=\"code-block\"><div class=\"code-block-header\"><span>CSS</span><span>assets/plugin.css</span></div><pre>{$cssCode}</pre></div>";
        } else {
            $doc .= "<div class=\"code-block empty\"><pre>/* CSS ainda não foi definido */</pre></div>";
        }

        $doc .= <<<HTML
  </div>

  <!-- ZIP Structure -->
  <div class="section">
    <h2>📁 Estrutura do ZIP</h2>
    <div class="code-block"><div class="code-block-header"><span>Directórios</span><span>{$plugin->name}.zip</span></div>
<pre>{$plugin->name}/
├── animusflow-plugin.json   ← Manifesto (nome, versão, hooks, settings)
├── Plugin.php               ← Classe principal do plugin
├── views/
│   └── widget.blade.php     ← Template HTML do widget
├── assets/
│   ├── widget.js            ← JavaScript do widget
│   └── plugin.css           ← Estilos do plugin
└── README.md                ← Esta documentação</pre>
    </div>
  </div>

  <!-- Footer -->
  <div class="doc-footer">
    <p>Documentação gerada em <strong>{$generatedAt}</strong> por <strong>AnimusFlowStudio</strong></p>
    <p style="margin-top:.35rem">Plugin <strong>{$plugin->name}</strong> v{$plugin->version} · {$plugin->license}</p>
  </div>

</div>
</body>
</html>
HTML;

        return response($doc, 200, [
            'Content-Type'        => 'text/html; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$plugin->name}-docs.html\"",
        ]);
    }

    // ──────────────────────────────────────────────
    //  AI Documentation Generator
    // ──────────────────────────────────────────────

    public function generateDocs(Request $request, string $uuid): JsonResponse
    {
        $plugin = StudioPlugin::where('uuid', $uuid)->firstOrFail();

        try {
            $readme = AIEngine::generatePluginDocs([
                'name'            => $plugin->name,
                'label'           => $plugin->label,
                'description'     => $plugin->description ?? '',
                'version'         => $plugin->version ?? '1.0.0',
                'author'          => $plugin->author ?? StudioSetting::get('studio_author', ''),
                'author_url'      => $plugin->author_url ?? StudioSetting::get('studio_author_url', ''),
                'category'        => $plugin->category ?? '',
                'license'         => $plugin->license ?? 'MIT',
                'requires'        => $plugin->min_animusflow_version ?? '1.0.0',
                'hooks'           => $plugin->hooks ?? [],
                'settings_schema' => $plugin->settings_schema ?? [],
                'plugin_php'      => $plugin->plugin_php ?? '',
                'widget_blade'    => $plugin->widget_blade ?? '',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $plugin->update(['readme' => $readme]);

        return response()->json(['readme' => $readme, 'success' => true]);
    }

    // ──────────────────────────────────────────────
    //  Export ZIP
    // ──────────────────────────────────────────────

    public function export(string $uuid)
    {
        $plugin = StudioPlugin::where('uuid', $uuid)->firstOrFail();
        $zip    = $this->buildPluginZip($plugin);

        return response()->streamDownload(
            fn () => print(file_get_contents($zip)),
            "{$plugin->name}.zip",
            ['Content-Type' => 'application/zip']
        );
    }

    // ──────────────────────────────────────────────
    //  Versioning
    // ──────────────────────────────────────────────

    /** List all versions for a plugin. */
    public function versions(string $uuid): JsonResponse
    {
        $plugin   = StudioPlugin::where('uuid', $uuid)->firstOrFail();
        $versions = StudioPluginVersion::where('studio_plugin_id', $plugin->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($v) => [
                'id'           => $v->id,
                'version'      => $v->version,
                'label'        => $v->label,
                'changelog'    => $v->changelog,
                'is_published' => $v->is_published,
                'published_uuid' => $v->published_uuid,
                'created_at'   => $v->created_at->toIso8601String(),
                'created_at_human' => $v->created_at->diffForHumans(),
                // Lightweight summary — no heavy code fields
                'summary' => [
                    'hooks'   => $v->snapshot['hooks']   ?? [],
                    'has_php' => !empty($v->snapshot['plugin_php']),
                    'has_widget' => !empty($v->snapshot['widget_blade']),
                    'has_js'  => !empty($v->snapshot['widget_js']),
                    'has_css' => !empty($v->snapshot['custom_css']),
                    'fields'  => count($v->snapshot['settings_schema'] ?? []),
                ],
            ]);

        return response()->json(['versions' => $versions]);
    }

    /** Get full snapshot of a specific version (for restore/diff). */
    public function versionSnapshot(string $uuid, int $versionId): JsonResponse
    {
        $plugin  = StudioPlugin::where('uuid', $uuid)->firstOrFail();
        $version = StudioPluginVersion::where('studio_plugin_id', $plugin->id)
            ->where('id', $versionId)
            ->firstOrFail();

        return response()->json([
            'version'  => $version->version,
            'label'    => $version->label,
            'changelog'=> $version->changelog,
            'snapshot' => $version->snapshot,
            'created_at_human' => $version->created_at->diffForHumans(),
        ]);
    }

    /** Save a manual version snapshot. */
    public function saveVersion(Request $request, string $uuid): JsonResponse
    {
        $plugin = StudioPlugin::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'version'   => ['required', 'string', 'regex:/^\d+\.\d+\.\d+([.-]\S+)?$/', 'max:30'],
            'changelog' => ['nullable', 'string', 'max:2000'],
        ]);

        $ver = $request->input('version');

        // Check duplicate
        $exists = StudioPluginVersion::where('studio_plugin_id', $plugin->id)
            ->where('version', $ver)->exists();
        if ($exists) {
            return response()->json(['error' => "A versão {$ver} já existe para este plugin."], 422);
        }

        $version = StudioPluginVersion::create([
            'studio_plugin_id' => $plugin->id,
            'version'          => $ver,
            'label'            => $plugin->label,
            'changelog'        => $request->input('changelog', ''),
            'snapshot'         => StudioPluginVersion::snapshotFrom($plugin),
            'is_published'     => false,
            'created_by'       => auth()->user()?->email ?? 'studio',
        ]);

        // Also update the plugin's own version field
        $plugin->update(['version' => $ver]);

        return response()->json([
            'success' => true,
            'version' => [
                'id'               => $version->id,
                'version'          => $version->version,
                'label'            => $version->label,
                'changelog'        => $version->changelog,
                'is_published'     => $version->is_published,
                'created_at_human' => $version->created_at->diffForHumans(),
                'summary'          => [
                    'hooks'      => $version->snapshot['hooks'] ?? [],
                    'has_php'    => !empty($version->snapshot['plugin_php']),
                    'has_widget' => !empty($version->snapshot['widget_blade']),
                    'has_js'     => !empty($version->snapshot['widget_js']),
                    'has_css'    => !empty($version->snapshot['custom_css']),
                    'fields'     => count($version->snapshot['settings_schema'] ?? []),
                ],
            ],
        ]);
    }

    /** Restore plugin to a previous version snapshot. */
    public function restoreVersion(string $uuid, int $versionId): JsonResponse
    {
        $plugin  = StudioPlugin::where('uuid', $uuid)->firstOrFail();
        $version = StudioPluginVersion::where('studio_plugin_id', $plugin->id)
            ->where('id', $versionId)
            ->firstOrFail();

        $snap = $version->snapshot;

        // Apply snapshot fields to current plugin (preserve uuid, name, id)
        $updateFields = array_intersect_key($snap, array_flip(StudioPluginVersion::$snapshotFields));
        unset($updateFields['name']); // never overwrite the slug
        $plugin->update($updateFields);

        // Reload fresh
        $plugin->refresh();

        return response()->json([
            'success' => true,
            'message' => "Plugin restaurado para v{$version->version}.",
            'plugin'  => $plugin->makeHidden('step_journal'),
            'step_journal' => PluginStepEngine::publicJournal($plugin->step_journal),
        ]);
    }

    /** Compare two version snapshots — return field-by-field diff. */
    public function compareVersions(Request $request, string $uuid): JsonResponse
    {
        $plugin = StudioPlugin::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'version_a' => ['required', 'integer'],
            'version_b' => ['required', 'integer'],
        ]);

        $vA = StudioPluginVersion::where('studio_plugin_id', $plugin->id)
            ->where('id', $request->integer('version_a'))->firstOrFail();
        $vB = StudioPluginVersion::where('studio_plugin_id', $plugin->id)
            ->where('id', $request->integer('version_b'))->firstOrFail();

        $snapA = $vA->snapshot;
        $snapB = $vB->snapshot;

        $codeFields = ['plugin_php', 'widget_blade', 'widget_js', 'custom_css', 'readme'];
        $metaFields = ['label', 'description', 'version', 'author', 'category', 'license',
                       'status', 'hooks', 'tags', 'settings_schema'];

        $diff = [];
        foreach (array_merge($metaFields, $codeFields) as $field) {
            $a = $snapA[$field] ?? null;
            $b = $snapB[$field] ?? null;

            // Normalise arrays to JSON for comparison
            if (is_array($a)) $a = json_encode($a, JSON_UNESCAPED_UNICODE);
            if (is_array($b)) $b = json_encode($b, JSON_UNESCAPED_UNICODE);

            if ($a !== $b) {
                $diff[] = [
                    'field'   => $field,
                    'is_code' => in_array($field, $codeFields),
                    'a'       => $a,
                    'b'       => $b,
                    'a_lines' => $a ? substr_count((string)$a, "\n") + 1 : 0,
                    'b_lines' => $b ? substr_count((string)$b, "\n") + 1 : 0,
                ];
            }
        }

        return response()->json([
            'version_a' => ['id' => $vA->id, 'version' => $vA->version, 'created_at_human' => $vA->created_at->diffForHumans()],
            'version_b' => ['id' => $vB->id, 'version' => $vB->version, 'created_at_human' => $vB->created_at->diffForHumans()],
            'diff'      => $diff,
            'changed'   => count($diff),
            'unchanged' => count(StudioPluginVersion::$snapshotFields) - count($diff),
        ]);
    }

    /** Internal: save a snapshot (called automatically on publish). */
    private function saveVersionSnapshot(
        StudioPlugin $plugin,
        string $changelog = '',
        bool $isPublished = false,
        ?string $publishedUuid = null
    ): void {
        $ver = $plugin->version ?? '1.0.0';

        // Don't duplicate — update changelog/published flag if version already exists
        $existing = StudioPluginVersion::where('studio_plugin_id', $plugin->id)
            ->where('version', $ver)->first();

        if ($existing) {
            $existing->update([
                'snapshot'       => StudioPluginVersion::snapshotFrom($plugin),
                'changelog'      => $changelog ?: $existing->changelog,
                'is_published'   => $isPublished || $existing->is_published,
                'published_uuid' => $publishedUuid ?? $existing->published_uuid,
            ]);
        } else {
            StudioPluginVersion::create([
                'studio_plugin_id' => $plugin->id,
                'version'          => $ver,
                'label'            => $plugin->label,
                'changelog'        => $changelog,
                'snapshot'         => StudioPluginVersion::snapshotFrom($plugin),
                'is_published'     => $isPublished,
                'published_uuid'   => $publishedUuid,
                'created_by'       => auth()->user()?->email ?? 'studio',
            ]);
        }
    }

    public function recipes(string $uuid): JsonResponse
    {
        $recipes = \App\Models\StudioAiRecipe::where('recipe_type', 'plugin')
            ->select(['id', 'name', 'description', 'prompt_pattern', 'placeholder_types'])
            ->get();

        return response()->json(['recipes' => $recipes]);
    }

    // ──────────────────────────────────────────────
    //  ZIP builder
    // ──────────────────────────────────────────────

    private function buildPluginZip(StudioPlugin $plugin): string
    {
        $tmpDir    = storage_path("app/export-plugin-{$plugin->uuid}");
        $pluginDir = "{$tmpDir}/{$plugin->name}";
        File::ensureDirectoryExists($pluginDir);

        // animusflow-plugin.json
        file_put_contents("{$pluginDir}/animusflow-plugin.json", json_encode([
            'name'        => $plugin->name,
            'label'       => $plugin->label,
            'description' => $plugin->description ?? '',
            'version'     => $plugin->version ?? '1.0.0',
            'author'      => $plugin->author ?? StudioSetting::get('studio_author', ''),
            'author_url'  => $plugin->author_url ?? StudioSetting::get('studio_author_url', ''),
            'category'    => $plugin->category ?? '',
            'tags'        => $plugin->tags ?? [],
            'license'     => $plugin->license ?? 'MIT',
            'requires'    => $plugin->min_animusflow_version ?? '1.0.0',
            'homepage'    => $plugin->homepage_url ?? '',
            'hooks'       => $plugin->hooks ?? [],
            'settings'    => $plugin->settings_schema ?? [],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Plugin.php
        if (!empty($plugin->plugin_php)) {
            file_put_contents("{$pluginDir}/Plugin.php", $plugin->plugin_php);
        } else {
            $class = str_replace(['-', ' ', '_'], '', ucwords(str_replace(['-', '_'], ' ', $plugin->name)));
            file_put_contents(
                "{$pluginDir}/Plugin.php",
                "<?php\n\ndeclare(strict_types=1);\n\nclass {$class}Plugin\n{\n    public function register(): void {}\n}\n"
            );
        }

        if (!empty($plugin->widget_blade)) {
            File::ensureDirectoryExists("{$pluginDir}/views");
            file_put_contents("{$pluginDir}/views/widget.blade.php", $plugin->widget_blade);
        }

        if (!empty($plugin->widget_js)) {
            File::ensureDirectoryExists("{$pluginDir}/assets");
            file_put_contents("{$pluginDir}/assets/widget.js", $plugin->widget_js);
        }

        if (!empty($plugin->custom_css)) {
            File::ensureDirectoryExists("{$pluginDir}/assets");
            file_put_contents("{$pluginDir}/assets/plugin.css", $plugin->custom_css);
        }

        // DOCS.html — inject the same HTML doc into the ZIP
        ob_start();
        $docResponse = $this->exportDoc($plugin->uuid);
        ob_end_clean();
        $docHtml = $docResponse->getContent();
        file_put_contents("{$pluginDir}/DOCS.html", $docHtml);

        // README.md
        $readmeContent = $plugin->readme;
        if (empty($readmeContent)) {
            $author    = $plugin->author    ?? StudioSetting::get('studio_author', '');
            $authorUrl = $plugin->author_url ?? StudioSetting::get('studio_author_url', '');
            $readmeContent = "# {$plugin->label}\n\n"
                . ($plugin->description ? "{$plugin->description}\n\n" : '')
                . "**Version:** {$plugin->version}\n"
                . "**License:** {$plugin->license}\n"
                . ($author ? "**Author:** {$author}" . ($authorUrl ? " <{$authorUrl}>" : '') . "\n" : '')
                . "\n## Installation\n\nUpload this ZIP via AnimusFlow Admin → Extensions → Plugins → Upload ZIP.\n"
                . "\n## Hooks\n\n"
                . implode("\n", array_map(fn ($h) => "- `{$h}`", $plugin->hooks ?? [])) . "\n";
        }
        file_put_contents("{$pluginDir}/README.md", $readmeContent);

        // Build ZIP — normalise paths to forward slashes (Windows fix)
        $zipPath    = storage_path("app/{$plugin->name}.zip");
        $zip        = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
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

    /** Devolve o espelho do processo para plugins (estado + histórico por passo). */
    public function journal(string $uuid): JsonResponse
    {
        $plugin = StudioPlugin::where('uuid', $uuid)->firstOrFail();

        return response()->json([
            'journal' => PluginStepEngine::publicJournal($plugin->step_journal),
            'labels'  => PluginStepEngine::STEP_LABELS,
        ]);
    }

    /** Classifica um pedido (sem aplicar) → a que passo de plugin pertence. */
    public function classifyRequest(Request $request, string $uuid): JsonResponse
    {
        StudioPlugin::where('uuid', $uuid)->firstOrFail();
        $data = $request->validate(['message' => 'required|string|max:4000']);

        $cls = PluginStepEngine::classify($data['message'], [], allowAi: true);
        return response()->json([
            'step'        => $cls['step'],
            'step_label'  => $cls['step'] ? PluginStepEngine::label($cls['step']) : null,
            'step_method' => $cls['method'],
        ]);
    }

    /** Reverte a última alteração registada de um passo do plugin. */
    public function revertStep(Request $request, string $uuid): JsonResponse
    {
        $plugin = StudioPlugin::where('uuid', $uuid)->firstOrFail();
        $data  = $request->validate([
            'step' => ['required', 'string', Rule::in(PluginStepEngine::steps())],
        ]);

        $reverted = PluginStepEngine::revertStep($plugin, $data['step']);

        return response()->json([
            'reverted' => $reverted,
            'plugin'   => $reverted ? $plugin->fresh()->makeHidden('step_journal') : null,
            'journal'  => PluginStepEngine::publicJournal($plugin->fresh()->step_journal),
        ]);
    }
}
