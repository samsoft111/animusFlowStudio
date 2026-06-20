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
    //  Theme Inspiration (category-based)
    // ──────────────────────────────────────────────

    /**
     * Generate a complete theme spec based on site category and visual style.
     *
     * Returns a full theme data array with:
     *   label, description, colors (light+dark), fonts, layout_config,
     *   capabilities, sections, custom_css
     */
    public static function generateThemeFromCategory(string $category, string $style = 'moderno'): array
    {
        $systemPrompt = <<<SYSTEM
Você é um designer de temas web especialista com profundo conhecimento de tendências de design para diferentes sectores de negócio. Você conhece os melhores sites e temas de cada categoria (restaurantes, e-commerce, agências, portfolios, etc.) e usa esse conhecimento para criar temas inspirados nas melhores práticas do sector.

Quando o utilizador pede inspiração para um tema da categoria "{$category}" com estilo "{$style}", você deve:
1. Basear-se nos melhores sites reais dessa categoria (ex: para restaurante: Noma, Eleven Madison Park, etc.)
2. Gerar um tema COMPLETO e PROFISSIONAL com identidade visual forte
3. As cores devem ser coerentes com a psicologia da categoria (restaurante = tons quentes, clínica = azul/branco limpo, etc.)
4. As secções HTML devem usar CSS custom properties (var(--color-primary), etc.)

CSS Custom Properties obrigatórias (usa oklch ou hex):
--color-primary, --color-primary-foreground
--color-secondary, --color-accent
--color-background, --color-foreground
--color-card, --color-muted, --color-muted-foreground
--color-border, --color-success, --color-warning, --color-destructive
--font-heading, --font-body (Google Fonts)

A resposta DEVE ser JSON válido apenas — sem markdown, sem code fences, sem explicações.

Retorna exactamente esta estrutura:
{
  "label": "Nome do Tema",
  "description": "Descrição breve do tema e sua inspiração",
  "inspiration": "Inspirado em: [sites/referências reais desta categoria]",
  "colors": {
    "light": {
      "--color-primary": "#...",
      "--color-primary-foreground": "#fff",
      "--color-secondary": "#...",
      "--color-accent": "#...",
      "--color-background": "#...",
      "--color-foreground": "#...",
      "--color-card": "#...",
      "--color-muted": "#...",
      "--color-muted-foreground": "#...",
      "--color-border": "#...",
      "--color-success": "#22c55e",
      "--color-warning": "#f59e0b",
      "--color-destructive": "#ef4444"
    },
    "dark": {
      "--color-primary": "#...",
      "--color-primary-foreground": "#fff",
      "--color-secondary": "#...",
      "--color-accent": "#...",
      "--color-background": "#...",
      "--color-foreground": "#...",
      "--color-card": "#...",
      "--color-muted": "#...",
      "--color-muted-foreground": "#...",
      "--color-border": "#...",
      "--color-success": "#22c55e",
      "--color-warning": "#f59e0b",
      "--color-destructive": "#ef4444"
    }
  },
  "fonts": {
    "heading": "Playfair Display",
    "body": "Inter"
  },
  "layout_config": {
    "header_type": "transparent|solid|minimal",
    "nav_type": "horizontal|sidebar|centered",
    "footer_type": "minimal|full|mega",
    "layout_type": "boxed|full-width",
    "max_width": "1200px",
    "spacing": "comfortable|compact|spacious",
    "show_dark_toggle": true,
    "back_to_top": true,
    "header_cta_text": "Reservar Mesa",
    "header_cta_url": "#contacto"
  },
  "capabilities": {
    "video_bg": false,
    "parallax": true,
    "animations": true,
    "lightbox": false,
    "mega_menu": false,
    "search": false,
    "cookie_banner": true,
    "preloader": false,
    "scroll_progress": false
  },
  "sections": {
    "hero": "<section style=\\\"background:var(--color-primary);padding:6rem 2rem;text-align:center;\\\"><h1 style=\\\"color:var(--color-primary-foreground);font-family:var(--font-heading);font-size:3rem;\\\">{{ \$page->title ?? 'Bem-vindo' }}</h1><p style=\\\"color:var(--color-primary-foreground);opacity:.8;margin-top:1rem;\\\">{{ \$page->description ?? '' }}</p><a href='#' style=\\\"display:inline-block;margin-top:2rem;padding:.875rem 2rem;background:var(--color-accent);color:#fff;border-radius:.5rem;text-decoration:none;font-weight:600;\\\">Começar</a></section>",
    "features": "<section style=\\\"padding:5rem 2rem;background:var(--color-background);\\\"><div style=\\\"max-width:1200px;margin:0 auto;display:grid;grid-template-columns:repeat(3,1fr);gap:2rem;\\\"><div style=\\\"padding:2rem;background:var(--color-card);border-radius:1rem;border:1px solid var(--color-border);\\\"><h3 style=\\\"color:var(--color-foreground);font-family:var(--font-heading);\\\">Qualidade</h3><p style=\\\"color:var(--color-muted-foreground);margin-top:.5rem;\\\">Comprometidos com a excelência em cada detalhe.</p></div><div style=\\\"padding:2rem;background:var(--color-card);border-radius:1rem;border:1px solid var(--color-border);\\\"><h3 style=\\\"color:var(--color-foreground);font-family:var(--font-heading);\\\">Experiência</h3><p style=\\\"color:var(--color-muted-foreground);margin-top:.5rem;\\\">Anos de experiência ao seu serviço.</p></div><div style=\\\"padding:2rem;background:var(--color-card);border-radius:1rem;border:1px solid var(--color-border);\\\"><h3 style=\\\"color:var(--color-foreground);font-family:var(--font-heading);\\\">Confiança</h3><p style=\\\"color:var(--color-muted-foreground);margin-top:.5rem;\\\">Centenas de clientes satisfeitos.</p></div></div></section>",
    "cta": "<section style=\\\"padding:5rem 2rem;background:var(--color-primary);text-align:center;\\\"><h2 style=\\\"color:var(--color-primary-foreground);font-family:var(--font-heading);font-size:2.25rem;\\\">Pronto para começar?</h2><p style=\\\"color:var(--color-primary-foreground);opacity:.8;margin-top:1rem;\\\">Entre em contacto connosco hoje.</p><a href='#contacto' style=\\\"display:inline-block;margin-top:2rem;padding:.875rem 2.5rem;background:#fff;color:var(--color-primary);border-radius:.5rem;font-weight:700;\\\">Contactar</a></section>"
  },
  "custom_css": "/* Tema {$category} - estilo {$style} */\\n:root { font-synthesis: none; }\\n* { box-sizing: border-box; }\\nbody { font-family: var(--font-body, Inter), sans-serif; background: var(--color-background); color: var(--color-foreground); }"
}
SYSTEM;

        $userPrompt = "Gera um tema profissional para a categoria \"{$category}\" com estilo visual \"{$style}\". "
            . "Inspira-te nos melhores sites reais desta categoria. "
            . "As cores devem refletir a identidade visual típica desta categoria. "
            . "As secções HTML devem ser modernas e usar CSS custom properties.";

        $raw = self::call($systemPrompt, $userPrompt, 6144);

        $parsed = self::parseJson($raw, [
            'label'         => ucfirst($category) . ' Theme',
            'description'   => "Tema para {$category}",
            'inspiration'   => '',
            'colors'        => ['light' => [], 'dark' => []],
            'fonts'         => ['heading' => 'Inter', 'body' => 'Inter'],
            'layout_config' => [],
            'capabilities'  => [],
            'sections'      => [],
            'custom_css'    => '',
        ]);

        return $parsed;
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
7. Se o utilizador pedir para CRIAR UM PLUGIN COMPLETO de raiz ("cria um plugin de X", "constrói um plugin que faça Y"), NÃO tentes gerar tudo nesta resposta. Responde com UMA frase curta a confirmar e inclui um bloco:
   ```build
   { "brief": "resumo claro do plugin a construir, em 1-2 frases" }
   ```
   Neste caso NÃO incluas o bloco json_updates.
SYSTEM;

        $provider = StudioSetting::get('ai_provider', 'claude');
        $model    = StudioSetting::get('ai_model', '');
        $apiKey   = self::resolveApiKey($provider);

        if (empty($apiKey)) {
            throw new RuntimeException('Chave AI não configurada. Vai a Definições → Provedor IA.');
        }

        $raw = match ($provider) {
            'openai' => self::chatOpenAI($apiKey, $model ?: 'gpt-4o', $system, $history, $attachments),
            'gemini' => self::chatGemini($apiKey, $model ?: 'gemini-2.0-flash', $system, $history, $attachments),
            'claude' => self::chatClaude($apiKey, $model ?: 'claude-sonnet-4-6', $system, $history, $attachments),
            default  => self::chatClaude($apiKey, $model ?: 'claude-sonnet-4-6', $system, $history, $attachments),
        };

        $updates = null;
        if (preg_match('/```json_updates\s*([\s\S]*?)```/m', $raw, $m)) {
            $parsed = json_decode(trim($m[1]), true);
            if (is_array($parsed)) {
                $updates = $parsed;
            }
        }

        // Detect a full-build directive — the AI decides when to run the pipeline
        $build = null;
        if (preg_match('/```build\s*([\s\S]*?)```/m', $raw, $bm)) {
            $parsed = json_decode(trim($bm[1]), true);
            if (is_array($parsed) && !empty($parsed['brief'])) {
                $build = ['brief' => (string) $parsed['brief']];
            }
        }

        // Detect a recipe block to register
        if (preg_match('/```recipe\s*([\s\S]*?)```/m', $raw, $rm)) {
            $parsedRecipe = json_decode(trim($rm[1]), true);
            if (is_array($parsedRecipe) && !empty($parsedRecipe['name']) && !empty($parsedRecipe['prompt_pattern'])) {
                \App\Models\StudioAiRecipe::updateOrCreate(
                    ['recipe_type' => 'plugin', 'name' => $parsedRecipe['name']],
                    [
                        'description'    => $parsedRecipe['description'] ?? null,
                        'prompt_pattern' => $parsedRecipe['prompt_pattern'],
                        'code_templates' => $parsedRecipe['code_templates'] ?? [],
                        'reply_template' => $parsedRecipe['reply_template'] ?? 'Resolvido via receita local.',
                    ]
                );
            }
        }

        $reply = preg_replace('/```json_updates\s*[\s\S]*?```/m', '', $raw);
        $reply = preg_replace('/```build\s*[\s\S]*?```/m', '', $reply);
        $reply = preg_replace('/```recipe\s*[\s\S]*?```/m', '', $reply);
        $reply = trim($reply ?? $raw);

        return ['reply' => $reply, 'updates' => $updates, 'build' => $build];
    }

    // ──────────────────────────────────────────────
    //  Multi-agent plugin builder (Modo Construção — plugins)
    // ──────────────────────────────────────────────

    /** Catalogue of specialised plugin-building agents (single source of truth). */
    public static function pluginAgents(): array
    {
        return [
            ['id' => 'logic',    'icon' => '🧩', 'label' => 'Lógica & Hooks',    'hint' => 'Classe PHP principal e hooks'],
            ['id' => 'widget',   'icon' => '🎨', 'label' => 'Interface (Widget)', 'hint' => 'Blade, JS e CSS do widget'],
            ['id' => 'settings', 'icon' => '⚙️', 'label' => 'Configurações',     'hint' => 'Esquema de campos configuráveis'],
        ];
    }

    /** Planner: turn a brief into an ordered list of plugin agents. Returns ['direction','agents']. */
    public static function buildPluginPlan(string $brief, string $skill = '', array $attachments = []): array
    {
        $ids  = array_column(self::pluginAgents(), 'id');
        $list = implode(', ', $ids);
        $skillBlock = trim($skill) !== '' ? "INSTRUÇÕES/SKILL DO UTILIZADOR (segue à risca):\n{$skill}\n\n" : '';

        $system = <<<SYSTEM
Você é o ORQUESTRADOR de construção de plugins do AnimusFlow CMS.
A partir de um brief, planeias quais agentes especializados devem correr e por que ordem.

{$skillBlock}AGENTES DISPONÍVEIS (ids): {$list}

Responde APENAS com um bloco json_updates, sem texto fora dele:
```json_updates
{
  "direction": "1-2 frases a descrever o plugin (objectivo, hooks, comportamento)",
  "agents": ["lista ordenada de ids de agentes a executar"]
}
```
Regras: usa só ids válidos da lista; ordena de forma lógica (logic → widget → settings); inclui apenas o que o brief justifica.
SYSTEM;

        $historyMsg = [['role' => 'user', 'content' => "BRIEF: {$brief}\n\nPlaneia os agentes."]];
        $raw = self::chatDispatch($system, $historyMsg, $attachments, 1500);

        $plan = ['direction' => '', 'agents' => []];
        if (preg_match('/```json_updates\s*([\s\S]*?)```/m', $raw, $m)) {
            $parsed = json_decode(trim($m[1]), true);
            if (is_array($parsed)) {
                $plan['direction'] = (string) ($parsed['direction'] ?? '');
                $plan['agents']    = array_values(array_intersect($parsed['agents'] ?? [], $ids));
            }
        }
        if (empty($plan['agents'])) {
            $plan['agents'] = ['logic', 'widget', 'settings'];
        }

        return $plan;
    }

    /** Run ONE specialised plugin agent. Returns ['agent','reply','updates']. */
    public static function runPluginAgent(string $agentId, string $brief, string $direction, string $currentPluginJson, array $attachments = [], string $note = ''): array
    {
        $system = self::pluginAgentSystem($agentId, $brief, $direction, $currentPluginJson);

        $userMsg = 'Gera agora a tua parte do plugin.';
        if (trim($note) !== '') {
            $userMsg .= "\n\nNota do verificador (corrige especificamente isto): " . $note;
        }
        $historyMsg = [['role' => 'user', 'content' => $userMsg]];
        $raw = self::chatDispatch($system, $historyMsg, $attachments, 16000);

        $updates = null;
        if (preg_match('/```json_updates\s*([\s\S]*?)```/m', $raw, $m)) {
            $parsed = json_decode(trim($m[1]), true);
            if (is_array($parsed)) {
                $updates = $parsed;
            }
        }

        $reply = trim((string) preg_replace('/```json_updates\s*[\s\S]*?```/m', '', $raw));

        return ['agent' => $agentId, 'reply' => $reply, 'updates' => $updates];
    }

    /** Verifier: review the plugin against the brief; returns ['summary','issues']. */
    public static function verifyPlugin(string $brief, string $direction, string $pluginJson): array
    {
        $ids  = array_column(self::pluginAgents(), 'id');
        $list = implode(', ', $ids);

        $system = <<<SYSTEM
Você é o agente VERIFICADOR de qualidade de plugins do AnimusFlow CMS.
Analisa o estado actual do plugin face ao brief e identifica partes em falta, fracas ou incoerentes.

AGENTES QUE PODEM CORRIGIR (ids válidos): {$list}

BRIEF: {$brief}
DIRECÇÃO: {$direction}
ESTADO ACTUAL DO PLUGIN: {$pluginJson}

Responde APENAS com um bloco json_updates, sem texto fora dele:
```json_updates
{
  "summary": "1-2 frases sobre o estado geral do plugin",
  "issues": [ {"agent": "id_do_agente", "reason": "o que melhorar, 1 frase accionável"} ]
}
```
Regras: usa só ids válidos; inclui no máximo 4 problemas REAIS e accionáveis; se o plugin estiver bom, devolve "issues": [].
SYSTEM;

        $historyMsg = [['role' => 'user', 'content' => 'Verifica o plugin e lista o que corrigir.']];
        $raw = self::chatDispatch($system, $historyMsg, [], 2000);

        $out = ['summary' => '', 'issues' => []];
        if (preg_match('/```json_updates\s*([\s\S]*?)```/m', $raw, $m)) {
            $parsed = json_decode(trim($m[1]), true);
            if (is_array($parsed)) {
                $out['summary'] = (string) ($parsed['summary'] ?? '');
                foreach (($parsed['issues'] ?? []) as $iss) {
                    if (is_array($iss) && in_array($iss['agent'] ?? '', $ids, true)) {
                        $out['issues'][] = ['agent' => $iss['agent'], 'reason' => (string) ($iss['reason'] ?? '')];
                    }
                }
            }
        }

        return $out;
    }

    /** Build the focused system prompt for one plugin agent. */
    private static function pluginAgentSystem(string $agentId, string $brief, string $direction, string $pluginJson): string
    {
        $base = <<<BASE
Você é um agente especializado de construção de plugins para o AnimusFlow CMS.
Responde em português (PT-PT). Produzes UMA frase curta de resumo seguida de um bloco json_updates APENAS com os campos da tua responsabilidade — nada mais.

BRIEF: {$brief}
DIRECÇÃO: {$direction}
PLUGIN ACTUAL (resumo): {$pluginJson}

ESTRUTURA AnimusFlow: page.render→onPageRender(\$page):string; content.publish→onContentPublish(\$page):void; admin.sidebar→onAdminSidebar():array.

TAREFA:
BASE;

        $task = match ($agentId) {
            'logic' => <<<LOGIC
Gera a classe PHP principal do plugin e os hooks activos (e metadados: label, description, version).
Responsável por: plugin_php (classe COMPLETA com declare(strict_types=1)), hooks (array), label, description, version.
```json_updates
{
  "label": "Nome do Plugin",
  "description": "O que faz",
  "version": "1.0.0",
  "hooks": ["page.render"],
  "plugin_php": "<?php\\ndeclare(strict_types=1);\\n..."
}
```
LOGIC,
            'widget' => <<<WIDGET
Gera a interface do plugin: o template Blade do widget, o JavaScript e o CSS.
Responsável por: widget_blade, widget_js, custom_css.
```json_updates
{
  "widget_blade": "<div class=\"af-plugin\">...</div>",
  "widget_js": "// JS do widget",
  "custom_css": "/* CSS do widget */"
}
```
WIDGET,
            'settings' => <<<SETTINGS
Gera o esquema de configurações do plugin (campos configuráveis pelo administrador).
Responsável por: settings_schema (array de {key, label, type, default, placeholder, hint}).
Types: text, textarea, color, select (com "options"), toggle (com "toggle_label").
```json_updates
{
  "settings_schema": [
    {"key": "titulo", "label": "Título", "type": "text", "default": "", "placeholder": "", "hint": ""}
  ]
}
```
SETTINGS,
            default => 'Gera a tua parte e devolve o json_updates apropriado.',
        };

        return $base . "\n" . $task;
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
6. Se o utilizador pedir para CRIAR UM TEMA COMPLETO de raiz ou uma construção grande (várias secções — ex: "cria um tema para um restaurante", "constrói um site para a minha clínica"), NÃO tentes fazer tudo nesta resposta. Em vez disso responde com UMA frase curta a confirmar que vais construir o tema e inclui um bloco:
   ```build
   { "brief": "resumo claro do que construir, em 1-2 frases" }
   ```
   Neste caso NÃO incluas o bloco json_updates.
SYSTEM;

        $provider = StudioSetting::get('ai_provider', 'claude');
        $model    = StudioSetting::get('ai_model', '');
        $apiKey   = self::resolveApiKey($provider);

        if (empty($apiKey)) {
            throw new RuntimeException('Chave AI não configurada. Vai a Definições → Provedor IA.');
        }

        $raw = match ($provider) {
            'openai' => self::chatOpenAI($apiKey, $model ?: 'gpt-4o', $system, $history, $attachments),
            'gemini' => self::chatGemini($apiKey, $model ?: 'gemini-2.0-flash', $system, $history, $attachments),
            'claude' => self::chatClaude($apiKey, $model ?: 'claude-sonnet-4-6', $system, $history, $attachments),
            default  => self::chatClaude($apiKey, $model ?: 'claude-sonnet-4-6', $system, $history, $attachments),
        };

        // Extract json_updates block
        $updates = null;
        if (preg_match('/```json_updates\s*([\s\S]*?)```/m', $raw, $m)) {
            $parsed = json_decode(trim($m[1]), true);
            if (is_array($parsed)) {
                $updates = $parsed;
            }
        }

        // Detect a full-build directive — the AI decides when a request warrants
        // the multi-agent pipeline instead of an inline edit.
        $build = null;
        if (preg_match('/```build\s*([\s\S]*?)```/m', $raw, $bm)) {
            $parsed = json_decode(trim($bm[1]), true);
            if (is_array($parsed) && !empty($parsed['brief'])) {
                $build = ['brief' => (string) $parsed['brief']];
            }
        }

        // Detect a recipe block to register
        if (preg_match('/```recipe\s*([\s\S]*?)```/m', $raw, $rm)) {
            $parsedRecipe = json_decode(trim($rm[1]), true);
            if (is_array($parsedRecipe) && !empty($parsedRecipe['name']) && !empty($parsedRecipe['prompt_pattern'])) {
                \App\Models\StudioAiRecipe::updateOrCreate(
                    ['recipe_type' => 'theme', 'name' => $parsedRecipe['name']],
                    [
                        'description'    => $parsedRecipe['description'] ?? null,
                        'prompt_pattern' => $parsedRecipe['prompt_pattern'],
                        'code_templates' => $parsedRecipe['code_templates'] ?? [],
                        'reply_template' => $parsedRecipe['reply_template'] ?? 'Resolvido via receita local.',
                    ]
                );
            }
        }

        // Remove both control blocks from the visible reply
        $reply = preg_replace('/```json_updates\s*[\s\S]*?```/m', '', $raw);
        $reply = preg_replace('/```build\s*[\s\S]*?```/m', '', $reply);
        $reply = preg_replace('/```recipe\s*[\s\S]*?```/m', '', $reply);
        $reply = trim($reply ?? $raw);

        return ['reply' => $reply, 'updates' => $updates, 'build' => $build];
    }

    // ──────────────────────────────────────────────
    //  Multi-agent theme builder (Modo Construção)
    // ──────────────────────────────────────────────

    /** Catalogue of specialised theme-building agents (single source of truth). */
    public static function themeAgents(): array
    {
        return [
            ['id' => 'design',     'icon' => '🎨', 'label' => 'Design & Branding',     'hint' => 'Cores, Fontes, Layout, Menu e Rodapé'],
            ['id' => 'intro',      'icon' => '✨', 'label' => 'Apresentação & Features', 'hint' => 'Hero, Funcionalidades, Testemunhos e Galeria'],
            ['id' => 'conversion', 'icon' => '📣', 'label' => 'Negócio & Conversão',     'hint' => 'Preços, CTA, FAQ e Contacto'],
            ['id' => 'code',       'icon' => '💻', 'label' => 'Código Customizado',    'hint' => 'Ajustes finos de CSS e JS'],
        ];
    }

    /** Section-producing agents (output goes into sections.{id}). */
    private static function sectionAgents(): array
    {
        return ['hero', 'features', 'pricing', 'testimonials', 'gallery', 'cta', 'faq', 'contact', 'footer'];
    }

    /**
     * Orchestrator/Planner agent — turns a brief (+ optional skill/instructions)
     * into an ordered list of agents to run. Returns ['direction', 'agents'].
     */
    public static function buildThemePlan(string $brief, string $skill = '', array $attachments = []): array
    {
        $ids   = array_column(self::themeAgents(), 'id');
        $list  = implode(', ', $ids);
        $skillBlock = trim($skill) !== '' ? "INSTRUÇÕES/SKILL DO UTILIZADOR (segue à risca):\n{$skill}\n\n" : '';

        $system = <<<SYSTEM
Você é o ORQUESTRADOR de construção de temas do AnimusFlow CMS.
A partir de um brief, planeias quais agentes especializados devem correr e por que ordem.

{$skillBlock}AGENTES DISPONÍVEIS (ids): {$list}

Responde APENAS com um bloco json_updates, sem texto fora dele:
```json_updates
{
  "direction": "1-2 frases a descrever a direção de design (estilo, tom, público)",
  "agents": ["lista ordenada de ids de agentes a executar"]
}
```
Regras: usa só ids válidos da lista; ordena de forma lógica (design → intro → conversion → code); inclui apenas o que o brief justifica.
SYSTEM;

        $history = [['role' => 'user', 'content' => "BRIEF: {$brief}\n\nPlaneia os agentes."]];
        $raw     = self::chatDispatch($system, $history, $attachments, 1500);

        $plan = ['direction' => '', 'agents' => []];
        if (preg_match('/```json_updates\s*([\s\S]*?)```/m', $raw, $m)) {
            $parsed = json_decode(trim($m[1]), true);
            if (is_array($parsed)) {
                $plan['direction'] = (string) ($parsed['direction'] ?? '');
                $plan['agents']    = array_values(array_intersect($parsed['agents'] ?? [], $ids));
            }
        }
        if (empty($plan['agents'])) {
            $plan['agents'] = ['design', 'intro', 'conversion', 'code'];
        }

        return $plan;
    }

    /**
     * Run ONE specialised agent. Returns ['agent', 'reply', 'updates'].
     * Each agent produces a focused json_updates block (its field only),
     * which keeps each call well within the output-token limit.
     */
    public static function runThemeAgent(string $agentId, string $brief, string $direction, string $currentThemeJson, array $attachments = [], string $note = ''): array
    {
        $system = self::themeAgentSystem($agentId, $brief, $direction, $currentThemeJson);

        // Grouped macro-agents emit larger blocks of HTML/CSS — allocate full headroom (16000 tokens)
        $maxTok = 16000;

        $userMsg = 'Gera agora a tua parte do tema.';
        if (trim($note) !== '') {
            $userMsg .= "\n\nNota do verificador (corrige especificamente isto): " . $note;
        }
        $history = [['role' => 'user', 'content' => $userMsg]];
        $raw     = self::chatDispatch($system, $history, $attachments, $maxTok);

        $updates = null;
        if (preg_match('/```json_updates\s*([\s\S]*?)```/m', $raw, $m)) {
            $parsed = json_decode(trim($m[1]), true);
            if (is_array($parsed)) {
                $updates = $parsed;
            }
        }

        $reply = trim((string) preg_replace('/```json_updates\s*[\s\S]*?```/m', '', $raw));

        return ['agent' => $agentId, 'reply' => $reply, 'updates' => $updates];
    }

    /**
     * Verifier agent — reviews the current theme against the brief and returns
     * which agents should be re-run to fix weak/missing parts.
     * Returns ['summary' => string, 'issues' => [['agent','reason'], ...]].
     */
    public static function verifyTheme(string $brief, string $direction, string $themeJson): array
    {
        $ids  = array_column(self::themeAgents(), 'id');
        $list = implode(', ', $ids);

        $system = <<<SYSTEM
Você é o agente VERIFICADOR de qualidade de temas do AnimusFlow CMS.
Analisa o estado actual do tema face ao brief e identifica partes em falta, fracas ou incoerentes.

AGENTES QUE PODEM CORRIGIR (ids válidos): {$list}

BRIEF: {$brief}
DIRECÇÃO DE DESIGN: {$direction}
ESTADO ACTUAL DO TEMA: {$themeJson}

Responde APENAS com um bloco json_updates, sem texto fora dele:
```json_updates
{
  "summary": "1-2 frases sobre o estado geral do tema",
  "issues": [ {"agent": "id_do_agente", "reason": "o que melhorar, 1 frase accionável"} ]
}
```
Regras: usa só ids válidos; inclui no máximo 6 problemas REAIS e accionáveis; se o tema estiver bom, devolve "issues": [].
SYSTEM;

        $history = [['role' => 'user', 'content' => 'Verifica o tema e lista o que corrigir.']];
        $raw     = self::chatDispatch($system, $history, [], 2000);

        $out = ['summary' => '', 'issues' => []];
        if (preg_match('/```json_updates\s*([\s\S]*?)```/m', $raw, $m)) {
            $parsed = json_decode(trim($m[1]), true);
            if (is_array($parsed)) {
                $out['summary'] = (string) ($parsed['summary'] ?? '');
                foreach (($parsed['issues'] ?? []) as $iss) {
                    if (is_array($iss) && in_array($iss['agent'] ?? '', $ids, true)) {
                        $out['issues'][] = [
                            'agent'  => $iss['agent'],
                            'reason' => (string) ($iss['reason'] ?? ''),
                        ];
                    }
                }
            }
        }

        return $out;
    }

    /** Build the focused system prompt for one agent. */
    private static function themeAgentSystem(string $agentId, string $brief, string $direction, string $themeJson): string
    {
        $base = <<<BASE
Você é um agente especializado de construção de temas para o AnimusFlow CMS.
Responde em português (PT-PT). Produzes UMA frase curta de resumo seguida de um bloco json_updates APENAS com os campos da tua responsabilidade — nada mais.
Regras de HTML/CSS: HTML semântico; usa SEMPRE variáveis CSS do tema (var(--color-primary), var(--color-bg), var(--color-text), var(--font-heading), var(--font-body), etc.); nada de frameworks externos; conteúdo de demonstração realista para o contexto do brief.

BRIEF: {$brief}
DIREÇÃO DE DESIGN: {$direction}
TEMA ACTUAL (resumo): {$themeJson}

TAREFA:
BASE;

        $task = match ($agentId) {
            'design' => <<<DESIGN
Gera o design global e branding do tema (cores light e dark, fontes de títulos/corpo, layout, capacidades e a secção do rodapé).
Responsável por atualizar os campos: colors, fonts, layout_config, capabilities, e a secção "footer" (em sections.footer).
Exemplo de retorno em json_updates:
```json_updates
{
  "colors": {
    "light": {
      "--color-primary": "#..",
      "--color-secondary": "#..",
      "--color-accent": "#..",
      "--color-bg": "#..",
      "--color-surface": "#..",
      "--color-text": "#..",
      "--color-muted": "#..",
      "--color-border": "#..",
      "--color-success": "#22c55e",
      "--color-warning": "#f59e0b",
      "--color-destructive": "#ef4444"
    },
    "dark": {
      "--color-primary": "#..",
      "--color-bg": "#..",
      "--color-surface": "#..",
      "--color-text": "#..",
      "--color-muted": "#..",
      "--color-border": "#.."
    }
  },
  "fonts": {
    "heading": "Outfit",
    "body": "Inter"
  },
  "layout_config": {
    "header_type": "glass|solid|transparent",
    "nav_type": "horizontal|hamburger",
    "nav_position": "right|center",
    "footer_type": "simple|columns",
    "layout_type": "full-width|boxed",
    "max_width": "1120",
    "spacing": "normal",
    "show_dark_toggle": true,
    "back_to_top": true,
    "header_cta_text": "Texto",
    "header_cta_url": "#"
  },
  "capabilities": {
    "animations": true,
    "parallax": false,
    "scroll_progress": false,
    "cookie_banner": false
  },
  "sections": {
    "footer": "<footer class=\"af-footer\">...HTML do rodapé...</footer>"
  }
}
```
DESIGN,
            'intro' => <<<INTRO
Gera as secções de introdução e apresentação do tema: Hero, Funcionalidades (Features), Testemunhos e Galeria.
Responsável por atualizar: sections.hero, sections.features, sections.testimonials, sections.gallery.
Usa as variáveis CSS globais do tema.
Exemplo de retorno em json_updates:
```json_updates
{
  "sections": {
    "hero": "<section class=\"af-hero\">...HTML...</section>",
    "features": "<section class=\"af-features\">...HTML...</section>",
    "testimonials": "<section class=\"af-testimonials\">...HTML...</section>",
    "gallery": "<section class=\"af-gallery\">...HTML...</section>"
  }
}
```
INTRO,
            'conversion' => <<<CONVERSION
Gera as secções de negócio e conversão do tema: Tabela de Preços (pricing), Chamada à Ação (cta), Perguntas Frequentes (faq) e Contacto (contact).
Responsável por atualizar: sections.pricing, sections.cta, sections.faq, sections.contact.
Usa as variáveis CSS globais do tema.
Exemplo de retorno em json_updates:
```json_updates
{
  "sections": {
    "pricing": "<section class=\"af-pricing\">...HTML...</section>",
    "cta": "<section class=\"af-cta\">...HTML...</section>",
    "faq": "<section class=\"af-faq\">...HTML...</section>",
    "contact": "<section class=\"af-contact\">...HTML...</section>"
  }
}
```
CONVERSION,
            'code' => <<<CODE
Gera o CSS personalizado e (opcional) JS complementar para micro-interações, transições e ajustes responsivos das secções do tema.
Responsável por atualizar: custom_css, custom_js.
Exemplo de retorno em json_updates:
```json_updates
{
  "custom_css": "/* CSS aqui */",
  "custom_js": "/* JS opcional aqui */"
}
```
CODE,
            default => "Gera a tua parte e devolve o json_updates apropriado.",
        };

        return $base . "\n" . $task;
    }

    /** Dispatch one focused LLM call to the active provider. */
    private static function chatDispatch(string $system, array $history, array $attachments, int $maxTokens): string
    {
        $provider = StudioSetting::get('ai_provider', 'claude');
        $model    = StudioSetting::get('ai_model', '');
        $apiKey   = self::resolveApiKey($provider);

        if (empty($apiKey)) {
            throw new RuntimeException('Chave AI não configurada. Vai a Definições → Provedor IA.');
        }

        return match ($provider) {
            'openai' => self::chatOpenAI($apiKey, $model ?: 'gpt-4o', $system, $history, $attachments, $maxTokens),
            'gemini' => self::chatGemini($apiKey, $model ?: 'gemini-2.0-flash', $system, $history, $attachments, $maxTokens),
            default  => self::chatClaude($apiKey, $model ?: 'claude-sonnet-4-6', $system, $history, $attachments, $maxTokens),
        };
    }

    private static function chatClaude(string $key, string $model, string $system, array $history, array $attachments, int $maxTokens = 4096): string
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
        ])->timeout(240)->post('https://api.anthropic.com/v1/messages', [
            'model'      => $model,
            'max_tokens' => $maxTokens,
            'system'     => $system,
            'messages'   => $messages,
        ]);

        if (!$response->successful()) {
            throw new RuntimeException('Claude API error: ' . $response->body());
        }

        return $response->json('content.0.text', '');
    }

    private static function chatOpenAI(string $key, string $model, string $system, array $history, array $attachments, int $maxTokens = 4096): string
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
            ->timeout(240)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model'      => $model,
                'max_tokens' => $maxTokens,
                'messages'   => $messages,
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('OpenAI API error: ' . $response->body());
        }

        return $response->json('choices.0.message.content', '');
    }

    private static function chatGemini(string $key, string $model, string $system, array $history, array $attachments, int $maxTokens = 4096): string
    {
        $contents = [];

        foreach ($history as $i => $msg) {
            $isLast = $i === count($history) - 1;
            $role = $msg['role'] === 'assistant' ? 'model' : 'user';

            if ($isLast && $msg['role'] === 'user' && !empty($attachments)) {
                $parts = [['text' => $msg['content']]];

                foreach ($attachments as $att) {
                    if ($att['type'] === 'image') {
                        $parts[] = [
                            'inlineData' => [
                                'mimeType' => $att['mime'],
                                'data'     => $att['data'],
                            ],
                        ];
                    } elseif ($att['type'] === 'document') {
                        $parts[] = [
                            'inlineData' => [
                                'mimeType' => 'application/pdf',
                                'data'     => $att['data'],
                            ],
                        ];
                    } elseif ($att['type'] === 'text_description') {
                        $parts[] = ['text' => $att['description']];
                    }
                }

                $contents[] = ['role' => $role, 'parts' => $parts];
            } else {
                $contents[] = ['role' => $role, 'parts' => [['text' => $msg['content']]]];
            }
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}";

        $response = Http::timeout(240)->post($url, [
            'contents'          => $contents,
            'systemInstruction' => [
                'parts' => [['text' => $system]],
            ],
            'generationConfig'  => ['maxOutputTokens' => $maxTokens],
        ]);

        if (!$response->successful()) {
            throw new RuntimeException('Gemini API error: ' . $response->body());
        }

        return $response->json('candidates.0.content.parts.0.text', '');
    }

    // ──────────────────────────────────────────────

    private static function call(string $system, string $user, int $maxTokens = 2048): string
    {
        $provider    = StudioSetting::get('ai_provider', 'claude');
        $model       = StudioSetting::get('ai_model', '');
        $temperature = (float) StudioSetting::get('ai_temperature', '0.7');
        $maxTok      = (int) StudioSetting::get('ai_max_tokens', (string) $maxTokens);
        $customInstr = StudioSetting::get('ai_custom_instructions', '');

        // Prepend custom instructions to system prompt
        if (!empty($customInstr)) {
            $system = trim($customInstr) . "\n\n" . $system;
        }

        $apiKey = self::resolveApiKey($provider);

        if (empty($apiKey)) {
            throw new RuntimeException('No AI API key configured. Go to Settings → AI Provider.');
        }

        return match ($provider) {
            'openai'  => self::callOpenAI($apiKey, $model ?: 'gpt-4o', $system, $user, $maxTok, $temperature),
            'gemini'  => self::callGemini($apiKey, $model ?: 'gemini-2.0-flash', $system, $user, $maxTok),
            'claude'  => self::callClaude($apiKey, $model ?: 'claude-sonnet-4-6', $system, $user, $maxTok),
            default   => self::callClaude($apiKey, $model ?: 'claude-sonnet-4-6', $system, $user, $maxTok),
        };
    }

    /**
     * Resolve the decrypted API key for a given provider.
     * Per-provider keys (`ai_api_key_{provider}`) take precedence; falls back to
     * the legacy single `ai_api_key` only when it belongs to the active provider.
     */
    private static function resolveApiKey(string $provider): string
    {
        $perProvider = StudioSetting::get("ai_api_key_{$provider}", '');
        if ($perProvider !== '') {
            try { return decrypt($perProvider); } catch (\Throwable) { return $perProvider; }
        }

        // Legacy single key — only honoured for the currently active provider
        if ($provider === StudioSetting::get('ai_provider', 'claude')) {
            $legacy = StudioSetting::get('ai_api_key', '');
            if ($legacy !== '') {
                try { return decrypt($legacy); } catch (\Throwable) { return $legacy; }
            }
        }

        return '';
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
