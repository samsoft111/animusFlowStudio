<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\StudioSetting;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AIEngine
{
    // ──────────────────────────────────────────────
    //  Public API
    // ──────────────────────────────────────────────

    /**
     * Generate a full theme design from a text prompt.
     *
     * Returns:
     *   colors.light / colors.dark — CSS variable maps
     *   fonts.heading / fonts.body — font family names
     *   sections — { type => blade_html }
     */
    public static function generateTheme(string $prompt): array
    {
        $systemPrompt = <<<'SYSTEM'
You are an expert CSS and web design AI. You generate AnimusFlow theme designs in JSON format.

The response MUST be valid JSON only — no markdown, no code fences, no explanation.

AnimusFlow uses CSS custom properties (oklch color space preferred). You MUST include all required CSS variables.

Return exactly this structure:
{
  "colors": {
    "light": {
      "--color-primary": "oklch(...)",
      "--color-primary-foreground": "oklch(1 0 0)",
      "--color-background": "oklch(...)",
      "--color-foreground": "oklch(...)",
      "--color-card": "oklch(...)",
      "--color-muted": "oklch(...)",
      "--color-muted-foreground": "oklch(...)",
      "--color-border": "oklch(...)",
      "--color-success": "oklch(0.65 0.20 150)",
      "--color-warning": "oklch(0.75 0.18 80)",
      "--color-destructive": "oklch(0.60 0.22 25)"
    },
    "dark": { ... same keys with darker values ... }
  },
  "fonts": {
    "heading": "font family name",
    "body": "font family name"
  },
  "sections": {
    "hero": "<section class=\"af-hero\" style=\"...\">...</section>",
    "features": "<section class=\"af-features\">...</section>",
    "cta": "<section class=\"af-cta\">...</section>",
    "testimonials": "<section class=\"af-testimonials\">...</section>",
    "pricing": "<section class=\"af-pricing\">...</section>"
  }
}

For sections, use only inline styles and CSS variables (e.g. var(--color-primary)). No external CSS frameworks.
SYSTEM;

        $userPrompt = "Design a theme for: {$prompt}";

        $raw = self::call($systemPrompt, $userPrompt, 4096);

        return self::parseJson($raw, ['colors' => ['light' => [], 'dark' => []], 'fonts' => [], 'sections' => []]);
    }

    /**
     * Generate plugin code from a text prompt.
     *
     * Returns:
     *   plugin_php       — full Plugin.php source
     *   widget_blade     — Blade HTML for page.render hook
     *   widget_js        — JavaScript for the widget
     *   settings_schema  — array of field definitions
     */
    public static function generatePlugin(string $prompt, array $hooks, string $pluginLabel): array
    {
        $hooksStr   = implode(', ', $hooks ?: ['page.render']);
        $className  = self::toClassName($pluginLabel);

        $systemPrompt = <<<SYSTEM
You are an expert PHP and Laravel developer. Generate an AnimusFlow plugin in JSON format.

The response MUST be valid JSON only — no markdown, no code fences, no explanation.

AnimusFlow plugin hooks:
- page.render: onPageRender(\$page): string  — returns HTML injected before </body>
- content.publish: onContentPublish(\$page): void
- admin.sidebar: onAdminSidebar(): array — returns ['label', 'icon', 'url']

Return exactly this structure:
{
  "plugin_php": "<?php\\n\\ndeclare(strict_types=1);\\n\\nclass {$className}Plugin\\n{\\n    ...\\n}\\n",
  "widget_blade": "<div class=\\\"af-widget\\\">...</div>",
  "widget_js": "// JS code",
  "settings_schema": [
    {"key": "example_key", "label": "Example Label", "type": "text", "default": "", "placeholder": "...", "hint": "..."}
  ]
}

Settings schema field types: text, textarea, color, select, toggle.
For select type add: "options": {"value": "Label"}.
For toggle type add: "toggle_label": "Enable feature".

The plugin should implement: {$hooksStr}
SYSTEM;

        $userPrompt = "Create a plugin that: {$prompt}";

        $raw = self::call($systemPrompt, $userPrompt, 4096);

        return self::parseJson($raw, [
            'plugin_php'      => '',
            'widget_blade'    => '',
            'widget_js'       => '',
            'settings_schema' => [],
        ]);
    }

    // ──────────────────────────────────────────────
    //  Internal helpers
    // ──────────────────────────────────────────────

    private static function call(string $system, string $user, int $maxTokens = 2048): string
    {
        $provider    = StudioSetting::get('ai_provider', 'claude');
        $model       = StudioSetting::get('ai_model', '');
        $rawKey      = StudioSetting::get('ai_api_key', '');
        $temperature = (float) StudioSetting::get('ai_temperature', '0.7');
        $maxTok      = (int) StudioSetting::get('ai_max_tokens', (string) $maxTokens);
        $customInstr = StudioSetting::get('ai_custom_instructions', '');

        // Prepend custom instructions to system prompt
        if (!empty($customInstr)) {
            $system = trim($customInstr) . "\n\n" . $system;
        }

        // Decrypt if stored encrypted
        $apiKey = '';
        if (!empty($rawKey)) {
            try {
                $apiKey = decrypt($rawKey);
            } catch (\Throwable) {
                $apiKey = $rawKey; // plain text fallback (legacy)
            }
        }

        if (empty($apiKey)) {
            throw new RuntimeException('No AI API key configured. Go to Settings → AI Provider.');
        }

        return match ($provider) {
            'openai'  => self::callOpenAI($apiKey, $model ?: 'gpt-4o', $system, $user, $maxTok, $temperature),
            'gemini'  => self::callGemini($apiKey, $model ?: 'gemini-1.5-flash', $system, $user, $maxTok),
            default   => self::callClaude($apiKey, $model ?: 'claude-sonnet-4-5', $system, $user, $maxTok),
        };
    }

    private static function callClaude(string $key, string $model, string $system, string $user, int $maxTokens): string
    {
        $response = Http::withHeaders([
            'x-api-key'         => $key,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model'      => $model,
            'max_tokens' => $maxTokens,
            'system'     => $system,
            'messages'   => [['role' => 'user', 'content' => $user]],
        ]);

        if (!$response->successful()) {
            throw new RuntimeException('Claude API error: ' . $response->body());
        }

        return $response->json('content.0.text', '');
    }

    private static function callOpenAI(string $key, string $model, string $system, string $user, int $maxTokens, float $temperature = 0.7): string
    {
        $response = Http::withToken($key)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model'       => $model,
                'max_tokens'  => $maxTokens,
                'temperature' => $temperature,
                'messages'    => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user',   'content' => $user],
                ],
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('OpenAI API error: ' . $response->body());
        }

        return $response->json('choices.0.message.content', '');
    }

    private static function callGemini(string $key, string $model, string $system, string $user, int $maxTokens): string
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}";

        $response = Http::post($url, [
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $system . "\n\n" . $user]]],
            ],
            'generationConfig' => ['maxOutputTokens' => $maxTokens],
        ]);

        if (!$response->successful()) {
            throw new RuntimeException('Gemini API error: ' . $response->body());
        }

        return $response->json('candidates.0.content.parts.0.text', '');
    }

    private static function parseJson(string $raw, array $fallback): array
    {
        // Strip possible markdown fences
        $clean = preg_replace('/^```(?:json)?\s*/m', '', $raw);
        $clean = preg_replace('/```\s*$/m', '', $clean);
        $clean = trim($clean ?? '');

        $data = json_decode($clean, true);

        if (!is_array($data)) {
            throw new RuntimeException('AI returned invalid JSON. Raw: ' . substr($raw, 0, 300));
        }

        return array_merge($fallback, $data);
    }

    private static function toClassName(string $label): string
    {
        return str_replace(['-', ' ', '_'], '', ucwords(str_replace(['-', '_'], ' ', $label)));
    }
}
