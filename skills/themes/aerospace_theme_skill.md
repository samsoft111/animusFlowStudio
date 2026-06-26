# Skill — Gerar o tema AeroSpace (AnimusFlowStudio)

> 🏷️ **Sistema B — Geração.** Carrega-me com o botão **✦** no Chat IA / Modo Construção do editor de
> tema e descreve "cria o tema AeroSpace". Eu sou um **brief de design** (cabe no limite de 60 000
> caracteres do upload), não o tema literal. O snapshot completo (HTML/CSS/JS já resolvido, espelho da
> BD) vive ao lado em **`aerospace_theme_snapshot.md`** — usa-o como *referência*, não como skill.

## Briefing

Cria um tema **premium, futurista e profissional** para a **AeroSpace** — logística autónoma de carga,
transporte vertical de mercadorias e monitorização orbital. **Multi-página** (não single-page): o menu
horizontal normal renderiza `$nav_links` com `@foreach`; o menu orbital e os botões usam **caminhos
relativos** (`/`, `/sobre`, `/servicos`, `/galeria`, `/contactos`) — **nunca** âncoras `#...`.

Aplica tudo num bloco ` ```json_updates ``` ` no fim da resposta. Gera os valores HTML/CSS/JS com
`json_encode` e **escapa `"` como `\"`** (aspas cruas quebram o JSON).

## Design system

- **Fontes:** `heading: "Outfit"`, `body: "Inter"` (Google Fonts).
- **Cores** (dark é o modo principal — tema escuro espacial; define também `light`):
  - `--color-background` `#030712` · `--color-foreground` `#F9FAFB` · `--color-secondary` `#020617`
  - `--color-primary` `#3B82F6` (azul) · `--color-accent` `#22D3EE` (ciano) — o par que dá o look HUD
  - `--color-success` `#34D399` · `--color-warning` `#FBBF24` · `--color-destructive` `#F87171`
  - `--color-card` `rgba(3,7,18,0.85)` · `--color-muted` `#0F172A` · `--color-border` `rgba(255,255,255,0.05)`
  - Garante contraste AA sobre fundo escuro.

## ⭐ Ecrã inicial — Screensaver HUD interativo (a assinatura do tema)

O `hero` abre com um **screensaver** que cobre o ecrã (`.screensaver-container`, `z-30`) e **recua**
quando o utilizador interage, revelando o conteúdo (nav + hero), como um protetor de ecrã dinâmico.

- **Recuo no hover E no foco:** `.aerospace-hero.group:hover` **e** `:focus-within` → screensaver vai a
  `z-0`, opacidade reduzida, `blur(4px)`, `scale(0.95)`, `pointer-events:none`; o `.hero-content` aparece
  (opacity 0→1) e o `.info-panel` esmaece. **Regressa** quando o cursor/foco saem (CSS puro).
- **Táctil (sem hover):** JS revela ao 1.º toque (`window.matchMedia('(hover: none)')` → `pointerdown`/
  `touchstart` adicionam `.hero-revealed`). Em desktop **não** se revela de forma permanente — é o hover/foco.
- **Aplica-se a TODAS as páginas** (não há gating de home; não usar `hero-internal`/`hero-revealed` por defeito).
- **Card central** (`.info-panel` → `.info-card`, `animate-pulse`): título + subtítulo + dica
  ("Passe o cursor ou toque no ecrã para aceder"). `position: fixed`, centrado na horizontal, `top`
  configurável. Esmaece no hover/foco.

### 4 modos de fundo (`hud_bg_type`) — todos com o MESMO efeito de recuo
`video` (`<video>` com `poster`) · `photo` (`.screensaver-photo`) · `gallery` (`.screensaver-gallery`,
slideshow) · `none` (só o **gradiente animado** `aerospace-nebula-drift` do container). No Blade: usa
`@if === 'video' @elseif === 'gallery' @elseif === 'photo' @endif` — o `none` cai fora (sem media). Dá
sempre **fallback CSS** (gradiente) — o `<video>`/imagens podem faltar.

## 🎛️ Definições configuráveis (theme_settings ↔ layout_config ↔ vars CSS)

> **Padrão central deste tema** (segue-o para qualquer valor que o criador deva ajustar no CMS):
> 1. O valor vive em **`layout_config`** (com um default = aspeto atual).
> 2. O **`hero`** emite-o como **variável CSS inline** no `<section style="…">` (um bloco `@php` no topo
>    do hero converte hex→rgb quando preciso — ex.: cor+opacidade → `rgba()`).
> 3. O **`custom_css`** lê `var(--x, fallback)`.
> 4. O campo é declarado no schema **`theme_settings`** (`source: layout`) para o CMS mostrar o formulário
>    em **Definições do Tema** e gravar de volta em `layout_config`.
>
> ⚠️ O schema NÃO se inventa no `json_updates`: a fonte única é
> **`app/Support/ThemeSettingsRecommender.php`**, semeado por `seed_aerospace_settings.php` (e pelo botão
> "Repor definições recomendadas"). Declarar o campo não chega — a secção/CSS **tem de ler** a chave.

Variáveis CSS emitidas pelo hero e o que controlam:

