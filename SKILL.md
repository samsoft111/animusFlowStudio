---
name: animusflow
description: >
  Build, architect, and extend AnimusFlow — an AI-native CMS SaaS platform for creating intelligent websites globally.
  Use this skill whenever the user mentions AnimusFlow, AnimusFlowStudio, asks to build CMS features, create themes,
  develop plugins, design the admin panel, work on multi-tenancy, integrate AI modules (content generation, chatbot,
  SEO, personalisation, analytics), implement billing/plans, or build the marketplace. Also trigger for database schema,
  API design, Laravel backend, Vue 3 + Inertia admin SPA, Blade site rendering, or any work scoped to either the
  AnimusFlow CMS or the AnimusFlowStudio creator platform.
  This skill contains the full product vision, architecture, naming conventions, tech stack decisions, and implementation
  patterns — always read it before writing any AnimusFlow code or making architectural decisions.
---

## ⚠️ READ THIS FIRST — Development environment (Windows)

### Project roots — TWO separate Laravel apps

| Project | Path | Purpose |
|---------|------|---------|
| **AnimusFlow Core (CMS)** | `C:\Users\samso\AntigravityWorkspace\animusFlow\core` | The CMS engine — sites, pages, blocks, AI, plugins |
| **AnimusFlowStudio** | `C:\Users\samso\AntigravityWorkspace\animusFlowStudio` | Creator platform — visual theme & plugin builder |

**Project root (CMS):** `C:\Users\samso\AntigravityWorkspace\animusFlow\core`

**PHP binary (8.2.31):**
```
C:\Users\samso\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.2_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe
```
Installed via WinGet. **Do NOT use XAMPP** — it has PHP 5.3 and is incompatible.

**Start the development server:**
```powershell
# IMPORTANT: artisan serve fails on this machine ("Failed to listen — reason: ?")
# Use php -S directly with the custom router instead:
$php = "C:\Users\samso\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.2_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe"
Set-Location "C:\Users\samso\AntigravityWorkspace\animusFlow\core"
& $php -S 127.0.0.1:8000 -t public public/server.php
# Run with run_in_background: true so static files (JS/CSS) are served correctly
```

The `public/server.php` router is critical — without it, ALL requests (including JS/CSS) go through Laravel and assets return HTML 404s.

Or use the pre-made batch file at the repo root:
```
C:\Users\samso\AntigravityWorkspace\animusFlow\serve.bat
```
Double-click or run from terminal — it sets the correct directory automatically.

**Admin URL (CMS):** http://127.0.0.1:8000/admin/  
**Admin login (CMS):** samsoft111@gmail.com / Admin@1234

### AnimusFlowStudio dev server
```powershell
# Studio runs as a standard Laravel + Vite app
$php = "C:\Users\samso\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.2_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe"
Set-Location "C:\Users\samso\AntigravityWorkspace\animusFlowStudio"
& $php artisan serve   # or use php -S if artisan serve fails (same issue as CMS)
# In a second terminal:
npm run dev
```
**Studio URL:** http://127.0.0.1:8001/ (or whichever port artisan assigns)  
**Studio database:** MySQL 8.0, database `animusflow_studio`, user `root`

**Run artisan commands:**
```powershell
$php = "C:\Users\samso\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.2_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe"
Set-Location "C:\Users\samso\AntigravityWorkspace\animusFlow\core"
& $php artisan migrate
& $php artisan tinker
& $php artisan route:list
```

**Database:** MySQL 8.0 on 127.0.0.1:3306, database `animusflow`, user `root`  
**Storage symlink** (needed for media uploads — run once):
```powershell
& $php artisan storage:link
```

**Test suite:** `& $php artisan test` → **212/212 passing, 577 assertions** (as of 2026-06-19)

---

# AnimusFlow — AI-Native CMS

## Product identity

**AnimusFlow** is a global AI-native CMS SaaS. "Animus" = intelligence and intent. "Flow" = fluidity and speed.

- **Tagline**: "The AI-native CMS. Build smarter websites in minutes."
- **Model**: Open-source core (MIT) + Premium SaaS plans
- **Audience**: Agencies, startups, SMEs, independent developers — globally
- **Key differentiator**: AI is in the core, not a plugin. Every site built on AnimusFlow thinks, learns, and adapts.

### Plans
| Plan | Target | Price |
|------|--------|-------|
| Free (open-source) | Developers, self-hosted | $0 |
| Pro | Freelancers, small agencies | $29/mo |
| Agency | Multi-client agencies | $79/mo |
| Enterprise | White-label, large orgs | Custom |

---

## Current implementation status (Phase 4 — as of 2026-05-28)

### What is built and working

- **Admin panel** — Vue 3 + Inertia SPA (Phase 4 complete as of 2026-05-26); login page remains Blade; public site rendering remains Blade
- **Page CRUD** — create, edit, publish, delete pages with UUID routing
- **Block editor** — 59 block types; AI generation from prompt; overlay editor v2
- **Theme system** — ThemeManager service; activate themes; AI generation + ZIP upload; AI-generated themes correctly use their own layout + sections
- **Plugin system** — PluginManager service; enable/disable + AI generation + ZIP upload; `PluginManager::dispatch()` fires hooks on active plugins; `page.render` wired in PageRenderController; `content.publish` wired on publish
- **AI providers** — store/remove Claude, OpenAI, Gemini API keys with encryption; all three are fully wired in `AIEngine` for generation **and** chat (`callGemini` + `callGeminiChat` added)
- **SEO** — SeoMetadata per page; AI auto-generated on publish (PageObserver) + manual override; rendered in `<head>` with OG/schema_json/keywords
- **Navigation** — nav_links JSON setting; rendered in theme header
- **Appearance** — 24 layout_* settings for header/content/footer/style; full visual configurator with card-based selectors, live preview iframe, brand color pickers, font family picker, shape selector, media background with overlay
- **Settings** — site_name, site_description, site_url, site_favicon, site_language, site_homepage_url; themes and plugins have moved to the Extensions page
- **Extensions page** — `/admin/extensions?tab=themes|plugins`; dedicated page with card grid for all installed themes and plugins; AI generator + ZIP upload per tab; theme cards show mini-site mock preview; plugin cards show hooks chips + enable/disable toggle
- **Setup wizard** — DB + admin user creation on first install
- **Public rendering** — `/p/{slug}` and `/preview/{uuid}` routes; root `/` redirects to `/p/home` (or first published page); `$segment` always computed and passed to all blade views
- **Media library** — upload images/PDF/video to public disk; grid view with copy-URL; admin sidebar link
- **Overlay editor v2 (Elementor-style)** — `public/editor/overlay.js`; activates via `?edit` or `af_editor_active` localStorage; features: sidebar field editor panel, drag-and-drop reordering, "Add block" buttons (type picker modal with 6 categories: Hero & Layout, Content, Media, Social, Conversion, Specialized, Social Media, AI Blocks, Interactive, Integrations), duplicate/delete blocks; live DOM preview; saved via `POST /api/v1/pages/{uuid}/blocks`; **saveAll fix**: always merges `dataset.blockContent` keys not already in extracted content (preserves arrays like `items[]`, `segments`, etc.)
- **API v1** — Sanctum auth + pages CRUD + blocks save + settings read/update; `POST /api/v1/events` visitor tracking (public, throttled 120/min)
- **Billing (Stripe)** — full checkout flow, webhook handler (HMAC-verified), customer portal; `Subscription` model; Free ($0), Pro ($29/mo), Agency ($79/mo) plans; Stripe price IDs configurable via Settings; `POST /stripe/webhook` is intentionally outside CSRF middleware
- **Plugin settings UI** — `AdminPluginSettingController` renders a dynamic settings form from the `settings[]` array declared in `animusflow-plugin.json`; field types: `text`, `textarea`, `color`, `select`, `toggle`; route: `GET /admin/plugins/{slug}/settings`
- **Chatbot RAG** — `POST /api/v1/chat` (public, throttled 30 req/min); `chatbot_enabled` setting; `AIEngine::generateChatReply()` accepts site context; `UpdateChatbotContext` job rebuilds context on every page publish; sessions + messages stored in `chatbot_sessions` + `chatbot_messages` tables; admin page at `/admin/chatbot` shows all conversations; chatbot widget passes `visitor_id` cookie
  - **Vector DB RAG (Pinecone)** — `App\Services\PineconeService` (`isEnabled`, `generateEmbedding`, `upsert`, `query`, `deletePageVectors`); when `pinecone_enabled` is on (af-chatbot settings `pinecone_enabled` + `pinecone_api_key`), `UpdateChatbotContext` chunks page text (500/100 overlap) → embeds → upserts per page; `ChatController` embeds the query and pulls topK matches; **graceful fallback** to the legacy `chatbot_site_context` text summary when Pinecone is disabled or errors
