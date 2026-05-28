<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StudioPlugin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PluginController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Plugins/Index', [
            'plugins' => StudioPlugin::latest()->get(['id', 'uuid', 'name', 'label', 'version', 'status', 'is_published', 'hooks', 'created_at']),
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
            'label'           => 'sometimes|string|max:200',
            'description'     => 'nullable|string|max:1000',
            'version'         => 'nullable|string|max:20',
            'hooks'           => 'nullable|array',
            'settings_schema' => 'nullable|array',
            'plugin_php'      => 'nullable|string',
            'widget_blade'    => 'nullable|string',
            'widget_js'       => 'nullable|string',
            'status'          => 'nullable|in:draft,ready,published',
        ]);

        $plugin->update($data);

        return back()->with('success', 'Plugin saved.');
    }

    public function destroy(string $uuid): RedirectResponse
    {
        StudioPlugin::where('uuid', $uuid)->firstOrFail()->delete();

        return redirect()->route('plugins.index')->with('success', 'Plugin deleted.');
    }

    public function export(string $uuid)
    {
        $plugin = StudioPlugin::where('uuid', $uuid)->firstOrFail();

        $zip = $this->buildPluginZip($plugin);

        return response()->streamDownload(
            fn () => print(file_get_contents($zip)),
            "{$plugin->name}.zip",
            ['Content-Type' => 'application/zip']
        );
    }

    private function buildPluginZip(StudioPlugin $plugin): string
    {
        $tmpDir = storage_path("app/export-plugin-{$plugin->uuid}");
        $pluginDir = "{$tmpDir}/{$plugin->name}";
        \Illuminate\Support\Facades\File::ensureDirectoryExists($pluginDir);

        // animusflow-plugin.json manifest
        file_put_contents("{$pluginDir}/animusflow-plugin.json", json_encode([
            'name'        => $plugin->name,
            'label'       => $plugin->label,
            'description' => $plugin->description ?? '',
            'version'     => $plugin->version ?? '1.0.0',
            'hooks'       => $plugin->hooks ?? [],
            'settings'    => $plugin->settings_schema ?? [],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Plugin.php
        if (!empty($plugin->plugin_php)) {
            file_put_contents("{$pluginDir}/Plugin.php", $plugin->plugin_php);
        } else {
            $class = str_replace(['-', ' '], '', ucwords(str_replace('-', ' ', $plugin->name)));
            file_put_contents("{$pluginDir}/Plugin.php", "<?php\n\ndeclare(strict_types=1);\n\nclass {$class}Plugin\n{\n    public function register(): void {}\n}\n");
        }

        // Optional files
        if (!empty($plugin->widget_blade)) {
            \Illuminate\Support\Facades\File::ensureDirectoryExists("{$pluginDir}/views");
            file_put_contents("{$pluginDir}/views/widget.blade.php", $plugin->widget_blade);
        }
        if (!empty($plugin->widget_js)) {
            \Illuminate\Support\Facades\File::ensureDirectoryExists("{$pluginDir}/assets");
            file_put_contents("{$pluginDir}/assets/widget.js", $plugin->widget_js);
        }

        // Build ZIP
        $zipPath = storage_path("app/{$plugin->name}.zip");
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
}
