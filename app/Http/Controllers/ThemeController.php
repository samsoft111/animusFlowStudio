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
    //  Live Preview (Phase 2)
    // ──────────────────────────────────────────────

    public function preview(string $uuid)
    {
        $theme = StudioTheme::where('uuid', $uuid)->firstOrFail();

        return view('preview.theme', compact('theme'));
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
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($tmpDir)) as $file) {
            if (!$file->isDir()) {
                $zip->addFile(
                    $file->getPathname(),
                    str_replace($tmpDir . DIRECTORY_SEPARATOR, '', $file->getPathname())
                );
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

    private function injectColors(string $layout, array $colors): string
    {
        $light = $colors['light'] ?? [];
        $dark  = $colors['dark']  ?? [];

        foreach ($light as $var => $value) {
            $layout = preg_replace(
                '/(' . preg_quote($var, '/') . '\s*:\s*)[^;]+;/',
                '${1}' . $value . ';',
                $layout, 1
            ) ?? $layout;
        }

        if (preg_match('/\[data-theme="dark"\]\s*\{/', $layout, $m, PREG_OFFSET_CAPTURE)) {
            $openBrace   = strpos($layout, '{', $m[0][1]);
            $closeBrace  = strpos($layout, '}', $openBrace + 1);
            $darkContent = substr($layout, $openBrace + 1, $closeBrace - $openBrace - 1);
            foreach ($dark as $var => $value) {
                $darkContent = preg_replace(
                    '/(' . preg_quote($var, '/') . '\s*:\s*)[^;]+;/',
                    '${1}' . $value . ';',
                    $darkContent, 1
                ) ?? $darkContent;
            }
            $layout = substr($layout, 0, $openBrace + 1) . $darkContent . substr($layout, $closeBrace);
        }

        return $layout;
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