- **Analytics + AI Insights** — visitor tracking JS in default theme layout sends `pageview` events to `/api/v1/events` → stored in `visitor_events` table; `insights:generate` artisan command calls `AIEngine::generateInsights()`, stores in `ai_insights` table; scheduled weekly (Monday 08:00); dashboard shows 7-day pageview chart, top pages, latest AI insight, chatbot session count
- **Personalisation Engine** — 4 built-in segments (`first_visit`, `returning`, `high_intent`, `mobile`) seeded in `personalisation_segments`; `VisitorSegmentResolver::resolve(visitorId)` classifies based on `visitor_events`; `PageRenderController` resolves segment from `af_vid` cookie and calls `ContentBlock::resolvedContent(segment)` to serve variants; admin variant UI at `/admin/pages/{uuid}/variants` (🎯 button in page edit topbar); `ContentVariant` stores per-block per-segment content overrides
- **Marketplace** — `/admin/marketplace`; local JSON registry at `storage/app/marketplace.json` (12 plugins + themes); search + type filter; one-click install (downloads ZIP or creates stub for demo); AI Prompt-to-install (`POST /admin/marketplace/prompt-install`): user types "instala plugin de e-commerce" → `AIEngine::interpretMarketplacePrompt()` identifies best match → auto-download + install; keyword fallback when AI unavailable; sidebar link 🛒 Marketplace
- **Full-page generation** — dashboard "Generate page" prompt bar; `POST /admin/pages/generate-from-prompt` → `AIEngine::generateFullPage()` returns `{title, slug, blocks[]}` → saves as draft → redirects to edit screen
- **Flutter companion app** — `animusFlow/app/` (separate project); runs with `flutter run`; screens: Login, Pages list, Page detail, Create page; uses API v1
- **WordPress Migration Tool** — `/admin/migration` (sidebar link 🔄 WP Migration); drag-and-drop WXR file upload; AJAX preview + full import; `App\Services\WordPressMigrator` converts WXR XML → AnimusFlow pages; HTML-to-blocks conversion; Gutenberg comment stripping; Yoast/RankMath/AIOSEO SEO meta import; 50 MB upload limit
- **Installer / Distribution Package** — `public/install.php` standalone pre-Laravel installer; checks 9 system requirements; AJAX DB test; writes `.env`; runs migrations; creates admin; seeds segments + default pages; `build-dist.php` at workspace root creates ZIP for distribution

- **26 new block types (Phase 3.5 — 2026-05-25):**
  - *Social Media (6):* `social_links`, `social_feed`, `social_share`, `social_counters`, `social_proof_bar`, `review_widget`
  - *AI Blocks (6):* `ai_chatbox`, `ai_recommendations` (segment-aware), `ai_summary`, `ai_faq`, `ai_search`, `ai_personalized`
  - *Interactive (14):* `form_builder` (→ POST /submit-form), `popup`, `sticky_cta`, `file_download`, `before_after`, `data_table`, `events_list`, `anchor_nav`, `cookie_banner`, `product_card`, `job_listing`, `rating_widget` (→ POST /api/v1/ratings), `survey` (→ POST /api/v1/surveys/{uuid}/vote), `media_kit`
  - New API endpoints: `POST /submit-form`, `GET /api/v1/search`, `POST /api/v1/ratings`, `GET /api/v1/ratings/{blockUuid}`, `POST /api/v1/surveys/{blockUuid}/vote`
  - New models: `FormSubmission`, `BlockRating` (with `averageFor()`), `SurveyVote` (with `totalsFor()`)
  - New controllers: `FormSubmissionController`, `SearchController`, `RatingController`, `SurveyController`

- **MCP Block system (Phase 3.6 — 2026-05-25):**
  - Block type `mcp_block` — connects any page block to any MCP-compatible service (Claude, GitHub, Open-Meteo, custom APIs…)
  - `App\Services\MCP\HttpClient` — full MCP Streamable HTTP transport client (spec 2024-11-05); stateless-first with initialize handshake fallback; session ID (Mcp-Session-Id) handled
  - `McpConnection` model + `mcp_connections` table — stores server URL + encrypted auth token; `authHeaders()`, `setToken()`, `decryptedToken()`, `scopeActive()`
  - `AdminMcpController` — full CRUD + probe (list tools + cache) + ping (test without saving)
  - `Api\V1\McpController` — `GET /api/v1/mcp/connections` (sanctum auth, for editor select), `POST /api/v1/mcp/invoke` (public, rate-limited, whitelist gate via connection_uuid)
  - Security: browser sends `connection_uuid + tool_name + arguments` only; server looks up URL + auth from DB — browser never sees credentials
  - Admin UI: `/admin/mcp` (list + probe), `/admin/mcp/create` + `/admin/mcp/{uuid}` (form with live test)
  - Admin sidebar: 🔌 MCP link (between Extensions and Chatbot)
  - Output layouts: `text` (markdown-lite), `list`, `cards`, `table`, `raw` (JSON debug)
  - Editor: `buildMcpBlockFields()` loads connections via AJAX, populates tool select on connection change; `input_params` JSON textarea; layout select

**Total block types: 59** (theme.json v1.3.0)  
Block categories in overlay.js: Hero & Layout, Content, Media, Social, Conversion, Specialized, 📱 Social Media, 🤖 AI Blocks, ⚡ Interactive, 🔌 Integrations

### Recently built (Phase 5 — 2026-06-19)
- **Multi-tenancy** — `IdentifyTenant` middleware (registered in `bootstrap/app.php`) switches the DB connection per tenant; `TenantManager` handles onboarding + provisioning; suspended tenants return 503; covered by `tests/Feature/Tenant/TenancyTest.php`
- **Dynamic / cloud storage disk** — `TenantManager::configureStorageDisk()` applies the disk from the `media_storage_disk` setting (S3 / Cloudflare R2 / MinIO via `AWS_ENDPOINT` + `AWS_URL`); `AdminMediaController` uploads to the configured disk
- **Vector DB RAG (Pinecone)** — see Chatbot RAG above
- **Gemini chat provider** — `AIEngine::callGemini` + `callGeminiChat`
- **Flutter media library** — `Api\V1\MediaController` (GET/POST/DELETE `/api/v1/media`) + Flutter `media_screen` / `home_shell`

### Not yet built — CMS Core (roadmap Phase 6+)
- AppyPay billing — Stripe is implemented; AppyPay is a future payment method
- Headless API / Next.js rendering
- Live marketplace sync from animus.kwantoe.com API (currently local JSON registry)
- Flutter: block editor, AI page generation, push notifications

---

## AnimusFlowStudio — Creator Platform (separate Laravel app)

**Path:** `C:\Users\samso\AntigravityWorkspace\animusFlowStudio`  
**Purpose:** A visual SaaS platform where designers and developers create, edit, and publish themes and plugins for the AnimusFlow marketplace. Creators log in here, build assets visually, then publish to the marketplace — separate from the CMS admin panel.

### What is built (as of 2026-06-18)

- **Auth** — Login/logout via `LoginController`; all routes behind `auth` middleware
- **Dashboard** — `DashboardController` → `Dashboard.vue`
- **About** — `AboutController` → `About.vue`
- **Settings** — `SettingsController` → `Settings.vue`; 6 setting groups

**Themes module** — full CRUD + 12-tab visual editor:

| Tab | Feature |
|-----|---------|
| Configurações | Name, slug, description, version, author, category, tags, license, preview image |
| Assets | Background images/videos with groups; overlay controls |
| Secções | Block library by category; drag-and-drop section ordering |
| Componentes | Visual component library with drag-and-drop |
| Variantes | 18 pre-defined colour palettes + full custom editor |
| Tipografia | Font pairs, scale, weight |
| Animações | Entry animations, scroll effects |
| SEO | Meta title template, OG settings |
| Código | Custom CSS/JS injection |
| Preview | Live iframe preview + **Modo Edição** (CSS token inspector) |
| Chat IA | Multimodal AI assistant — text + images/PDFs/audio/video |
| 🕐 Versões | Version history timeline — manual/auto/publish snapshots + restore |

