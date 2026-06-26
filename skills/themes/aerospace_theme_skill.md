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

## layout_config (campos-chave)

```
header_type: glass · header_sticky: true · header_cta_text: "Contacto" · header_cta_url: "/contactos"
nav_type: circular · nav_position: center · menu_layout: circular · submenu_type: circular
normal_menu_position: horizontal-right · max_width: 1120 · spacing: normal · layout_type: full-width
show_dark_toggle: true · back_to_top: true
gallery_layout: "3d-carousel" · gallery_auto_rotate: true · gallery_tilt_enabled: true
hud_bg_type: "video" · hud_bg_video: "/videos/aerospace-fundo.mp4"
hud_bg_single_photo: "/images/aerospace-hero.svg" · hud_bg_gallery: [3 imagens]
hud_overlay_enabled: true · interactive_mesh_3d: true · preloader_terminal: true · telemetry_enabled: false
chat_popup_enabled: true · chat_popup_mode: "form" · chat_voice_commands: true · hover_sound_effects: true
contact_show_map: true · contact_map_iframe: "<embed Google Maps>" · contact_show_newsletter: true
menu_space_top: 24 · menu_space_bottom: 24
nav_links: [ Home /, Sobre /sobre, Serviços /servicos, Galeria /galeria, Contactos /contactos ]
```

> ⚠️ Para o `hud_bg_type: "video"` dá sempre um **fallback CSS** (gradiente animado) em
> `.screensaver-container` — o `<video>` não tem poster, degrada se a media faltar.

## capabilities

`video_bg`, `parallax`, `animations`, `lightbox`, `cookie_banner`, `preloader`, `scroll_progress`,
`back_to_top` → **true**. `mega_menu`, `search` → false.

## sections (Blade)

Gera as **13** secções, cada uma como HTML/Blade que pode ler `$theme->layout_config[...]`:

`hero` · `about` · `features` · `stats` · `steps` · `gallery` · `testimonials` · `team` ·
`contact` · `map` · `cta` · `text` · `footer`

A `gallery` usa o carrossel 3D (`gallery_layout`); o `hero`/screensaver usa o fundo HUD; o `contact`
mostra o mapa (`contact_map_iframe`) e newsletter conforme as flags.

## custom_css / custom_js

Implementa os comportamentos de marca: **preloader de consola de boot**, **grelha 3D interativa de
perspetiva**, **menu orbital com sonar**, **widgets de cockpit arrastáveis**, **comandos por voz** e os
efeitos de hover. (Referência completa já resolvida: `aerospace_theme_snapshot.md`.)

## Acabamento

`status: "published"`, `version: "1.1.0"`. Valida no **Preview**, depois exporta (ZIP / `.afprompt`) e
**instala no AnimusFlow**. As "Definições do site" (`theme_settings`) são semeadas à parte por
`seed_aerospace_settings.php` (botão "Repor definições recomendadas" no editor).
