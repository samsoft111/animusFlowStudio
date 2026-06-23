---
name: animusflow
description: >
  Build, architect, and extend AnimusFlow — an AI-native CMS SaaS platform for creating intelligent websites globally.
  Use this skill whenever the user mentions AnimusFlow, AnimusFlowStudio, asks to build CMS features, create themes,
  develop plugins, design the admin panel, work on multi-tenancy, integrate AI modules (content generation, chatbot,
  SEO, personalisation, analytics), implement billing/plans, or build the marketplace. Also trigger for database schema,
  API design, Laravel backend, Vue 3 + Inertia admin SPA, Blade site rendering, or any work scoped to either the
  AnimusFlow CMS or the AnimusFlowStudio creator platform.
---

> [!IMPORTANT]
> **Este ficheiro é um apontador.** A documentação técnica completa está em:
> `C:\Users\samso\AntigravityWorkspace\animusFlow\skills\animusflow\SKILL.md`
>
> **Lê sempre esse ficheiro antes de qualquer trabalho no AnimusFlow ou AnimusFlowStudio.**

## Resumo rápido — AnimusFlowStudio

**Path:** `C:\Users\samso\AntigravityWorkspace\animusFlowStudio`  
**Purpose:** Creator platform — visual theme & plugin builder (separado do CMS admin)  
**DB:** MySQL 8.0, database `animusflow_studio`, user `root`  
**URL:** http://127.0.0.1:8001/  
**Credenciais Studio:** test@example.com / password

**Stack:** Laravel 11 + Vue 3 + Inertia + Tailwind v4 + Vite  
**Build:** `cmd /c "npm run build"` (PowerShell tem restrição de execution policy — usar sempre `cmd /c`)

### Ficheiros principais do Studio
| Ficheiro | Propósito |
|----------|-----------|
| `app/Http/Controllers/ThemeController.php` | CRUD + AI + export + publish + LAYOUT_MAP |
| `app/Http/Controllers/PluginController.php` | Plugin CRUD + AI + versioning |
| `app/Http/Controllers/RecipeController.php` | AI Recipe CRUD + export/import + analytics |
| `app/Services/AIEngine.php` | Toda a lógica de IA (chat, geração, categorias, recipe engine) |
| `app/Models/StudioAiRecipe.php` | Modelo de receitas (matchAndResolve + testResolve) |
| `resources/js/Pages/Themes/Edit.vue` | Editor visual 12 abas (incluindo ⚡ Macros) |
| `resources/js/Pages/Plugins/Edit.vue` | Editor plugin 11 abas (incluindo ⚡ Macros) |
| `resources/js/Pages/Recipes/Index.vue` | Lista de receitas com stats e toggle |
| `resources/js/Pages/Recipes/Form.vue` | Formulário de criação/edição de receitas |
| `resources/js/Pages/Recipes/Analytics.vue` | Dashboard de analytics de receitas |
| `resources/views/preview/theme.blade.php` | Preview (auth): Tailwind + custom_css/js + `Blade::render` das secções + Modo Edição |
| `routes/web.php` | Todas as rotas do Studio |
| `tests/` | Scripts de teste manuais (php tests/xxx.php) |

### Configurações do layout — LAYOUT_MAP (Studio → AnimusFlow CMS)
```php
private const LAYOUT_MAP = [
    'header_type'      => 'layout_header_bg',        // glass / solid / transparent
    'nav_position'     => 'layout_header_menu',       // left / center / right
    'max_width'        => 'layout_content_max_width', // 960 / 1120 / 1280 / 1440 / full
    'spacing'          => 'layout_content_spacing',   // compact / normal / spacious
    'show_dark_toggle' => 'layout_header_show_toggle',// bool → '1'/'0'
    'header_sticky'    => 'layout_header_sticky',     // bool → '1'/'0'
    'header_cta_text'  => 'layout_header_cta_text',  // string
    'header_cta_url'   => 'layout_header_cta_url',   // string
    'footer_copyright'     => 'layout_footer_copyright', // string
    'menu_layout'          => 'layout_menu_layout',       // circular / normal
    'normal_menu_position' => 'layout_normal_menu_position', // horizontal-right / horizontal-left / vertical-left / vertical-center / vertical-right
    'back_to_top'          => null,                       // via capabilities
];
```