| layout_config | var CSS | controla | default |
|---|---|---|---|
| `screensaver_scrim` (%) | `--scrim-opacity` (=/100) | escurecimento do vídeo (`.info-panel` bg alpha) | 10 |
| `screensaver_blur` (px) | `--scrim-blur` | desfoque do scrim (`backdrop-filter`) | 0 |
| `screensaver_video_opacity` (%) | `--video-opacity` (=/100) | opacidade do `.screensaver-container` | 100 |
| `info_card_top` (%) | `--info-card-top` | posição vertical do card | 36 |
| `menu_space_top` (px) | `--circular-menu-y` (= valor−84 px) | altura do menu circular (24 → −60px) | 24 |
| `navbar_color` + `navbar_opacity` (%) | `--navbar-bg` (rgba via `@php`) | cor/opacidade da barra `.normal-navbar` | #1E293B / 72 |
| `hero_bg_color` | `--hero-bg` | cor do fundo (visível com screensaver esmaecido / modo `none`) | #0F1A2E |
| `hero_internal_padding_top` (px) | `--subpage-padding-top` | início do conteúdo nas subpáginas (modo `hero-internal`) | 50 |
| `info_card_{title,subtitle,hint}_text` | — (Blade `{{ }}`) | textos do card | AEROSPACE / Operações & Logística Aérea / Passe o cursor… |
| `info_card_{title,subtitle,hint}_size` (px) | `style` inline | tamanho de cada texto | 20 / 12 / 10 |
| `info_card_{title,subtitle,hint}_color` | `style` inline | cor de cada texto | #FFFFFF / #06B6D4 / #94A3B8 |

## layout_config (campos-chave)

```
header_type: glass · header_sticky: true · header_cta_text: "Contacto" · header_cta_url: "/contactos"
nav_type: circular · nav_position: center · menu_layout: circular · submenu_type: circular
normal_menu_position: horizontal-right · max_width: 1120 · spacing: normal · layout_type: full-width
show_dark_toggle: true · back_to_top: true
gallery_layout: "3d-carousel" · gallery_auto_rotate: true · gallery_tilt_enabled: true
hud_bg_type: "video"|"photo"|"gallery"|"none" · hud_bg_video · hud_bg_single_photo · hud_bg_gallery: [3]
hud_overlay_enabled: true · interactive_mesh_3d: true · preloader_terminal: true · telemetry_enabled: false
chat_popup_enabled: true · chat_popup_mode: "form" · chat_voice_commands: true · hover_sound_effects: true
contact_show_map: true · contact_map_iframe: "<embed Google Maps>" · contact_show_newsletter: true
menu_space_top: 24 · menu_space_bottom: 24      // menu_space_top = altura do menu circular
nav_links: [ Home /, Sobre /sobre, Serviços /servicos, Galeria /galeria, Contactos /contactos ]
+ (configuráveis acima): screensaver_scrim/blur/video_opacity · info_card_top · info_card_*_text/size/color
  · navbar_color · navbar_opacity · hero_bg_color · hero_internal_padding_top (subpáginas)
```

## capabilities

`video_bg`, `parallax`, `animations`, `lightbox`, `cookie_banner`, `preloader`, `scroll_progress`,
`back_to_top` → **true**. `mega_menu`, `search` → false.

## sections (Blade) — dirigidas por conteúdo + por página

Gera as **13** secções como HTML/Blade. Lêem `$theme->layout_config[...]` e — importante — são
**dirigidas por conteúdo**: cada secção começa com `@php $c = $content ?? []; … @endphp` e usa
`{{ $c['heading'] ?? 'fallback' }}` (+ `$settings`), para mostrar o conteúdo real da página no CMS, com
fallback para texto demo. (12 secções são dinâmicas; o `footer` é global/estático.)

`hero` · `about` · `features` · `stats` · `steps` · `gallery` · `testimonials` · `team` ·
`contact` · `map` · `cta` · `text` · `footer`

A `gallery` usa o carrossel 3D (`gallery_layout`); o `hero` é o screensaver (acima); o `contact`
mostra o mapa (`contact_map_iframe`) e newsletter conforme as flags.

### Home vs Subpáginas
- **Home** (`/`): hero com o screensaver interativo a cobrir o ecrã.
- **Subpáginas** (`/sobre`, `/servicos`, `/galeria`, `/contactos`): hero **compacto**
  (`hero-internal hero-revealed`) — conteúdo logo abaixo do menu, com o screensaver **visível como
  fundo** (`.hero-internal .screensaver-container` opacity 0.55, sem blur). Espaço do topo configurável
  via `hero_internal_padding_top` → `--subpage-padding-top`. Menu efetivo = `normal` (via `$isHome`).
- **Preview por página** (`resources/views/preview/theme.blade.php`): deriva `$currentPage` do caminho,
  filtra secções por `$pageSectionsMap`, e injeta o conteúdo de
  `skills/themes/aerospace-demo-content.json` (fallback `sampleData`). Rotas `/sobre … /contactos` →
  `ThemeController::previewPage` (tema via sessão).

## custom_css / custom_js

Implementa os comportamentos de marca: **preloader de consola de boot**, **grelha 3D interativa de
perspetiva**, **menu orbital com sonar**, **widgets de cockpit arrastáveis**, **comandos por voz**, o
**screensaver interativo** (hover/foco/táctil) e os efeitos de hover. O `custom_css` deve ler as
`var(--…)` da tabela de definições acima. (Referência completa já resolvida: `aerospace_theme_snapshot.md`.)

## Acabamento e fluxo de manutenção

`status: "published"`, `version: "1.1.0"`. Valida no **Preview**, depois exporta (ZIP / `.afprompt`) e
**instala no AnimusFlow** (os campos aparecem em Definições do Tema → grupos *Cabeçalho*, *Menus*,
*Fundo & HUD*, etc.).

Ao alterar o tema (BD `StudioTheme` "AeroSpace"):
1. editar a BD (custom_css / sections.* / layout_config). Para regenerar as secções dirigidas por
   conteúdo há o script `skills/themes/make_aerospace_dynamic.php` (reescreve os 12 templates dinâmicos),
2. se mudou o schema → `php skills/themes/seed_aerospace_settings.php`,
3. **`php skills/themes/build_aerospace_skill.php --write`** (resync do snapshot),
4. `php tests/aerospace_theme_test.php` deve ficar **verde**.