Additional theme actions (routes):
- `GET /themes/{uuid}/export` — download ZIP (cross-platform, forward-slash paths)
- `GET /themes/{uuid}/export-prompt` — AI-ready `.afprompt` file with `[AF:THEME:BEGIN]...[AF:THEME:END]` + sha256 checksum
- `POST /themes/{uuid}/generate-ai` — AI generation per tab
- `POST /themes/{uuid}/publish` — publish to Marketplace (animus.kwantoe.com); sets `is_published`, `status=published`, `animus_package_uuid`
- `POST /themes/{uuid}/install-in-cms` — push ZIP directly to a local CMS instance via `POST /api/v1/studio/install-theme`
- `POST /themes/{uuid}/chat` — multimodal AI chat for theme design
- `POST /themes/{uuid}/upload-asset` — upload image/video asset
- `DELETE /themes/{uuid}/asset` — remove asset
- `GET /preview/theme/{uuid}` — **public** live preview (no auth, used in editor iframe)
- `POST /themes/inspire` — **category-based AI theme generation** (BEFORE {uuid} routes); returns `{success, theme_uuid, preview_url, edit_url, label, inspiration, colors}`

**Inspiração por Categoria** (`ThemeController::inspire`, `AIEngine::generateThemeFromCategory`):
- Button "✦ Inspiração por Categoria" in `Index.vue` (top-right + empty state)
- Modal with **25 site categories** and **5 visual styles**:
  - Categories: 🛒 E-commerce, 🍽️ Restaurante, 💼 Agência, 🎨 Portfolio, 📝 Blog, 🏨 Hotel, 🚀 SaaS, 💪 Fitness, 🏥 Clínica, 🎵 Música/Eventos, 🏠 Imobiliário, 🎓 Educação, 📸 Fotografia, 🎮 Gaming, 💄 Beleza, 💻 Tecnologia, 🛡️ Seguros, ⚖️ Jurídico, 📊 Consultoria, 🏗️ Construção, 🚚 Transporte, ✈️ Viagens, 🤝 ONG/Social, 👗 Moda, 🍳 Gastronomia
  - Styles: ⬜ Minimalista, ✦ Moderno, 💎 Elegante, ⚡ Arrojado, 🌈 Colorido
- Modal flow: `select` → `loading` (animated dots) → `result` (iframe preview + color palette swatches) → `error`
- Result step: label, inspiration text, live iframe preview, 8-color palette, "Usar como base →" (edit URL) + "Gerar outra versão"
- `AIEngine::generateThemeFromCategory(string $category, string $style): array` — prompt instructs AI to reference real websites of the category; returns complete theme spec: colors (light+dark CSS vars), fonts (Google Fonts), layout_config, capabilities, sections HTML (using CSS vars), custom_css, inspiration text
- Controller creates a StudioTheme draft in DB and returns full JSON (including colors) in one call — no second fetch needed
- Category grid scrollable (max-h-64 overflow-y-auto) to accommodate all 25 categories

**Chat IA — Multimodal AI Theme Assistant** (`ThemeController::chat`, `AIEngine::chatTheme`):
- Tab "💬 Chat IA" in `Edit.vue` — scrollable message list, quick-prompt pills, drag-drop attachments
- Sends last 20 turns as `history[]` + current message + up to 5 files (20 MB each)
- File routing: `image/*` + `application/pdf` → base64 content blocks (native Claude/OpenAI vision); `audio/*` + `video/*` → descriptive text for design inspiration; other → text preview up to 2000 chars
- AI response contains optional ` ```json_updates ``` ` block parsed by regex — stripped from visible reply
- **Deep-merge**: AI only needs to specify changed keys; controller calls `array_replace_recursive($existing, $updates[$field])` for `colors`, `layout_config`, `capabilities`, `fonts`, `assets`
- **Bidirectional sync**: after chat applies changes, `form` in Vue is updated with spread-operator deep-merge (not full replace) — `form.colors.dark` is preserved when AI only touches `form.colors.light`
- `AIEngine::chatTheme(array $history, string $themeJson, array $attachments): array` — returns `['reply', 'updates']`
- `AIEngine::chatClaude(...)` — builds Claude messages with `image`/`document` content blocks; includes `'anthropic-beta' => 'pdfs-2024-09-25'`
- `AIEngine::chatOpenAI(...)` — uses `image_url` blocks with base64 data URI

**Modo Edição — CSS Token Inspector** (visual in-preview editor):
- "✏️ Modo Edição" toggle button in Preview tab toolbar (`Edit.vue`)
- When activated: sends `af-enable-edit` + `af-apply-vars` postMessage to iframe with current `form.colors.light` + `form.fonts` vars
- In `resources/views/preview/theme.blade.php`: full overlay script activated by postMessage from parent
  - Hover: dashed outline + tooltip (`tag.class`)
  - Click: slide-in inspector panel (280px right) showing element CSS vars + all theme tokens
  - Color vars → native `<input type=color>` + hex text input; font vars → text input
  - Live `setProperty()` on `:root` + `postMessage({type:'af-token-change', var, value})` to parent
  - "💾 Guardar tema" button → `postMessage({type:'af-save-request'})` → parent calls `save()`
  - Auto-activates with `?edit=1` in URL; sends `af-ready` on load
- In `Edit.vue`: `handlePreviewMessage(e)` maps incoming vars:
  - `--font-heading` / `--font-body` → `form.fonts`
  - `--color-*` → `form.colors.light[varName]`
  - `af-save-request` → `save()`
  - `af-ready` with editMode ON → re-sends enable + vars (handles iframe reloads)
- Toast "✦ Token actualizado — Guarda para persistir" fades in/out on each change
- `onMounted`/`onUnmounted` wire/unwire `window.addEventListener('message', handlePreviewMessage)`

**Export system** — `ThemeController` private methods:
- `buildThemeZip(StudioTheme)` — creates temp dir, writes `theme.json` (with layout + capabilities + blocks), `layout.blade.php` (with injected colors + fonts), `page.blade.php`, `sections/*.blade.php`, `components/*.blade.php`, `custom.css` (optional minify), `custom.js`, `assets/`, `README.md`; **ZIP paths normalised to forward slashes** (Windows fix: `str_replace('\\', '/', $tmpDir)`)
- `mapStudioColors(array $colors): array` — maps 10 Studio tokens (`--color-primary` → `--primary`, `--color-background` → `--bg`, etc.) to AnimusFlow token names; handles light + dark; passes `--color-warning` as `--warning` extra var
- `mapStudioLayout(array $layoutConfig): array` — maps Studio layout fields to AnimusFlow setting keys (`header_type` → `layout_header_bg`, `max_width` → `layout_content_max_width`, booleans → `'1'/'0'` strings)
- `injectColors(string $layout, array $colors): string` — splits at `[data-theme="dark"]`, replaces light/dark vars independently via `replaceCssVar()`
- `injectFonts(string $layout, array $fonts): string` — builds Google Fonts URL and injects `<link>` before `</head>`
- `exportPrompt()` — generates `.afprompt` file with `af_install` payload ready for AnimusFlow import; includes `af_settings` (brand colors, font family, layout settings, capabilities); checksum sha256

**Token map (Studio → AnimusFlow):**
```
--color-primary     → --primary
--color-secondary   → --primary-h
--color-accent      → --accent
--color-background  → --bg
--color-foreground  → --text
--color-card        → --bg-subtle
--color-muted       → --bg-muted
--color-border      → --border
--color-success     → --success
--color-destructive → --danger
--color-warning     → --warning (extra, not in AnimusFlow default)
```

**Font family map (Studio → AnimusFlow enum):**
```
Inter → inter | Poppins → poppins | DM Sans → dm-sans | Outfit → outfit
Plus Jakarta Sans → plus-jakarta | Playfair Display → playfair
Fraunces → fraunces | Sora → sora | (unknown) → inter (fallback)
```

**Theme versioning** (`StudioThemeVersion` model, `studio_theme_versions` table):
- Each version is a **full snapshot** of the theme state: `version`, `label`, `changelog`, `snapshot_type`, plus `colors`, `fonts`, `sections`, `layout_config`, `capabilities`, `assets`, `components`, `variants`, `custom_css`, `custom_js`, `description`
- `StudioThemeVersion::snapshot(StudioTheme $theme, string $changelog = '', string $type = 'manual'): self` — uses `getRawOriginal()` to capture JSON columns verbatim; auto-generates `uuid` on create
- **Snapshot types:** `manual` 📌 (created by the user) · `auto` ⚡ (created automatically before each restore) · `publish` 🚀 (created automatically when publishing to the Marketplace)
- Restore **always creates an auto-snapshot of the current state first** (`changelog = "Antes de restaurar v{n}"`) so a restore is itself reversible
- `StudioTheme::versions()` — HasMany relation
- Routes:
  - `GET /themes/{uuid}/versions` → `ThemeController::listVersions` (name `themes.versions.list`)
  - `POST /themes/{uuid}/versions` → `ThemeController::createVersion` (name `themes.versions.create`) — `{success, version}`
  - `POST /themes/{uuid}/versions/{versionUuid}/restore` → `ThemeController::restoreVersion` (name `themes.versions.restore`) — `{success, message, theme}`
  - `DELETE /themes/{uuid}/versions/{versionUuid}` → `ThemeController::deleteVersion` (name `themes.versions.delete`)
