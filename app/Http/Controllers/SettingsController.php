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
    /** Keys that are stored encrypted and never returned raw to the frontend. */
    private const ENCRYPTED_KEYS = ['ai_api_key', 'animusflow_api_key'];

    public function index(): Response
    {
        $s = fn (string $key, mixed $default = '') => StudioSetting::get($key, $default);

        return Inertia::render('Settings', [
            'settings' => [
                // ── Studio ──
                'studio_name'         => $s('studio_name', 'AnimusFlowStudio'),
                'studio_author'       => $s('studio_author'),
                'studio_author_email' => $s('studio_author_email'),
                'studio_author_url'   => $s('studio_author_url'),

                // ── AI ──
                'ai_provider'            => $s('ai_provider', 'claude'),
                'ai_model'               => $s('ai_model'),
                'has_ai_key'             => !empty($s('ai_api_key')),
                'ai_temperature'         => $s('ai_temperature', '0.7'),
                'ai_max_tokens'          => $s('ai_max_tokens', '4096'),
                'ai_custom_instructions' => $s('ai_custom_instructions'),

                // ── Theme Defaults ──
                'theme_default_primary'      => $s('theme_default_primary', '#6366f1'),
                'theme_default_font_heading' => $s('theme_default_font_heading', 'Inter'),
                'theme_default_font_body'    => $s('theme_default_font_body', 'Inter'),
                'theme_default_version'      => $s('theme_default_version', '1.0.0'),
                'theme_default_sections'     => $s('theme_default_sections', 'hero,features,cta,testimonials,pricing'),
                'theme_dark_mode'            => $s('theme_dark_mode', 'always'),
                'theme_border_radius'        => $s('theme_border_radius', 'normal'),
                'theme_animusflow_path'      => $s('theme_animusflow_path', '../animusFlow/core'),

                // ── Plugin Defaults ──
                'plugin_default_version' => $s('plugin_default_version', '1.0.0'),
                'plugin_default_hooks'   => $s('plugin_default_hooks', 'page.render'),
                'plugin_namespace'       => $s('plugin_namespace', ''),

                // ── Marketplace ──
                'animus_api_url'              => $s('animus_api_url', 'https://animus.kwantoe.com'),
                'has_animusflow_api_key'      => !empty($s('animusflow_api_key')),
                'marketplace_publisher_name'  => $s('marketplace_publisher_name'),
                'marketplace_publisher_url'   => $s('marketplace_publisher_url'),
                'marketplace_auto_publish'    => $s('marketplace_auto_publish', '0'),

                // ── Export ──
                'export_minify_html'          => $s('export_minify_html', '0'),
                'export_include_readme'       => $s('export_include_readme', '1'),
                'export_animusflow_min_ver'   => $s('export_animusflow_min_ver', '1.0.0'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            // Studio
            'studio_name'         => 'nullable|string|max:100',
            'studio_author'       => 'nullable|string|max:150',
            'studio_author_email' => 'nullable|email|max:200',
            'studio_author_url'   => 'nullable|url|max:300',

            // AI
            'ai_provider'            => 'nullable|in:claude,openai,gemini',
            'ai_model'               => 'nullable|string|max:100',
            'ai_api_key'             => 'nullable|string|max:1000',
            'ai_temperature'         => 'nullable|numeric|min:0|max:1',
            'ai_max_tokens'          => 'nullable|integer|min:256|max:16000',
            'ai_custom_instructions' => 'nullable|string|max:2000',

            // Theme defaults
            'theme_default_primary'      => 'nullable|string|max:50',
            'theme_default_font_heading' => 'nullable|string|max:100',
            'theme_default_font_body'    => 'nullable|string|max:100',
            'theme_default_version'      => 'nullable|string|max:20',
            'theme_default_sections'     => 'nullable|string|max:500',
            'theme_dark_mode'            => 'nullable|in:always,optional,none',
            'theme_border_radius'        => 'nullable|in:sharp,normal,rounded',
            'theme_animusflow_path'      => 'nullable|string|max:300',

            // Plugin defaults
            'plugin_default_version' => 'nullable|string|max:20',
            'plugin_default_hooks'   => 'nullable|string|max:200',
            'plugin_namespace'       => 'nullable|string|max:150',

            // Marketplace
            'animus_api_url'             => 'nullable|url|max:255',
            'animusflow_api_key'         => 'nullable|string|max:500',
            'marketplace_publisher_name' => 'nullable|string|max:150',
            'marketplace_publisher_url'  => 'nullable|url|max:300',
            'marketplace_auto_publish'   => 'nullable|in:0,1',

            // Export
            'export_minify_html'        => 'nullable|in:0,1',
            'export_include_readme'     => 'nullable|in:0,1',
            'export_animusflow_min_ver' => 'nullable|string|max:20',
        ]);

        $groups = [
            'studio_name'         => 'studio',
            'studio_author'       => 'studio',
            'studio_author_email' => 'studio',
            'studio_author_url'   => 'studio',
            'ai_provider'         => 'ai',
            'ai_model'            => 'ai',
            'ai_temperature'      => 'ai',
            'ai_max_tokens'       => 'ai',
            'ai_custom_instructions' => 'ai',
            'theme_default_primary'      => 'theme',
            'theme_default_font_heading' => 'theme',
            'theme_default_font_body'    => 'theme',
            'theme_default_version'      => 'theme',
            'theme_default_sections'     => 'theme',
            'theme_dark_mode'            => 'theme',
            'theme_border_radius'        => 'theme',
            'theme_animusflow_path'      => 'theme',
            'plugin_default_version' => 'plugin',
            'plugin_default_hooks'   => 'plugin',
            'plugin_namespace'       => 'plugin',
            'animus_api_url'             => 'marketplace',
            'marketplace_publisher_name' => 'marketplace',
            'marketplace_publisher_url'  => 'marketplace',
            'marketplace_auto_publish'   => 'marketplace',
            'export_minify_html'        => 'export',
            'export_include_readme'     => 'export',
            'export_animusflow_min_ver' => 'export',
        ];

        foreach ($data as $key => $value) {
            // Encrypted keys — only update when a new non-empty value is provided
            if ($key === 'ai_api_key') {
                if (!empty($value)) {
                    StudioSetting::set('ai_api_key', encrypt($value), 'ai');
                }
                continue;
            }
            if ($key === 'animusflow_api_key') {
                if (!empty($value)) {
                    StudioSetting::set('animusflow_api_key', encrypt($value), 'marketplace');
                }
                continue;
            }

            StudioSetting::set($key, $value ?? '', $groups[$key] ?? 'general');
        }

        return back()->with('success', 'Settings saved.');
    }
}
