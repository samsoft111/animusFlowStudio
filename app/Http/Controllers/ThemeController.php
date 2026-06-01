<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StudioSetting;
use App\Models\StudioTheme;
use App\Services\AIEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
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
            'name'        => 'required|string|regex:/^[a-z0-9][a-z0-9\-_]{0,49}$/|unique:studio_themes,name',
            'label'       => 'required|string|max:200',
            'description' => 'nullable|string|max:1000',
            'version'     => 'nullable|string|max:20',
        ]);

        $theme = StudioTheme::create($data);

        return redirect()->route('themes.edit', $theme->uuid)->with('success', 'Theme created.');
    }

    public function edit(string $uuid): Response
    {
        $theme = StudioTheme::where('uuid', $uuid)->firstOrFail();

        return Inertia::render('Themes/Edit', ['theme' => $theme]);
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
        ]);

        $theme->update($data);

        return back()->with('success', 'Theme saved.');
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
        ];

        $request->validate([
            'file' => 'required|file|max:51200', // 50 MB (vídeos)
            'slot' => 'required|in:' . implode(',', $allowedSlots),
        ]);

        $slot = $request->input('slot');
        $dir  = "themes/{$theme->uuid}";
        $path = $request->file('file')->store($dir, 'public');
        $url  = '/storage/' . $path;

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
            // Delete file from disk
            $localPath = public_path(str_replace('/storage/', 'storage/', $assets[$slot]));
            if (file_exists($localPath)) {
                @unlink($localPath);
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

        try {
            $result = AIEngine::chatTheme($history, $themeJson, $attachments);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        // If AI returned theme updates, apply them with deep-merge for nested fields
        $applied = false;
        if (!empty($result['updates'])) {
            $allowed = [
                'label', 'description', 'version', 'status',
                'colors', 'fonts', 'layout_config', 'capabilities',
                'sections', 'components', 'custom_css', 'custom_js',
                'variants', 'assets',
            ];
            $updates = array_intersect_key($result['updates'], array_flip($allowed));

            // Deep-merge nested array fields so AI only needs to specify changed keys
            foreach (['colors', 'layout_config', 'capabilities', 'fonts', 'assets'] as $field) {
                if (isset($updates[$field]) && is_array($updates[$field])) {
                    $existing = is_array($theme->$field) ? $theme->$field : [];
                    $updates[$field] = array_replace_recursive($existing, $updates[$field]);
                }
            }

            if (!empty($updates)) {
                $theme->update($updates);
                $applied = true;
            }
        }

        return response()->json([
            'reply'   => $result['reply'],
            'updates' => $result['updates'] ?? null,
            'applied' => $applied,
            'theme'   => $applied ? $theme->fresh() : null,
        ]);
    }

    // ──────────────────────────────────────────────
    //  Live Preview (Phase 2)
    // ──────────────────────────────────────────────

    public function preview(string $uuid)
    {
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
        return ['hero', 'features', 'text', 'cta', 'testimonials', 'pricing', 'gallery', 'faq'];
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
        'header_type'      => 'layout_header_bg',
        'nav_position'     => 'layout_header_menu',
        'max_width'        => 'layout_content_max_width',
        'spacing'          => 'layout_content_spacing',
        'show_dark_toggle' => 'layout_header_show_toggle',
        'header_sticky'    => 'layout_header_sticky',
        'header_cta_text'  => 'layout_header_cta_text',
        'header_cta_url'   => 'layout_header_cta_url',
        'footer_copyright' => 'layout_footer_copyright',
        'back_to_top'      => null,   // handled separately via capabilities
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
}