- UI: `Themes/Edit.vue` 🕐 Versões tab — timeline of snapshots, `loadVersions()`, `saveVersion()`, `restoreVersion()`, `deleteVersion()`; `themeVersions` ref; create-snapshot modal

**Test scripts** (run with `php tests/<file>.php`):
- `tests/bidir_flow_test.php` — 55 assertions; verifies Chat↔Manual bidirectional flow, deep-merge preservation
- `tests/modo_edicao_test.php` — 102 assertions; verifies Modo Edição overlay, postMessage protocol, CSS var mapping, Edit.vue bridge
- `tests/export_test.php` — 117 assertions; verifies ZIP structure, token mapping, injectColors, injectFonts, installInCms, publish marketplace
- `tests/inspire_category_test.php` — 168 assertions (15 blocks); verifies AIEngine::generateThemeFromCategory system prompt + mock return, ThemeController::inspire validation + BD integration + error handling, route position/auth, all 25 categories + 5 styles in Index.vue, modal steps/animations/imports, Vite build
- `tests/theme_version_test.php` — 49 assertions; verifies StudioThemeVersion::snapshot, ThemeController listVersions/createVersion/restoreVersion/deleteVersion, auto-snapshot before restore, publish snapshot wiring, routes, Themes/Edit.vue Versões tab
- `tests/plugin_test.php` — 190 assertions; verifies buildPluginZip, exportPrompt, installInCms, publish, **plugin versioning** (save/list/snapshot/compare/restore), routes, Chat IA, Plugins/Edit.vue, CMS integration

**Plugins module** — CRUD + editor:
- Create, edit, delete plugins
- `POST /plugins/{uuid}/chat` — multimodal AI chat for plugin development (same protocol as themes)
- `POST /plugins/{uuid}/generate-ai` — AI-assisted plugin generation (quick single-prompt)
- `POST /plugins/{uuid}/publish` — publish to marketplace
- `GET /plugins/{uuid}/export` — download ZIP

**Plugin creation flow** — same as themes: `GET /plugins/create` auto-creates a draft and redirects to editor (no separate creation form).

**Plugin editor — 11 tabs**:

| Tab | Feature |
|-----|---------|
| Detalhes | Label, version, description, author, category, license, tags, status |
| Hooks | Checkbox selector for page.render / content.publish / admin.sidebar |
| PHP | Plugin.php editor with scaffold generator |
| Widget | widget.blade.php + widget.js editors |
| CSS | plugin.css editor |
| Configurações | Settings schema builder (key, label, type, default, placeholder, hint) |
| Docs | README.md editor |
| ✨ IA | Quick AI generator (single prompt → PHP + widget + schema) |
| 💬 Chat IA | Multimodal AI assistant — text + images/PDFs/audio/video |
| 📦 Versões | Version history timeline + diff viewer + restore |
| Exportar | Status checklist + ZIP download + Install no CMS + Publish marketplace |

**Chat IA (plugins)** — `PluginController::chat`, `AIEngine::chatPlugin`:
- Same multimodal protocol as `chatTheme` — last 20 turns + up to 5 files (20 MB each)
- System prompt focused on PHP plugin development, AnimusFlow hooks, widget Blade/JS
- AI response contains optional ` ```json_updates ``` ` block with plugin field changes
- Applied fields: `plugin_php`, `widget_blade`, `widget_js`, `custom_css`, `settings_schema`, `hooks`, `label`, `description`, `version`, `status`
- Vue: `applyChatUpdates()` does direct assignment (no deep-merge needed for plugin fields)
- Quick prompts: "Cria um plugin de barra de anúncio", "Gera um PHP scaffold para page.render", etc.

**Plugin versioning** (`StudioPluginVersion` model, `studio_plugin_versions` table):
- Each version snapshots the fields listed in `StudioPluginVersion::$snapshotFields` (`plugin_php`, `widget_blade`, `widget_js`, `custom_css`, `settings_schema`, `hooks`, …; **not** `uuid`)
- Auto-snapshot is wired into publish via `saveVersionSnapshot()`
- Routes:
  - `GET /plugins/{uuid}/versions` → `PluginController::versions` (name `plugins.versions.list`)
  - `POST /plugins/{uuid}/versions` → `PluginController::saveVersion` (name `plugins.versions.save`) — rejects duplicate version with **422**
  - `GET /plugins/{uuid}/versions/{versionId}` → `PluginController::versionSnapshot` (name `plugins.versions.snapshot`)
  - `POST /plugins/{uuid}/versions/{versionId}/restore` → `PluginController::restoreVersion` (name `plugins.versions.restore`)
  - `POST /plugins/{uuid}/versions/compare` → `PluginController::compareVersions` (name `plugins.versions.compare`) — returns field-level `diff` + `changed` count
- UI: `Plugins/Edit.vue` 📦 Versões tab — timeline, `bumpVersion()`, `createVersion()`, `restoreToVersion()`, `selectForCompare()`, `runCompare()`, `viewSnapshot()`; diff viewer (2-col grid); snapshot modal
- ⚠️ Difference vs theme versioning: plugins support **diff/compare between two versions**; themes do not (restore + delete only). Plugins reject duplicate version numbers; theme `createVersion` does not.

**buildPluginZip() Windows fix** — same forward-slash normalisation as `buildThemeZip()`: `str_replace('\\', '/', $tmpDir)` before ZIP path comparison.

### Studio tech stack
| Layer | Technology |
|-------|-----------|
| Backend | Laravel 11, PHP 8.2+ |
| Frontend | Vue 3 + Inertia (same pattern as CMS admin) |
| CSS | Tailwind v4 |
| Build | Vite with `@` alias → `resources/js` |
| DB | MySQL, separate database (`animusflow_studio`) |

### Studio file structure
```
animusFlowStudio/
├── app/Http/Controllers/
│   ├── Auth/LoginController.php
│   ├── AboutController.php
│   ├── DashboardController.php
│   ├── PluginController.php
│   ├── SettingsController.php
│   └── ThemeController.php          ← main theme logic (CRUD + AI + publish + preview)
├── resources/js/Pages/
│   ├── About.vue
│   ├── Dashboard.vue
│   ├── Settings.vue
│   ├── Auth/
│   ├── Themes/
│   │   ├── Index.vue                ← theme list
│   │   └── Edit.vue                 ← 10-tab visual editor (1300+ lines)
│   └── Plugins/
│       ├── Index.vue
│       └── Edit.vue
└── routes/web.php
```

### Studio — not yet built
- Direct publish API connection to `animus.kwantoe.com` marketplace
- Creator billing / subscription (creators pay to publish premium assets)
- Analytics for creators (downloads, installs, revenue)

---

## Architecture overview

```
Browser
   │
   ├── [Vue 3 + Inertia — Admin SPA]     [Blade — Generated Sites]
   │         (resources/js/)                  (theme/default/)
   │                │                              │
   └────────────────┴──────────────────────────────┘
                    ▼
           [Laravel 11 — Core]
                    │
   ├── MySQL (multi-tenant: DB-per-tenant via IdentifyTenant + TenantManager)
   ├── Redis (future — queues, cache)
   ├── AI Layer — Claude / OpenAI API via AIEngine service
   └── MCP Layer — HttpClient connects to any MCP Streamable HTTP server
```

For full schema and module breakdown → see `references/architecture.md`  
For implementation details and file structure → see `references/implementation.md`

---

## Tech stack — current reality

| Layer | Technology | Notes |
|-------|-----------|-------|
| Backend | Laravel 11 | PHP 8.2+, strict types |
| Admin frontend | **Vue 3 + Inertia** | `resources/js/` — entry: `admin.js`; login page remains Blade |
| Site rendering | Blade | Default theme in `resources/views/theme/default/` |
| Database | MySQL | Multi-tenant DB-per-tenant (IdentifyTenant middleware + TenantManager) |
| AI engine | Claude API or OpenAI API | Via `App\Services\AIEngine` |
| MCP client | `App\Services\MCP\HttpClient` | Streamable HTTP transport; stateless-first |
| File storage | Local / S3 / R2 / MinIO | Disk chosen via `media_storage_disk` setting (`TenantManager::configureStorageDisk`) |

