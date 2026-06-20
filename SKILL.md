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
| `app/Services/AIEngine.php` | Toda a lógica de IA (chat, geração, categorias) |
| `resources/js/Pages/Themes/Edit.vue` | Editor visual 12 abas (4085+ linhas) |
| `resources/js/Pages/Plugins/Edit.vue` | Editor plugin 11 abas |
| `resources/views/preview/theme.blade.php` | Iframe de preview + Modo Edição overlay |
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
    'footer_copyright' => 'layout_footer_copyright', // string
    'menu_layout'      => 'layout_menu_layout',       // circular / normal (In9vador)
    'back_to_top'      => null,                       // via capabilities
];
```

### Padrão de tema avançado — In9vador
- `menu_layout: 'circular'` → menu orbital com HOME no centro
- `menu_layout: 'normal'` → barra horizontal clássica
- Em Blade CMS usar **sempre** `$layout['menu_layout']` — **nunca** `$theme->layout_config['menu_layout']`
- Ver documentação completa em `references/theme-development.md` Secção 10

---

Para toda a documentação restante (modelos, rotas, AI engine, MCP, plugin system, billing, block types, etc.)  
→ **consulta o ficheiro principal:** `C:\Users\samso\AntigravityWorkspace\animusFlow\skills\animusflow\SKILL.md`
