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
    //  Plugin Documentation Generator
    // ──────────────────────────────────────────────

    /**
     * Generate a comprehensive README.md for a plugin using AI.
     * Returns the Markdown string.
     */
    public static function generatePluginDocs(array $pluginData): string
    {
        $name          = $pluginData['name']        ?? '';
        $label         = $pluginData['label']       ?? $name;
        $description   = $pluginData['description'] ?? '';
        $version       = $pluginData['version']     ?? '1.0.0';
        $author        = $pluginData['author']       ?? '';
        $authorUrl     = $pluginData['author_url']   ?? '';
        $category      = $pluginData['category']     ?? '';
        $license       = $pluginData['license']      ?? 'MIT';
        $requires      = $pluginData['requires']     ?? '1.0.0';
        $hooks         = implode(', ', $pluginData['hooks'] ?? []);
        $schemaJson    = json_encode($pluginData['settings_schema'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $pluginPhp     = $pluginData['plugin_php']   ?? '';
        $widgetBlade   = $pluginData['widget_blade'] ?? '';

        $systemPrompt = <<<'SYSTEM'
Você é um technical writer especialista em documentação de plugins para CMS.
Gera documentação README.md profissional e completa em Markdown para plugins AnimusFlow.

A documentação deve incluir TODAS as secções seguintes:
1. Cabeçalho com badges (versão, licença, categoria)
2. Descrição detalhada do que o plugin faz
3. Funcionalidades principais (lista com ícones emoji)
4. Requisitos do sistema
5. Instalação passo-a-passo
6. Configuração (com tabela de todos os campos de configuração)
7. Referência de Hooks (como o plugin se integra com o CMS)
8. Utilização e exemplos práticos
9. Personalização (CSS, JS)
10. Perguntas Frequentes (FAQ) — mínimo 3 perguntas relevantes
11. Changelog (v{versão} — data actual)
12. Licença e Autor

Usa Markdown rico: tabelas, code blocks com syntax highlighting, badges, listas com emojis.
Escreve em português (PT-PT), de forma profissional mas acessível.
SYSTEM;

        $userPrompt = <<<PROMPT
Gera documentação README.md completa para este plugin:

**Nome:** {$label} ({$name})
**Versão:** {$version}
**Descrição:** {$description}
**Categoria:** {$category}
**Licença:** {$license}
**Hooks activos:** {$hooks}
**AnimusFlow mínimo:** {$requires}
**Autor:** {$author} {$authorUrl}

**Campos de Configuração:**
{$schemaJson}

**Plugin.php (resumo):**
```php
{$pluginPhp}
```

**Widget HTML:**
```html
{$widgetBlade}
```
PROMPT;

        return self::call($systemPrompt, $userPrompt, 6144);
    }

    // ──────────────────────────────────────────────
    //  Plugin Inspiration (category-based examples)
    // ──────────────────────────────────────────────

    /**
     * Generate 3 concrete plugin examples for a given category.
     *
     * Returns an array of up to 3 examples, each with:
     *   title, description, inspiration_source, plugin_php,
     *   widget_blade, widget_js, custom_css, settings_schema, hooks
     */
    public static function inspirePlugin(
        string $category,
        string $pluginName,
        string $pluginLabel,
        array  $hooks,
        string $description = ''
    ): array {
        $hooksStr   = implode(', ', $hooks ?: ['page.render']);
        $className  = self::toClassName($pluginLabel ?: $pluginName);
        $descPart   = $description ? "\nPlugin description: {$description}" : '';

        $systemPrompt = <<<SYSTEM
Você é um especialista em desenvolvimento de plugins para o AnimusFlow CMS e tem profundo conhecimento de padrões de plugins em WordPress, Joomla, Drupal e outros CMS populares.

Quando o utilizador pede inspiração para um plugin de categoria "{$category}", você deve:
1. Pesquisar no seu conhecimento exemplos reais de plugins populares dessa categoria
2. Gerar 3 exemplos COMPLETOS e FUNCIONAIS, do mais simples ao mais completo
3. Cada exemplo deve ter um propósito ligeiramente diferente dentro da mesma categoria

AnimusFlow plugin hooks disponíveis:
- page.render: onPageRender(\$page): string  — retorna HTML injectado antes de </body>. IMPORTANTE: use file_get_contents(__DIR__ . '/views/widget.blade.php') ou heredoc/strings inline para retornar HTML, NUNCA use view()->render()
- content.publish: onContentPublish(\$page): void — disparado ao publicar página
- admin.sidebar: onAdminSidebar(): array — retorna ['label', 'icon', 'url']

REGRAS CRÍTICAS:
- Plugin.php NUNCA usa view()->render() — o CMS não regista namespaces de views
- Plugin.php deve retornar HTML como string (heredoc, sprintf ou file_get_contents)
- Sempre include declare(strict_types=1) no PHP
- widget_blade é o ficheiro views/widget.blade.php separado (para referência, não chamado por view())
- CSS deve usar classes com prefixo único (ex: .af-{slug}-*)
- JavaScript deve ser vanilla JS dentro de DOMContentLoaded

A resposta DEVE ser JSON válido apenas — sem markdown, sem code fences, sem explicações.

Retorna exactamente esta estrutura:
{
  "examples": [
    {
      "title": "Nome do exemplo",
      "description": "O que este exemplo faz",
      "inspiration_source": "Inspirado em: [plugin/padrão real, ex: 'Hello Bar do Sumo', 'Google Analytics snippet', etc.]",
      "complexity": "simples|médio|avançado",
      "hooks": ["page.render"],
      "plugin_php": "<?php\\n\\ndeclare(strict_types=1);\\n\\nclass {$className}Plugin\\n{\\n    ...código completo...\\n}\\n",
      "widget_blade": "<div class=\\\"af-{slug}-widget\\\">...</div>",
      "widget_js": "document.addEventListener('DOMContentLoaded', () => { ... });",
      "custom_css": ".af-{slug}-* { ... }",
      "settings_schema": [{"key":"...","label":"...","type":"text","default":"","placeholder":"","hint":""}]
    }
  ]
}
SYSTEM;

        $userPrompt = "Gera 3 exemplos de plugin para a categoria \"{$category}\" para o AnimusFlow CMS.{$descPart}\nHooks activos: {$hooksStr}\nNome do plugin: {$pluginName}";

        $raw = self::call($systemPrompt, $userPrompt, 8192);

        $parsed = self::parseJson($raw, ['examples' => []]);

        // Normalise: ensure at most 3 examples
        $parsed['examples'] = array_slice($parsed['examples'] ?? [], 0, 3);

        return $parsed;
    }

    // ──────────────────────────────────────────────
    //  Multimodal Chat (Plugin assistant)
    // ──────────────────────────────────────────────

    /**
     * Multi-turn chat that can analyse images, PDFs and other attachments
     * to help the user build a plugin.
     *
     * Same attachment descriptor format as chatTheme().
     * Returns {reply:string, updates:array|null}
     */
    public static function chatPlugin(array $history, string $currentPluginJson, array $attachments = []): array
    {
        $system = <<<SYSTEM
Você é um especialista em desenvolvimento de plugins para o AnimusFlow CMS.
Ajuda o utilizador a criar e configurar plugins através de conversa natural em português (PT-PT).

PLUGIN ACTUAL (JSON):
{$currentPluginJson}

CAMPOS DISPONÍVEIS PARA ACTUALIZAR:
- label, description, version, status
- hooks — array dos eventos activos: "page.render", "content.publish", "admin.sidebar"
- plugin_php — código PHP completo da classe principal (incluir declare(strict_types=1))
- widget_blade — template Blade injectado no front-end via hook page.render
- widget_js — JavaScript do widget (carregado no front-end)
- custom_css — CSS do plugin
- settings_schema — array de campos configuráveis [{key, label, type, default, placeholder, hint}]

ESTRUTURA DO PLUGIN AnimusFlow:
- page.render:     onPageRender(\$page): string  — retorna HTML injectado antes de </body>
- content.publish: onContentPublish(\$page): void — disparado ao publicar uma página
- admin.sidebar:   onAdminSidebar(): array        — retorna ['label', 'icon', 'url']

TYPES PARA settings_schema: text, textarea, color, select (com "options": {"val":"Label"}), toggle (com "toggle_label").

INSTRUÇÕES:
1. Responde SEMPRE em português (PT-PT), de forma clara e concisa.
2. Se o utilizador pedir alterações ao plugin, inclui no final da resposta um bloco JSON:
   ```json_updates
   { apenas os campos que mudam }
   ```
3. Se analisares imagens ou documentos, usa-os para inspiração no design do widget.
4. Se não há alterações, não incluis o bloco json_updates.
5. Sê proactivo: sugere melhorias e boas práticas de desenvolvimento.
6. Quando gerares PHP, inclui sempre a classe COMPLETA com declare(strict_types=1).
SYSTEM;

        $provider = StudioSetting::get('ai_provider', 'claude');
        $model    = StudioSetting::get('ai_model', '');
        $rawKey   = StudioSetting::get('ai_api_key', '');

        $apiKey = '';
        if (!empty($rawKey)) {
            try { $apiKey = decrypt($rawKey); } catch (\Throwable) { $apiKey = $rawKey; }
        }
        if (empty($apiKey)) {
            throw new RuntimeException('Chave AI não configurada. Vai a Definições → Provedor IA.');
        }

        $raw = match ($provider) {
            'openai' => self::chatOpenAI($apiKey, $model ?: 'gpt-4o', $system, $history, $attachments),
            default  => self::chatClaude($apiKey, $model ?: 'claude-sonnet-4-5', $system, $history, $attachments),
        };

        $updates = null;
        if (preg_match('/```json_updates\s*([\s\S]*?)```/m', $raw, $m)) {
            $parsed = json_decode(trim($m[1]), true);
            if (is_array($parsed)) {
                $updates = $parsed;
            }
        }

        $reply = preg_replace('/```json_updates\s*[\s\S]*?```/m', '', $raw);
        $reply = trim($reply ?? $raw);

        return ['reply' => $reply, 'updates' => $updates];
    }

    // ──────────────────────────────────────────────
    //  Internal helpers
    // ──────────────────────────────────────────────
    //  Multimodal Chat (Theme assistant)
    // ──────────────────────────────────────────────

    /**
     * Multi-turn chat that can analyse images, PDFs and other attachments
     * to help the user design a theme.
     *
     * $history  — array of {role:'user'|'assistant', content:string}
     * $attachments — array of processed attachment descriptors:
     *   image:    {type:'image',    mime:string, data:string (base64)}
     *   document: {type:'document', data:string (base64)}
     *   other:    {type:'text_description', description:string}
     *
     * Returns {reply:string, updates:array|null}
     */
    public static function chatTheme(array $history, string $currentThemeJson, array $attachments = []): array
    {
        $system = <<<SYSTEM
Você é um designer especialista em temas para o AnimusFlow CMS.
Ajuda o utilizador a criar e configurar temas através de conversa natural em português (PT-PT).

TEMA ACTUAL (JSON):
{$currentThemeJson}

CAMPOS DISPONÍVEIS PARA ACTUALIZAR:
- label, description, version, status
- colors.light / colors.dark — mapas de variáveis CSS (ex: "--color-primary": "#...")
- fonts.heading / fonts.body — nome da família de fonte
- layout_config — header_type, nav_type, footer_type, layout_type, max_width, spacing, show_dark_toggle, back_to_top, header_cta_text, header_cta_url
- capabilities — video_bg, parallax, animations, lightbox, mega_menu, search, cookie_banner, preloader, scroll_progress (true/false)
- sections — {tipo: html_blade}
- components — {uid: {type, variant, blade}}
- custom_css, custom_js
- variants — array de paletas alternativas

INSTRUÇÕES:
1. Responde SEMPRE em português (PT-PT), de forma clara e concisa.
2. Se o utilizador pedir alterações ao tema, inclui no final da resposta um bloco JSON:
   ```json_updates
   { apenas os campos que mudam }
   ```
3. Se analisares imagens, vídeos ou documentos, extrai cores, estilos e layouts para sugerir mudanças concretas.
4. Se não há alterações, não incluis o bloco json_updates.
5. Sê proactivo: sugere melhorias mesmo quando o utilizador faz perguntas gerais.
SYSTEM;

        $provider = StudioSetting::get('ai_provider', 'claude');
        $model    = StudioSetting::get('ai_model', '');
        $rawKey   = StudioSetting::get('ai_api_key', '');

        $apiKey = '';
        if (!empty($rawKey)) {
            try { $apiKey = decrypt($rawKey); } catch (\Throwable) { $apiKey = $rawKey; }
        }
        if (empty($apiKey)) {
            throw new RuntimeException('Chave AI não configurada. Vai a Definições → Provedor IA.');
        }

        $raw = match ($provider) {
            'openai' => self::chatOpenAI($apiKey, $model ?: 'gpt-4o', $system, $history, $attachments),
            default  => self::chatClaude($apiKey, $model ?: 'claude-sonnet-4-5', $system, $history, $attachments),
        };

        // Extract json_updates block
        $updates = null;
        if (preg_match('/```json_updates\s*([\s\S]*?)```/m', $raw, $m)) {
            $parsed = json_decode(trim($m[1]), true);
            if (is_array($parsed)) {
                $updates = $parsed;
            }
        }

        // Remove json_updates from visible reply
        $reply = preg_replace('/```json_updates\s*[\s\S]*?```/m', '', $raw);
        $reply = trim($reply ?? $raw);

        return ['reply' => $reply, 'updates' => $updates];
    }

    private static function chatClaude(string $key, string $model, string $system, array $history, array $attachments): string
    {
        $messages = [];

        foreach ($history as $i => $msg) {
            $isLast = $i === count($history) - 1;

            // On the last user message, append attachment content blocks
            if ($isLast && $msg['role'] === 'user' && !empty($attachments)) {
                $blocks = [['type' => 'text', 'text' => $msg['content']]];

                foreach ($attachments as $att) {
                    if ($att['type'] === 'image') {
                        $blocks[] = [
                            'type'   => 'image',
                            'source' => [
                                'type'       => 'base64',
                                'media_type' => $att['mime'],
                                'data'       => $att['data'],
                            ],
                        ];
                    } elseif ($att['type'] === 'document') {
                        $blocks[] = [
                            'type'   => 'document',
                            'source' => [
                                'type'       => 'base64',
                                'media_type' => 'application/pdf',
                                'data'       => $att['data'],
                            ],
                        ];
                    } elseif ($att['type'] === 'text_description') {
                        $blocks[] = ['type' => 'text', 'text' => $att['description']];
                    }
                }

                $messages[] = ['role' => 'user', 'content' => $blocks];
            } else {
                $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
            }
        }

        $response = Http::withHeaders([
            'x-api-key'         => $key,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
            'anthropic-beta'    => 'pdfs-2024-09-25',
        ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
            'model'      => $model,
            'max_tokens' => 4096,
            'system'     => $system,
            'messages'   => $messages,
        ]);

        if (!$response->successful()) {
            throw new RuntimeException('Claude API error: ' . $response->body());
        }

        return $response->json('content.0.text', '');
    }

    private static function chatOpenAI(string $key, string $model, string $system, array $history, array $attachments): string
    {
        $messages = [['role' => 'system', 'content' => $system]];

        foreach ($history as $i => $msg) {
            $isLast = $i === count($history) - 1;

            if ($isLast && $msg['role'] === 'user' && !empty($attachments)) {
                $parts = [['type' => 'text', 'text' => $msg['content']]];

                foreach ($attachments as $att) {
                    if ($att['type'] === 'image') {
                        $parts[] = [
                            'type'      => 'image_url',
                            'image_url' => ['url' => "data:{$att['mime']};base64,{$att['data']}"],
                        ];
                    } elseif ($att['type'] === 'text_description') {
                        $parts[] = ['type' => 'text', 'text' => $att['description']];
                    }
                }

                $messages[] = ['role' => 'user', 'content' => $parts];
            } else {
                $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
            }
        }

        $response = Http::withToken($key)
            ->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model'      => $model,
                'max_tokens' => 4096,
                'messages'   => $messages,
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('OpenAI API error: ' . $response->body());
        }

        return $response->json('choices.0.message.content', '');
    }

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