### Code style
- PHP 8.2+ with `declare(strict_types=1)` on every file
- Laravel 11 conventions (Form Requests, Policies, Jobs for future work)
- Admin views: Vue 3 SFCs (`<script setup>`, Composition API) — NOT Blade
- Site/public views: Blade templates with CSS variables (no Tailwind)
- Admin CSS: Tailwind v4 (`@tailwindcss/vite`) with design tokens — use `bg-primary` NOT `bg-[--color-primary]` (Tailwind v4 doesn't wrap `[--var]` in `var()` automatically)
- Git: Conventional commits — `feat:`, `fix:`, `refactor:`, `docs:`, `test:`

---

## Core models

| Model | Table | Key fields |
|-------|-------|-----------|
| `Page` | `pages` | uuid, title, slug, status (draft/published), SoftDeletes |
| `ContentBlock` | `content_blocks` | page_id, type, content (JSON), settings (JSON), sort_order |
| `SeoMetadata` | `seo_metadata` | page_id, meta_title, meta_description, og_title, og_description, og_image, keywords (JSON), generated_by (ai/manual) |
| `Setting` | `settings` | key, value, group — accessed via `Setting::get(key, default)` / `Setting::set(key, value, group)` |
| `AiProviderSetting` | `ai_provider_settings` | provider (claude/openai/gemini), api_key (encrypted), model, is_default |
| `Subscription` | `subscriptions` | plan (free/pro/agency), status (active/trialing/canceled), stripe_customer_id, stripe_subscription_id, current_period_end |
| `MediaFile` | `media_files` | uuid, filename, path, mime_type, size, SoftDeletes |
| `FormSubmission` | `form_submissions` | page_uuid, block_id, data (JSON), visitor_ip, visitor_id |
| `BlockRating` | `block_ratings` | block_uuid, visitor_id, score (tinyint); unique(block_uuid, visitor_id); `averageFor(uuid): {average, count}` |
| `SurveyVote` | `survey_votes` | block_uuid, visitor_id, option_index (smallint); unique(block_uuid, visitor_id); `totalsFor(uuid, optionCount): array` |
| `McpConnection` | `mcp_connections` | uuid, name, server_url, auth_type (none/bearer/api_key), auth_token (encrypted), auth_header_name, description, is_active, tools_cache (JSON), tools_cached_at |

### pages table — important
The `pages` table does **not** have a `sort_order` column. Use `->latest()` or `->orderBy('id')` — never `->orderBy('sort_order')`.

---

## Settings pattern

All configuration uses the `Setting` model. Never hardcode config values in controllers.

```php
// Read
Setting::get('site_name', 'AnimusFlow');

// Write
Setting::set('active_theme', 'aurora', 'theme');

// Key groups: theme, plugins, layout, seo, general, billing
```

---

## AI engine

All AI calls go through `App\Services\AIEngine`. Never call AI APIs directly from controllers.

```php
AIEngine::generateBlocks(string $description): array                                   // returns validated block array
AIEngine::generateTheme(string $prompt): array                                         // returns design spec JSON
AIEngine::generatePlugin(string $prompt): array                                        // returns plugin manifest JSON
AIEngine::generateSeo(string $pageTitle, string $pageText): array                     // returns {meta_title, meta_description, keywords[], schema_json}
AIEngine::generateChatReply(string $message, array $history, string $siteName, string $siteDescription, string $siteContext = ''): string
AIEngine::generateInsights(array $metrics): string                                     // returns formatted insight text (markdown with emoji)
AIEngine::generateFullPage(string $description): array                                 // returns {title, slug, blocks[]} for one-prompt page creation
AIEngine::interpretMarketplacePrompt(string $userPrompt, array $items): array          // returns {id, confidence, reason} for prompt-to-install
```

For full AIEngine implementation → see `references/implementation.md`

---

## MCP system

All MCP calls go through `App\Services\MCP\HttpClient`. Never call MCP servers directly from controllers.

```php
// List tools available on a server
MCP\HttpClient::listTools(string $serverUrl, array $authHeaders = []): array
// Returns: [{name, description, inputSchema}, ...]

// Invoke a tool and get result
MCP\HttpClient::invokeTool(string $serverUrl, string $toolName, array $arguments = [], array $authHeaders = []): string|array
// Returns: string (single text block) or array (multi-part text) — throws RuntimeException on error

// Quick ping test
MCP\HttpClient::ping(string $serverUrl, array $authHeaders = []): bool
```

**Protocol**: MCP Streamable HTTP (spec 2024-11-05)  
**Strategy**: stateless-first (direct `tools/call`) → full `initialize` handshake on error code -32600  
**Session**: `Mcp-Session-Id` response header captured and forwarded on subsequent requests  
**Auth types stored in McpConnection**: `none`, `bearer` (Authorization: Bearer), `api_key` (custom header)  
**Security**: `POST /api/v1/mcp/invoke` validates `connection_uuid` against active DB records — browser never sees server URL or token

For MCP admin UI → `resources/views/admin/mcp/`  
For MCP block blade → `resources/views/theme/default/sections/mcp_block.blade.php`

---

## Theme system

Themes live in `resources/views/theme/{slug}/`. ThemeManager scans this directory.

```php
ThemeManager::all(): array        // all installed themes from theme.json manifests
ThemeManager::active(): string    // slug of active theme (from settings)
ThemeManager::activate(string $name): void
ThemeManager::viewPrefix(): string  // returns "theme.{active}" or "theme.default"
```

**Primary install method: AI prompt** → `POST /admin/settings/theme/generate`  
Secondary install method: ZIP upload → `POST /admin/settings/theme/upload`

For theme file structure and development guide → see `references/theme-development.md`

---

## Plugin system

Plugins live in `core/plugins/{slug}/` (i.e. `base_path('plugins/{slug}')` from within Laravel). PluginManager scans this directory.

```php
PluginManager::all(): array                            // all installed plugins from manifests
PluginManager::active(): array                         // slugs of active plugins
PluginManager::isActive(string $slug): bool
PluginManager::enable(string $slug): void
PluginManager::disable(string $slug): void
PluginManager::dispatch(string $hook, mixed ...$args): array  // fires hook on all active plugins; returns non-null results
```

**Hook → method name mapping:**
```
page.render      → onPageRender(Page $page): string   // HTML injected before </body>
content.publish  → onContentPublish(Page $page): void // fires when page is published
admin.sidebar    → onAdminSidebar(): array             // ['label', 'icon', 'url']
```

**dispatch() is already wired:**
- `PageRenderController::renderPage()` calls `dispatch('page.render', $page)` — result injected as `$plugin_html` in layout
- `AdminPageController::publish()` calls `dispatch('content.publish', $page)`

**Bundled plugins:**
- `plugins/af-hello-bar/` — announcement bar using `page.render` hook; reads `hello_bar_message`, `hello_bar_bg`, `hello_bar_text` from Settings; has plugin settings UI; uses `animusflow-plugin.json`
- `plugins/af-chatbot/` — AI chat widget using `page.render` hook; calls `POST /api/v1/chat`; configurable label, welcome message, position, accent colour; has plugin settings UI; uses `animusflow-plugin.json`
- `plugins/af-seo-pro/` — advanced SEO stub (installed from marketplace as demo); no hooks active; uses **`manifest.json`** (⚠️ inconsistency — standard is `animusflow-plugin.json`; PluginManager reads both)

**Manifest file naming:** The canonical manifest filename is `animusflow-plugin.json`. `af-seo-pro` uses `manifest.json` because it was installed as a marketplace stub — PluginManager should accept both, but new plugins should always use `animusflow-plugin.json`.

**Primary install method: AI prompt** → `POST /admin/settings/plugin/generate`  
Secondary install method: ZIP upload → `POST /admin/settings/plugin/upload`

For plugin manifest format and hooks → see `references/plugin-development.md`

### Plugin settings UI
Any plugin can declare configurable fields in its `animusflow-plugin.json` under `"settings": [...]`. Each field object:
```json
{ "key": "my_key", "label": "Human label", "type": "text|textarea|color|select|toggle", "default": "", "placeholder": "", "hint": "", "toggle_label": "Enabled" }
```
For `select`, provide `"options": {"value": "Label", ...}` (object, not array).

`AdminPluginSettingController` handles `GET /admin/plugins/{slug}/settings` and `POST /admin/plugins/{slug}/settings`. It reads fields from the manifest and saves via `Setting::set(key, value, 'plugins')`.

---

## Billing system

**Model:** `Subscription` (`app/Models/Subscription.php`)  
**Controller:** `AdminBillingController` (`app/Http/Controllers/Admin/AdminBillingController.php`)  
**View:** `resources/views/admin/billing.blade.php`  
**Migration:** `2026_05_23_000001_create_billing_tables.php`

```php
Subscription::current(): self           // returns first row or in-memory Free plan
Subscription::isPaid(): bool            // true if plan is pro or agency
Subscription::isActive(): bool          // true if status is active or trialing
Subscription::PLANS: array             // catalogue: free/pro/agency with price, features, highlight flag
```

**Stripe settings keys (stored via Setting::set, group 'billing'):**
- `stripe_public_key`, `stripe_secret_key`, `stripe_webhook_secret`
- `stripe_price_pro`, `stripe_price_agency` — Stripe Price IDs for each plan

**Checkout flow:** `POST /admin/billing/checkout/{plan}` → Stripe Checkout Session → redirect to Stripe → `GET /admin/billing/success?plan=&session_id=` → verify session → update Subscription record.

**Webhook:** `POST /stripe/webhook` — registered outside CSRF middleware; verifies HMAC signature; handles `customer.subscription.created`, `customer.subscription.updated`, `customer.subscription.deleted`.

**Portal:** `GET /admin/billing/portal` → Stripe Customer Portal (manage subscription, cancel, update card).

---

## Block system — all 59 types

Block sections live in `resources/views/theme/default/sections/{type}.blade.php`.  
Each receives: `$content` (array from JSON column), `$settings` (array), `$block` (ContentBlock model), `$segment` (string — active visitor segment).

### Original 10 (Phase 1)
`hero`, `features`, `text`, `cta`, `testimonials`, `pricing`, `gallery`, `faq`, `contact`, `newsletter`

### Phase 2 additions (22)
`columns`, `image`, `stats`, `logocloud`, `spacer`, `video`, `richtext`, `team`, `steps`, `icongrid`, `tabs`, `timeline`, `banner`, `cards`, `quote`, `map`, `code`, `comparison`, `countdown`, `progress`, `embed`, `breadcrumb`

### Phase 3.5 — Social Media (6)
| Type | Key fields |
|------|-----------|
| `social_links` | `links[]` → each: `{platform, url, label}`. Platforms: `x`, `linkedin`, `github`, `instagram`, `youtube`, `tiktok`, `discord` |
| `social_feed` | `heading`, `posts[]` → each: `{image, likes, comments, url}` |
| `social_share` | `heading`, `platforms[]` (strings: twitter/facebook/linkedin/copy) |
| `social_counters` | `heading`, `items[]` → each: `{platform, count, label}` |
| `social_proof_bar` | `items[]` → each: `{type, value, label}` |
| `review_widget` | `heading`, `overall_rating`, `total_reviews`, `platform` |

### Phase 3.5 — AI Blocks (6)
| Type | Key fields |
|------|-----------|
| `ai_chatbox` | `heading`, `welcome_message`, `suggestions[]` (strings) |
| `ai_recommendations` | `heading`, `segments[$seg]['items'][]` → each: `{title, text, link, cta_text, image}`; `$seg` from `$segment` variable |
| `ai_summary` | `heading`, `generated_text`, `bullets[]` → each: `{icon, text}`; `generated_by` (ai/manual) |
| `ai_faq` | `heading`, `badge`, `items[]` → each: `{question, answer, confidence}` (confidence: high/medium/low) |
| `ai_search` | `heading`, `placeholder`, `context` |
| `ai_personalized` | `heading`, `segments[$seg]['headline']` + `segments[$seg]['body']` + `segments[$seg]['cta_text']` + `segments[$seg]['cta_url']` |

### Phase 3.5 — Interactive (14)
| Type | Key fields |
|------|-----------|
| `form_builder` | `heading`, `submit_text`, `success_message`, `fields[]` → each: `{label, type, name, required, placeholder, options[]}` |
| `popup` | `heading`, `text`, `cta_text`, `cta_url`, `trigger` (load/exit/scroll/delay) |
| `sticky_cta` | `text`, `cta_text`, `cta_url` |
| `file_download` | `heading`, `file_url`, `file_size`, `file_type` |
| `before_after` | `title`, `before_img`, `after_img`, `before_label`, `after_label` |
| `data_table` | `title`, `headers[]`, `rows[][]` |
| `events_list` | `title`, `events[]` → each: `{title, date, time, location, url, cta_text}` |
| `anchor_nav` | `items[]` → each: `{label, target}` (target = CSS id without #) |
| `cookie_banner` | `text`, `accept_text`, `decline_text`, `privacy_url` |
| `product_card` | `heading`, `items[]` → each: `{name, price, image, badge, features[], cta_text, cta_url}` |
| `job_listing` | `heading`, `items[]` → each: `{title, department, location, type, salary, url}` |
| `rating_widget` | `heading`, `thank_you_message`, `labels[]` (strings for star labels); ⚠️ NO `question` field |
| `survey` | `question`, `options[]` → each: `{text}` — **must be objects, not plain strings** |
| `media_kit` | `heading`, `logos[]` → each: `{src, label, bg}`, `colors[]` → each: `{hex, name, label}`, `files[]` → each: `{name, url, size, icon}` |

### Phase 3.6 — Integrations (1)
| Type | Key fields |
|------|-----------|
| `mcp_block` | `heading`, `connection_uuid`, `tool_name`, `input_params` (JSON string), `output_layout` (text/list/cards/table/raw), `loading_text`, `error_text` |

⚠️ **Critical field name gotchas** (discovered during testing — blade uses these exact names):
- `social_links` → uses `links[]` not `items[]`
- `survey` → `options[]` must be `[{text: '...'}]` objects, NOT plain strings — PHP 8.2 deprecates string-keyed access on strings
- `ai_recommendations` → segment-specific heading uses `$segData['heading'] ?? $heading` — if segment data has no heading key, falls back to top-level `$heading`
- `rating_widget` → has no `question` field in blade
- `cookie_banner` → fields are `text`, `accept_text`, `decline_text`, `privacy_url` (NOT title/accept_label/policy_url)

---

## Layout settings system

All 24 `layout_*` settings keys are passed to every rendered page via `PageRenderController::layoutSettings()` as the `$layout` variable. Stored in DB via `Setting::set(key, value, 'layout')`.

### Header
| Key | Default | Description |
|-----|---------|-------------|
| `layout_header_menu` | `right` | Menu position: left / center / right |
| `layout_header_sticky` | `1` | 1 = sticky header, 0 = static |
| `layout_header_bg` | `glass` | glass / solid / transparent |
| `layout_header_cta_text` | `''` | Primary CTA label (empty = hidden) |
| `layout_header_cta_url` | `#` | Primary CTA URL |
| `layout_header_cta2_text` | `''` | Secondary CTA label (empty = hidden) |
| `layout_header_cta2_url` | `#` | Secondary CTA URL |
| `layout_header_cta2_style` | `outline` | outline / ghost / primary |
| `layout_header_show_toggle` | `1` | 1 = show dark mode toggle |
| `layout_logo_height` | `36` | Logo height px: 24 / 28 / 32 / 36 / 44 / 56 |

### Content
| Key | Default | Description |
|-----|---------|-------------|
| `layout_content_max_width` | `1120` | 960 / 1120 / 1280 / 1440 / full |
| `layout_content_spacing` | `normal` | compact / normal / spacious (section padding-y) |
| `layout_content_animations` | `1` | 1 = scroll fade-in via IntersectionObserver |
| `layout_content_bg` | `white` | white / neutral / dark / image / video |
| `layout_content_font_size` | `md` | sm (14px) / md (16px) / lg (17.5px) |
| `layout_content_bg_media` | `''` | URL for image/video background |
| `layout_content_bg_overlay` | `dark` | dark / light overlay colour |
| `layout_content_bg_opacity` | `50` | 0–90 overlay opacity % |

### Style (brand)
| Key | Default | Description |
|-----|---------|-------------|
| `layout_brand_primary` | `#6366f1` | Primary colour — hex #rrggbb |
| `layout_brand_accent` | `#8b5cf6` | Accent colour — hex #rrggbb |
| `layout_font_family` | `inter` | inter / poppins / dm-sans / outfit / plus-jakarta / playfair / fraunces / sora |
| `layout_shape` | `normal` | sharp / normal / rounded (border-radius scale) |

### Footer
| Key | Default | Description |
|-----|---------|-------------|
| `layout_footer_copyright` | `''` | Footer text (empty = auto from site_name) |
| `layout_footer_show_brand` | `1` | 1 = show "Built with AnimusFlow" |
| `layout_footer_bg` | `default` | default / dark / accent |
| `layout_footer_mode` | `simple` | simple / columns |
| `layout_footer_columns` | `[]` | JSON array of column groups |
| `layout_footer_links` | `[]` | JSON array of bottom links |
| `layout_footer_social_twitter` | `''` | Twitter/X URL |
| `layout_footer_social_linkedin` | `''` | LinkedIn URL |
| `layout_footer_social_github` | `''` | GitHub URL |
| `layout_footer_social_instagram` | `''` | Instagram URL |
| `layout_footer_social_youtube` | `''` | YouTube URL |
| `layout_footer_show_logo` | `0` | 1 = show logo in footer |

---

## Admin routes reference

All admin routes require `admin` middleware. Named with `admin.` prefix.

| Method | Path | Name | Controller |
|--------|------|------|------------|
| GET | /admin | admin.dashboard | AdminController@dashboard |
| GET | /admin/login | admin.login | AdminController@showLogin |
| POST | /admin/login | admin.login.post | AdminController@login |
| POST | /admin/logout | admin.logout | AdminController@logout |
| GET | /admin/about | admin.about | AdminController@about |
| GET | /admin/pages | admin.pages | AdminPageController@index |
| GET | /admin/pages/create | admin.pages.create | AdminPageController@create |
| POST | /admin/pages | admin.pages.store | AdminPageController@store |
| GET | /admin/pages/{uuid} | admin.pages.edit | AdminPageController@edit |
| POST | /admin/pages/{uuid} | admin.pages.update | AdminPageController@update |
| POST | /admin/pages/{uuid}/publish | admin.pages.publish | AdminPageController@publish |
| DELETE | /admin/pages/{uuid} | admin.pages.destroy | AdminPageController@destroy |
| GET | /admin/pages/{uuid}/editor | admin.pages.editor | AdminPageController@editor |
| POST | /admin/pages/{uuid}/blocks | admin.pages.blocks | AdminPageController@blocksUpdate |
| POST | /admin/pages/generate-from-prompt | admin.pages.generate | AdminPageController@generateFromPrompt |
| POST | /admin/ai/generate-blocks | admin.ai.generate | AdminAiController@generate |
| POST | /admin/pages/{uuid}/ai-apply | admin.ai.apply | AdminAiController@apply |
| GET | /admin/media | admin.media | AdminMediaController@index |
| GET | /admin/media/gallery | admin.media.gallery | AdminMediaController@gallery |
| POST | /admin/media/upload | admin.media.upload | AdminMediaController@upload |
| POST | /admin/media/{uuid} | admin.media.update | AdminMediaController@update |
| DELETE | /admin/media/{uuid} | admin.media.destroy | AdminMediaController@destroy |
| GET | /admin/appearance | admin.appearance | AdminAppearanceController@index |
| POST | /admin/appearance | admin.appearance.update | AdminAppearanceController@update |
| GET | /admin/extensions | admin.extensions | AdminExtensionsController@index |
| GET | /admin/settings/navigation | admin.settings.nav | AdminNavController@index |
| POST | /admin/settings/navigation | admin.settings.nav.update | AdminNavController@update |
| GET | /admin/settings | admin.settings | AdminSettingController@index |
| POST | /admin/settings | admin.settings.update | AdminSettingController@update |
| POST | /admin/settings/theme | admin.settings.theme.activate | AdminSettingController@activateTheme |
| POST | /admin/settings/theme/upload | admin.settings.theme.upload | AdminSettingController@uploadTheme |
| POST | /admin/settings/theme/generate | admin.settings.theme.generate | AdminSettingController@promptTheme |
| POST | /admin/settings/plugin | admin.settings.plugin.toggle | AdminSettingController@togglePlugin |
| POST | /admin/settings/plugin/upload | admin.settings.plugin.upload | AdminSettingController@uploadPlugin |
| POST | /admin/settings/plugin/generate | admin.settings.plugin.generate | AdminSettingController@promptPlugin |
| POST | /admin/settings/ai-providers | admin.settings.ai.store | AdminSettingController@storeAiProvider |
| DELETE | /admin/settings/ai-providers/{id} | admin.settings.ai.destroy | AdminSettingController@destroyAiProvider |
| POST | /admin/settings/chatbot | admin.settings.chatbot.update | AdminSettingController@updateChatbot |
| GET | /admin/plugins/{slug}/settings | admin.plugin.settings | AdminPluginSettingController@show |
| POST | /admin/plugins/{slug}/settings | admin.plugin.settings.update | AdminPluginSettingController@update |
| GET | /admin/mcp | admin.mcp.index | AdminMcpController@index |
| GET | /admin/mcp/create | admin.mcp.create | AdminMcpController@create |
| POST | /admin/mcp | admin.mcp.store | AdminMcpController@store |
| POST | /admin/mcp/ping | admin.mcp.ping | AdminMcpController@ping (test without saving) |
| GET | /admin/mcp/{uuid} | admin.mcp.edit | AdminMcpController@edit |
| PUT | /admin/mcp/{uuid} | admin.mcp.update | AdminMcpController@update |
| DELETE | /admin/mcp/{uuid} | admin.mcp.destroy | AdminMcpController@destroy |
| POST | /admin/mcp/{uuid}/probe | admin.mcp.probe | AdminMcpController@probe (list tools + cache) |
| GET | /admin/chatbot | admin.chatbot | AdminChatbotController@index |
| GET | /admin/chatbot/{id} | admin.chatbot.show | AdminChatbotController@show |
| DELETE | /admin/chatbot/{id} | admin.chatbot.destroy | AdminChatbotController@destroy |
| POST | /admin/chatbot/rebuild-context | admin.chatbot.rebuild | AdminChatbotController@rebuildContext |
| GET | /admin/pages/{uuid}/variants | admin.pages.variants | AdminVariantController@index |
| POST | /admin/pages/{uuid}/variants | admin.pages.variants.store | AdminVariantController@store |
| DELETE | /admin/pages/{uuid}/variants/{id} | admin.pages.variants.destroy | AdminVariantController@destroy |
| GET | /admin/marketplace | admin.marketplace | AdminMarketplaceController@index |
| POST | /admin/marketplace/install | admin.marketplace.install | AdminMarketplaceController@install |
| POST | /admin/marketplace/prompt-install | admin.marketplace.prompt | AdminMarketplaceController@promptInstall |
| GET | /admin/migration | admin.migration | AdminMigrationController@index |
| POST | /admin/migration/preview | admin.migration.preview | AdminMigrationController@preview |
| POST | /admin/migration/import | admin.migration.import | AdminMigrationController@import |
| GET | /admin/billing | admin.billing | AdminBillingController@index |
| POST | /admin/billing/stripe-keys | admin.billing.stripe-keys | AdminBillingController@saveStripeKeys |
| POST | /admin/billing/checkout/{plan} | admin.billing.checkout | AdminBillingController@checkout |
| GET | /admin/billing/success | admin.billing.success | AdminBillingController@success |
| GET | /admin/billing/portal | admin.billing.portal | AdminBillingController@portal |

### Public + special routes
- `GET /` — redirects to `/p/home` (or first published page); falls back to `welcome` view
- `GET /p/{slug}` — render published page (status = published only)
- `GET /preview/{uuid}` — preview any page (no status check; shows preview banner)
- `GET /setup/*` — setup wizard
- `POST /stripe/webhook` — Stripe webhook (no CSRF, no admin auth; HMAC-verified)
- `POST /submit-form` — public form submission (CSRF required; saves to form_submissions table)

### API v1 routes

| Method | Path | Auth | Rate limit | Controller |
|--------|------|------|-----------|------------|
| POST | /api/v1/auth/login | — | — | AuthController@login |
| GET | /api/v1/auth/me | Sanctum | — | AuthController@me |
| POST | /api/v1/auth/logout | Sanctum | — | AuthController@logout |
| GET | /api/v1/pages | Sanctum | — | PageController@index |
| POST | /api/v1/pages | Sanctum | — | PageController@store |
| GET | /api/v1/pages/{uuid} | Sanctum | — | PageController@show |
| PUT | /api/v1/pages/{uuid} | Sanctum | — | PageController@update |
| DELETE | /api/v1/pages/{uuid} | Sanctum | — | PageController@destroy |
| POST | /api/v1/pages/{uuid}/publish | Sanctum | — | PageController@publish |
| POST | /api/v1/pages/{uuid}/unpublish | Sanctum | — | PageController@unpublish |
| POST | /api/v1/pages/{uuid}/blocks | Sanctum | — | PageController@saveBlocks |
| POST | /api/v1/blocks/preview | Sanctum | — | BlockPreviewController@preview |
| GET | /api/v1/settings | Sanctum | — | SettingController@index |
| PUT | /api/v1/settings | Sanctum | — | SettingController@update |
| GET | /api/v1/mcp/connections | Sanctum | — | McpController@connections |
| POST | /api/v1/chat | — | 30/min | ChatController@chat |
| POST | /api/v1/events | — | 120/min | EventController@track |
| GET | /api/v1/search | — | 60/min | SearchController@search |
| POST | /api/v1/ratings | — | 60/min | RatingController@store |
| GET | /api/v1/ratings/{blockUuid} | — | 60/min | RatingController@show |
| POST | /api/v1/surveys/{blockUuid}/vote | — | 30/min | SurveyController@vote |
| POST | /api/v1/mcp/invoke | — | 60/min | McpController@invoke |

---

## Vue 3 + Inertia admin — architecture notes

### Dependencies
- `inertiajs/inertia-laravel ^3.1` (Composer)
- `vue ^3.5`, `@inertiajs/vue3 ^3.2`, `@vitejs/plugin-vue ^6.0` (npm)
- `lucide-vue-next`, `radix-vue`, `class-variance-authority` (npm)
- Tailwind v4 (`@tailwindcss/vite`)

### Entry points
- `resources/js/admin.js` — Vue/Inertia bootstrap + `import '../css/admin.css'`
- `resources/views/admin-app.blade.php` — root Inertia template (`@vite(['resources/js/admin.js'])`)
- **IMPORTANT**: do NOT add `resources/css/admin.css` to `@vite()` separately — it's already imported inside `admin.js`. Doing so causes `Unable to locate file in Vite manifest` error.

### Shared props (HandleInertiaRequests)
`auth.user`, `site.name`, `flash` — available via `usePage().props` in every Vue page.

### AdminLayout slots
- Default slot: page content
- `#actions`: topbar action buttons (e.g. Save, Publish)

### Vue admin pages (resources/js/Pages/Admin/)
| Page | Component | Controller method |
|------|-----------|------------------|
| Dashboard | `Dashboard.vue` | `AdminController::dashboard()` |
| Pages list | `Pages/Index.vue` | `AdminPageController::index()` |
| Page edit (metadata/SEO) | `Pages/Edit.vue` | `AdminPageController::edit()` |
| Page editor (visual blocks) | `Pages/Editor.vue` | `AdminPageController::editor()` |
| Navigation | `Navigation.vue` | `AdminNavController::index()` |
| Appearance | `Appearance.vue` | `AdminAppearanceController::index()` |
| Settings | `Settings.vue` | `AdminSettingController::index()` |
| Media | `Media/Index.vue` | `AdminMediaController::index()` |
| Extensions | `Extensions.vue` | `AdminExtensionsController::index()` |
| Chatbot | `Chatbot.vue` | `AdminChatbotController::index()` |
| Chatbot session | `ChatbotSession.vue` | `AdminChatbotController::show()` |
| Billing | `Billing.vue` | `AdminBillingController::index()` |
| MCP list | `Mcp/Index.vue` | `AdminMcpController::index()` |
| MCP form | `Mcp/Form.vue` | `AdminMcpController::create()/edit()` |
| Marketplace | `Marketplace.vue` | `AdminMarketplaceController::index()` |
| Migration | `Migration.vue` | `AdminMigrationController::index()` |
| Plugin settings | `Plugins/Settings.vue` | `AdminPluginSettingController::show()` |
| About | `About.vue` | `AdminController::about()` |

### Inertia patterns
```js
// Navigation
import { router, useForm, usePage } from '@inertiajs/vue3'
router.get('/admin/pages')          // redirect
router.post('/admin/foo', data)     // POST
router.delete('/admin/foo/1')       // DELETE

// Forms
const form = useForm({ title: '' })
form.post('/admin/pages')
form.put('/admin/pages/uuid')
form.processing                     // boolean — disable submit button

// Reload partial data
router.reload({ only: ['files'] })

// External/file forms (Stripe checkout, ZIP upload)
// Use raw fetch or <form method="POST"> — NOT useForm (avoids Inertia intercept)
```

### CSS in Vue (Tailwind v4 tokens)
Always use token utility classes, never raw CSS variable syntax in arbitrary values:
```
bg-primary       ✅    bg-[--color-primary]  ❌
bg-sidebar       ✅    bg-[--color-sidebar]  ❌
text-primary     ✅    text-[--color-primary] ❌
```

### Security pattern (repeated in multiple pages)
Never pass raw API keys or auth tokens to Vue props. Use boolean flags instead:
```php
// Controller — correct
'providers' => $providers->map(fn($p) => [...$p->toArray(), 'has_key' => !empty($p->api_key)])
// auth_token, stripe_secret_key, etc. → omitted from all Inertia responses
```

---

## Appearance admin page — architecture notes

**File:** `core/resources/views/admin/appearance.blade.php`

### Live Preview
- Uses `/p/{slug}` (public URL), NOT `/preview/{uuid}` — avoids the "Preview mode" banner
- The JS IIFE `applyToIframe()` reads all form values and applies CSS variables directly to `iframe.contentDocument.documentElement.style` in real-time
- Color changes fire on `input` event (not `change`); font changes inject/update a `<link id="af-preview-font-link">` in the iframe `<head>`
- Toggle button hides/shows `#preview-container`

### Known PHP gotcha — numeric array keys
In Blade `@foreach` loops with numeric-looking string keys (`'960'`, `'1120'`, `'0'`, `'1'`), PHP converts them to integers. Use `=== (string)$val` for `checked` comparison.  
Affected fields: `layout_content_max_width`, `layout_content_animations`, `layout_logo_height`.

---

## ⚠️ Critical Blade gotcha — `@php` comments

**Blade comments `{{-- --}}` inside `@php ... @endphp` blocks are NOT stripped by the Blade compiler.**
They remain as literal text in the compiled PHP → `ParseError: syntax error`.

```php
// WRONG — causes ParseError at runtime:
@php
    $navCta = $layout['header_cta_text'] ?? '';  {{-- item 5 --}}
@endphp

// CORRECT — use PHP line comments:
@php
    $navCta = $layout['header_cta_text'] ?? '';  // item 5
@endphp
```

This applies to ALL `@php` blocks in Blade templates. Always use `//` or `/* */` inside them.

---

## AI error handling — mandatory rule

AI failure must NEVER block content publishing or page loading. Always wrap `AIEngine` calls and degrade gracefully (empty/manual content) on failure.

```php
try {
    $blocks = AIEngine::generateBlocks($description);
} catch (\Throwable $e) {
    Log::warning('AI generation failed', ['error' => $e->getMessage()]);
    return []; // user fills manually — never surface a 500 to the visitor
}
```

---

## Database naming conventions

- Tables: `snake_case`, plural
- Foreign keys: `{table_singular}_id`
- Timestamps: always `created_at` + `updated_at` on every table
- Soft deletes: `deleted_at` on content tables (pages, content_blocks, media_files)
- UUIDs for public-facing IDs; auto-increment for internal joins
- Encrypted fields stored via Laravel `encrypted` cast (e.g. `api_key`, `auth_token`)

---

## Key files

| Task | File |
|------|------|
| Theme file structure, CSS tokens, section data schemas | `references/theme-development.md` |
| Plugin manifest, hooks, scaffold, AI spec | `references/plugin-development.md` |
| Database schema, migrations | `references/architecture.md` |
| Plugins, themes, marketplace (future) | `references/plugins-themes-marketplace.md` |
| AI modules — chatbot API, SEO, personalisation, analytics | `references/ai-modules.md` |
| MCP client service | `app/Services/MCP/HttpClient.php` |
| MCP admin controller | `app/Http/Controllers/Admin/AdminMcpController.php` |
| MCP API controller | `app/Http/Controllers/Api/V1/McpController.php` |
| MCP block section | `resources/views/theme/default/sections/mcp_block.blade.php` |
| Overlay editor | `public/editor/overlay.js` |
| Vue entry point | `resources/js/admin.js` |
| Vue root layout | `resources/js/Layouts/AdminLayout.vue` |
| Vue sidebar link | `resources/js/Components/SidebarLink.vue` |
| Vue stat card | `resources/js/Components/StatCard.vue` |
| Vue toggle switch | `resources/js/Components/ToggleSwitch.vue` |
| Vue language switcher | `resources/js/Components/LanguageSwitcher.vue` |
| Vue interactive counter block | `resources/js/Blocks/InteractiveCounter.vue` |
| Inertia middleware (shared props) | `app/Http/Middleware/HandleInertiaRequests.php` |
| Inertia root template | `resources/views/admin-app.blade.php` |
| Admin CSS (Tailwind v4 design tokens) | `resources/css/admin.css` |
