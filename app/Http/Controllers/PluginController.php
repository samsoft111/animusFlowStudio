<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StudioPlugin;
use App\Models\StudioSetting;
use App\Services\AIEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
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

    public function create(): Response
    {
        return Inertia::render('Plugins/Edit', ['plugin' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|regex:/^[a-z0-9][a-z0-9\-_]{0,49}$/|unique:studio_plugins,name',
            'label'       => 'required|string|max:200',
            'description' => 'nullable|string|max:1000',
            'version'     => 'nullable|string|max:20',
        ]);

        $plugin = StudioPlugin::create($data);

        return redirect()->route('plugins.edit', $plugin->uuid)->with('success', 'Plugin created.');
    }

    public function edit(string $uuid): Response
    {
        $plugin = StudioPlugin::where('uuid', $uuid)->firstOrFail();

        return Inertia::render('Plugins/Edit', ['plugin' => $plugin]);
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

        $plugin->update($data);

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

        $plugin->update([
            'plugin_php'      => $result['plugin_php']      ?? $plugin->plugin_php,
            'widget_blade'    => $result['widget_blade']    ?? $plugin->widget_blade,
            'widget_js'       => $result['widget_js']       ?? $plugin->widget_js,
            'settings_schema' => $result['settings_schema'] ?? $plugin->settings_schema,
        ]);

        $fresh = $plugin->fresh();

        return response()->json([
            'success'         => true,
            'plugin_php'      => $fresh->plugin_php,
            'widget_blade'    => $fresh->widget_blade,
            'widget_js'       => $fresh->widget_js,
            'settings_schema' => $fresh->settings_schema,
        ]);
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
        $plugin = StudioPlugin::where('uuid', $uuid)->firstOrFail();
        $zip    = $this->buildPluginZip($plugin);

        return response()->streamDownload(
            fn () => print(file_get_contents($zip)),
            "{$plugin->name}.zip",
            ['Content-Type' => 'application/zip']
        );
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

        // Build ZIP
        $zipPath = storage_path("app/{$plugin->name}.zip");
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
}