### Padrão de tema avançado — AeroSpace
- `menu_layout: 'circular'` → menu orbital com HOME no centro (cockpit circular)
- `menu_layout: 'normal'` → barra normal clássica (horizontal ou vertical)
- `normal_menu_position` → 5 posições/orientações para o menu normal:
  - `horizontal-right` (barra horizontal, links alinhados à direita)
  - `horizontal-left` (barra horizontal, links alinhados à esquerda)
  - `vertical-left` (sidebar vertical fixa à esquerda)
  - `vertical-right` (sidebar vertical fixa à direita)
  - `vertical-center` (sidebar vertical flutuante centrada no ecrã)
- Em Blade CMS usar **sempre** `$layout['menu_layout']` e `$layout['normal_menu_position']` — **nunca** `$theme->layout_config[...]`
- Ver documentação completa em `references/theme-development.md` Secção 10

### Preview de tema no Studio — pipeline de rendering
`resources/views/preview/theme.blade.php` (rota `themes.preview`, **`->middleware('auth')`**) reflecte o tema real:
- **Tailwind Play CDN** + `tailwind.config` que mapeia `font-heading`/`font-body` → `var(--font-*)`. Permite as classes utilitárias usadas pelos temas (`py-24`, `bg-[#070C18]`, `md:grid-cols-2`, valores arbitrários `[#...]`).
- **`custom_css`** injectado em `<style id="af-theme-custom-css">` (a seguir aos estilos default, para os poder sobrepor).
- **`custom_js`** injectado em `<script id="af-theme-custom-js">` no fim do `<body>` (depois do editor de tokens).
- **Secções compiladas via `Blade::render($html, ['theme' => $theme], deleteCachedView: true)`** (com try/catch) — `@if`/`@foreach`/`{{ }}` são resolvidos. As secções **são Blade** (o export grava cada uma como `sections/{tipo}.blade.php`). Erro de compilação → fallback gracioso (comentário `<!-- Blade render error -->` + HTML cru, sem rebentar a página).
- ⚠️ **A rota é auth-only precisamente porque compila Blade armazenado server-side** — torná-la pública seria RCE. `StudioTheme` não tem `user_id` (Studio mono-utilizador), por isso exigir login é o controlo suficiente; **nunca** reverter para rota pública.
- Secções com default no preview: `hero`, `features`, `testimonials`, `pricing`, `cta`. Qualquer outra chave (`about`, `stats`, `footer`, `ai_*`, …) é renderizada no `@foreach` final, pela ordem de inserção no JSON; chaves `ai_*` vazias têm mockups de fallback.

### Convenções do bloco `json_updates` de um tema
- HTML/CSS/JS dentro dos valores de string: **escapar sempre as aspas como `\"`**. Aspas cruas (`class="..."`) quebram o JSON — mesmo com o parser tolerante a string fica truncada no 1.º `"`. Ao gerar secções novas, produzir os valores com `json_encode`/`json.dumps` (escape garantido).
- Chaves do bloco: `label`, `description`, `version`, `status` (`draft` → só promover depois de validar no preview), `colors.{light,dark}`, `fonts`, `layout_config`, `capabilities`, `sections`, `custom_css`, `custom_js`.
- HUD com `hud_bg_type:"video"`: o `<video>` do screensaver não tem `poster` — dar **fallback CSS** no `.screensaver-container` (gradiente animado, ex.: `@keyframes` de nebulosa) para degradar quando o `.mp4`/imagem faltar. Os assets referenciados (`/videos/...`, `/images/...`) têm de existir no destino.

---

## Receitas IA — Rotas do Studio

| Método | Rota | Nome | Controller |
|--------|------|------|------------|
| GET | /recipes | recipes.index | RecipeController@index |
| GET | /recipes/create | recipes.create | RecipeController@create |
| POST | /recipes | recipes.store | RecipeController@store |
| GET | /recipes/analytics | recipes.analytics | RecipeController@analytics |
| GET | /recipes/{id}/edit | recipes.edit | RecipeController@edit |
| PUT | /recipes/{id} | recipes.update | RecipeController@update |
| DELETE | /recipes/{id} | recipes.destroy | RecipeController@destroy |
| POST | /recipes/{id}/toggle | recipes.toggle | RecipeController@toggle |
| POST | /recipes/{id}/test | recipes.test | RecipeController@test |
| POST | /recipes/import | recipes.import | RecipeController@import |
| GET | /recipes/export | recipes.export | RecipeController@export |
| GET | /themes/{uuid}/recipes | themes.recipes | ThemeController@recipes |
| GET | /plugins/{uuid}/recipes | plugins.recipes | PluginController@recipes |

---

Para toda a documentação restante (modelos, rotas, AI engine, MCP, plugin system, billing, block types, etc.)  
→ **consulta o ficheiro principal:** `C:\Users\samso\AntigravityWorkspace\animusFlow\skills\animusflow\SKILL.md`
