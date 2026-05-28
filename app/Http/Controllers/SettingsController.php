<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StudioSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Settings', [
            'settings' => [
                'studio_name'        => StudioSetting::get('studio_name', 'AnimusFlowStudio'),
                'animusflow_api_key' => StudioSetting::get('animusflow_api_key', ''),
                'animus_api_url'     => StudioSetting::get('animus_api_url', 'https://animus.kwantoe.com'),
                'ai_provider'        => StudioSetting::get('ai_provider', 'claude'),
                'ai_api_key'         => StudioSetting::get('ai_api_key', ''),
                'ai_model'           => StudioSetting::get('ai_model', ''),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'studio_name'        => 'nullable|string|max:100',
            'animusflow_api_key' => 'nullable|string|max:500',
            'animus_api_url'     => 'nullable|url|max:255',
            'ai_provider'        => 'nullable|in:claude,openai,gemini',
            'ai_api_key'         => 'nullable|string|max:500',
            'ai_model'           => 'nullable|string|max:100',
        ]);

        foreach ($data as $key => $value) {
            StudioSetting::set($key, $value ?? '');
        }

        return back()->with('success', 'Settings saved.');
    }
}
