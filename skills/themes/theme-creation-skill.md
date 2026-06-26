# Skill genérico — Criação de Tema (AnimusFlowStudio)

Instruções para o **Chat IA do editor de tema** gerar um tema completo, profissional e coerente.
Aplica as alterações como um bloco ` ```json_updates ``` ` no fim da resposta.

> Exemplo concreto de um tema premium real: `aerospace_theme_skill.md` (nesta pasta).
> Guia técnico completo (Sistema A): `animusFlow/skills/animusflow/references/theme-development.md`.

---

## 1. Contrato do bloco `json_updates`

Chaves aceites (só precisas de incluir as que mudam — o controlador faz deep-merge):

```
label, description, version, status,        // meta (status: "draft" até validar no preview)
colors.{light,dark}, fonts,                 // design system
layout_config, capabilities,                // estrutura + flags
sections, custom_css, custom_js,            // conteúdo (Blade) + código
settings                                    // schema "Definições do Tema" (ver §5)
```

⚠️ **Escape de aspas:** HTML/CSS/JS dentro de valores string **têm de escapar `"` como `\"`**. Aspas
cruas quebram o JSON (mesmo o parser tolerante trunca no 1.º `"`). Gera os valores com `json_encode`.

## 2. Design system

- `colors.light` e `colors.dark`: define os 11 tokens (`--color-primary`, `--color-accent`,
  `--color-background`, `--color-foreground`, `--color-card`, `--color-muted`, `--color-border`,
  `--color-success`, `--color-destructive`, `--color-warning`, `--color-secondary`). Garante contraste AA.
- `fonts`: `{ "heading": "Outfit", "body": "Inter" }` — famílias do Google Fonts.

## 3. layout_config

Estrutura/comportamento. Campos comuns: `header_type` (glass/solid/transparent/centered/sidebar),
`header_sticky`, `header_cta_text/url`, `nav_type`, `nav_position`, `menu_layout` (circular/normal),
`max_width`, `spacing`, `footer_type`, `show_dark_toggle`, `back_to_top`. Temas avançados podem ter
campos próprios (ex.: `hud_bg_type`, `telemetry_enabled`).

## 4. sections (Blade)

Cada secção é **Blade** gravada no export como `sections/{tipo}.blade.php`. Pode usar `@if`/`@foreach`/
`{{ }}` e **`$theme->layout_config['chave']`** (no preview do Studio e no CMS o `$theme` é injetado).
- HUD/vídeo de fundo: dá **fallback CSS** (gradiente animado) para degradar quando o media falta.
- Multi-página: nav usa `$nav_links` (loop `@foreach`); usa caminhos relativos (`/`, `/sobre`) e **não**
  âncoras de single-page (`#sobre`).

## 5. settings — schema "Definições do Tema"

Declara as opções que o **criador do site** vai configurar no AnimusFlow (Admin → Definições do Tema).
Cada campo:

```json
{ "key": "hud_bg_type", "label": "Tipo de fundo", "type": "select", "group": "fundo",
  "source": "layout", "default": "video",
  "options": { "video": "Vídeo", "photo": "Foto única", "gallery": "Galeria" }, "hint": "…" }
```

- `type`: text · textarea · number · range · toggle · color · select · media_image · media_video · media_gallery
- `source`: layout · color_light · color_dark · font · capability (onde o valor vive no render)
- `group`: geral · cabecalho · menus · cores · tipografia · fundo · layout · rodape · funcionalidades

> Regra de ouro: para uma definição ter efeito, a secção/layout **tem de ler** a chave
> (`$layout[...]` / `$theme->layout_config[...]` / `$theme_settings[...]`). Declarar o campo não chega.
> Botão "Repor definições recomendadas" no editor → semeia o schema a partir do design atual.

## 6. capabilities
Flags de funcionalidade: `video_bg`, `parallax`, `animations`, `lightbox`, `cookie_banner`,
`preloader`, `scroll_progress`, `back_to_top`, etc.

## 7. Fluxo
Gerar → validar no **Preview** → `status: "published"` → exportar (ZIP/`.afprompt`) → **instalar no AnimusFlow**.
