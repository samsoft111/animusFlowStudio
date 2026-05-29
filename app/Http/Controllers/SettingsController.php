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
        $rawKey = StudioSetting::get('ai_api_key', '');

        return Inertia::render('Settings', [
            'settings' => [
                'studio_name'        => StudioSetting::get('studio_name', 'AnimusFlowStudio'),
                'animusflow_api_key' => StudioSetting::get('animusflow_api_key', ''),
                'animus_api_url'     => StudioSetting::get('animus_api_url', 'https://animus.kwantoe.com'),
                'ai_provider'        => StudioSetting::get('ai_provider', 'claude'),
                'ai_model'           => StudioSetting::get('ai_model', ''),
                // Never expose the raw key to the frontend — only presence flag
                'has_ai_key'         => !empty($rawKey),
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
            'ai_model'           => 'nullable|string|max:100',
            'ai_api_key'         => 'nullable|string|max:1000',
        ]);

        foreach ($data as $key => $value) {
            if ($key === 'ai_api_key') {
                // Only update if a new key was provided (non-empty)
                if (!empty($value)) {
                    StudioSetting::set('ai_api_key', encrypt($value), 'ai');
                }
                continue;
            }
            StudioSetting::set($key, $value ?? '', $key === 'animusflow_api_key' ? 'marketplace' : 'general');
        }

        return back()->with('success', 'Settings saved.');
    }
}
