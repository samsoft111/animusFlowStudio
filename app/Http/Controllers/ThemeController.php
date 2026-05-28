<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StudioTheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ThemeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Themes/Index', [
            'themes' => StudioTheme::latest()->get(['id', 'uuid', 'name', 'label', 'version', 'status', 'is_published', 'preview_url', 'created_at']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Themes/Edit', ['theme' => null]);
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
            'label'       => 'sometimes|string|max:200',
            'description' => 'nullable|string|max:1000',
            'version'     => 'nullable|string|max:20',
            'colors'      => 'nullable|array',
            'fonts'       => 'nullable|array',
            'sections'    => 'nullable|array',
            'status'      => 'nullable|in:draft,ready,published',
            'preview_url' => 'nullable|url|max:500',
        ]);

        $theme->update($data);

        return back()->with('success', 'Theme saved.');
    }

    public function destroy(string $uuid): RedirectResponse
    {
        StudioTheme::where('uuid', $uuid)->firstOrFail()->delete();

        return redirect()->route('themes.index')->with('success', 'Theme deleted.');
    }

    public function export(string $uuid)
    {
        $theme = StudioTheme::where('uuid', $uuid)->firstOrFail();

        $zip = $this->buildThemeZip($theme);

        return response()->streamDownload(
            fn () => print(file_get_contents($zip)),
            "{$theme->name}.zip",
            ['Content-Type' => 'application/zip']
        );
    }

    private function buildThemeZip(StudioTheme $theme): string
    {
        $tmpDir  = storage_path("app/export-{$theme->uuid}");
        $themeDir = "{$tmpDir}/{$theme->name}";
        \Illuminate\Support\Facades\File::ensureDirectoryExists($themeDir . '/sections');

        // theme.json manifest
        file_put_contents("{$themeDir}/theme.json", json_encode([
            'name'        => $theme->name,
            'label'       => $theme->label,
            'description' => $theme->description,
            'version'     => $theme->version ?? '1.0.0',
            'blocks'      => $this->allBlockTypes(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Layout from AnimusFlow default + token injection
        $defaultLayout = $this->animusFlowDefaultPath('layout.blade.php');
        if (file_exists($defaultLayout)) {
            $layout = file_get_contents($defaultLayout);
            $layout = $this->injectColors($layout, $theme->colors ?? []);
            file_put_contents("{$themeDir}/layout.blade.php", $layout);
        }

        // page.blade.php
        $defaultPage = $this->animusFlowDefaultPath('page.blade.php');
        if (file_exists($defaultPage)) {
            $page = str_replace('theme.default', "theme.{$theme->name}", file_get_contents($defaultPage));
            file_put_contents("{$themeDir}/page.blade.php", $page);
        }

        // Sections — copy all 61 defaults, then overlay AI-generated ones
        foreach (glob($this->animusFlowDefaultPath('sections') . '/*.blade.php') ?: [] as $f) {
            \Illuminate\Support\Facades\File::copy($f, "{$themeDir}/sections/" . basename($f));
        }
        foreach ($theme->sections ?? [] as $type => $blade) {
            file_put_contents("{$themeDir}/sections/{$type}.blade.php", $blade);
        }

        // Build ZIP
        $zipPath = storage_path("app/{$theme->name}.zip");
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($tmpDir)) as $file) {
            if (!$file->isDir()) {
                $zip->addFile($file->getPathname(), str_replace($tmpDir . DIRECTORY_SEPARATOR, '', $file->getPathname()));
            }
        }
        $zip->close();
        \Illuminate\Support\Facades\File::deleteDirectory($tmpDir);

        return $zipPath;
    }

    private function animusFlowDefaultPath(string $relative): string
    {
        return base_path("../animusFlow/core/resources/views/theme/default/{$relative}");
    }

    private function allBlockTypes(): array
    {
        $themeJson = $this->animusFlowDefaultPath('theme.json');
        if (file_exists($themeJson)) {
            $data = json_decode(file_get_contents($themeJson), true);
            return $data['blocks'] ?? [];
        }
        return ['hero', 'features', 'text', 'cta'];
    }

    private function injectColors(string $layout, array $colors): string
    {
        $light = $colors['light'] ?? [];
        $dark  = $colors['dark'] ?? [];

        foreach ($light as $var => $value) {
            $layout = preg_replace('/(' . preg_quote($var, '/') . '\s*:\s*)[^;]+;/', '${1}' . $value . ';', $layout, 1);
        }

        if (preg_match('/\[data-theme="dark"\]\s*\{/', $layout, $m, PREG_OFFSET_CAPTURE)) {
            $openBrace  = strpos($layout, '{', $m[0][1]);
            $closeBrace = strpos($layout, '}', $openBrace + 1);
            $darkContent = substr($layout, $openBrace + 1, $closeBrace - $openBrace - 1);
            foreach ($dark as $var => $value) {
                $darkContent = preg_replace('/(' . preg_quote($var, '/') . '\s*:\s*)[^;]+;/', '${1}' . $value . ';', $darkContent, 1);
            }
            $layout = substr($layout, 0, $openBrace + 1) . $darkContent . substr($layout, $closeBrace);
        }

        return $layout;
    }
}
