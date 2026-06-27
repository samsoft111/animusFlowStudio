<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\StudioTheme;

/**
 * Constrói o schema de "Definições do site" RECOMENDADO para um tema —
 * a lista declarativa de campos que o criador configurará no AnimusFlow.
 *
 * Os `default` são tirados dos valores ATUAIS do tema (layout_config / colors /
 * fonts / capabilities), por isso o schema reflete o design real do tema.
 *
 * Fonte única de verdade partilhada por:
 *   - skills/themes/seed_aerospace_settings.php (seed em CLI)
 *   - ThemeController::recommendSettings() (botão "Repor definições recomendadas")
 */
final class ThemeSettingsRecommender
{
    private const FONT_OPTIONS = [
        'Inter' => 'Inter', 'Poppins' => 'Poppins', 'DM Sans' => 'DM Sans', 'Outfit' => 'Outfit',
        'Plus Jakarta Sans' => 'Plus Jakarta Sans', 'Playfair Display' => 'Playfair Display',
        'Fraunces' => 'Fraunces', 'Sora' => 'Sora',
    ];

    /** @return array<int,array<string,mixed>> */
    public static function recommend(StudioTheme $theme): array
    {
        $lc    = $theme->layout_config ?? [];
        $cl    = $theme->colors['light'] ?? [];
        $cd    = $theme->colors['dark'] ?? [];
        $fonts = $theme->fonts ?? [];
        $caps  = $theme->capabilities ?? [];

        return [
            // ── Geral ────────────────────────────────────────────────────
            self::f(['key' => 'show_dark_toggle', 'label' => 'Mostrar alternância claro/escuro', 'type' => 'toggle', 'group' => 'geral', 'default' => $lc['show_dark_toggle'] ?? true, 'hint' => 'Botão de tema claro/escuro no cabeçalho.']),
            self::f(['key' => 'back_to_top', 'label' => 'Botão "voltar ao topo"', 'type' => 'toggle', 'group' => 'geral', 'default' => $lc['back_to_top'] ?? true]),
            self::f(['key' => 'logo_type', 'label' => 'Tipo de logótipo', 'type' => 'select', 'group' => 'geral', 'default' => $lc['logo_type'] ?? 'both',
                'options' => ['text' => 'Apenas Texto', 'image' => 'Apenas Imagem', 'both' => 'Imagem e Texto']]),
            self::f(['key' => 'logo_image_light', 'label' => 'Logótipo — imagem (Modo Claro)', 'type' => 'media_image', 'group' => 'geral', 'default' => $lc['logo_image_light'] ?? '/images/aerospace-logo.svg']),
            self::f(['key' => 'logo_image_dark', 'label' => 'Logótipo — imagem (Modo Escuro)', 'type' => 'media_image', 'group' => 'geral', 'default' => $lc['logo_image_dark'] ?? '']),
            self::f(['key' => 'logo_height', 'label' => 'Logótipo — altura (px)', 'type' => 'range', 'group' => 'geral', 'default' => $lc['logo_height'] ?? 36, 'min' => 20, 'max' => 100, 'step' => 2]),
            self::f(['key' => 'logo_text_size', 'label' => 'Logótipo — tamanho do texto (px)', 'type' => 'range', 'group' => 'geral', 'default' => $lc['logo_text_size'] ?? 20, 'min' => 14, 'max' => 36, 'step' => 1]),
            self::f(['key' => 'logo_text_weight', 'label' => 'Logótipo — espessura do texto', 'type' => 'select', 'group' => 'geral', 'default' => $lc['logo_text_weight'] ?? 'bold',
                'options' => ['normal' => 'Regular', 'medium' => 'Médio', 'semibold' => 'Semi-negrito', 'bold' => 'Negrito', 'extrabold' => 'Extra-negrito']]),
            self::f(['key' => 'favicon_image', 'label' => 'Favicon do site', 'type' => 'media_image', 'group' => 'geral', 'default' => $lc['favicon_image'] ?? '/favicon.ico']),


            // ── Cabeçalho ────────────────────────────────────────────────
            self::f(['key' => 'header_type', 'label' => 'Estilo do cabeçalho', 'type' => 'select', 'group' => 'cabecalho', 'default' => $lc['header_type'] ?? 'glass',
                'options' => ['glass' => 'Glass / Blur', 'solid' => 'Sólido', 'transparent' => 'Transparente', 'centered' => 'Logo centrado', 'sidebar' => 'Sidebar']]),
            self::f(['key' => 'header_sticky', 'label' => 'Cabeçalho fixo (sticky)', 'type' => 'toggle', 'group' => 'cabecalho', 'default' => $lc['header_sticky'] ?? true]),
            self::f(['key' => 'header_cta_text', 'label' => 'Botão CTA — texto', 'type' => 'text', 'group' => 'cabecalho', 'default' => $lc['header_cta_text'] ?? '', 'hint' => 'Vazio = sem botão.']),
            self::f(['key' => 'header_cta_url', 'label' => 'Botão CTA — URL', 'type' => 'text', 'group' => 'cabecalho', 'default' => $lc['header_cta_url'] ?? '#']),
            self::f(['key' => 'navbar_color', 'label' => 'Barra de menus — cor', 'type' => 'color', 'group' => 'cabecalho', 'default' => $lc['navbar_color'] ?? '#1E293B']),
            self::f(['key' => 'navbar_opacity', 'label' => 'Barra de menus — opacidade (%)', 'type' => 'range', 'group' => 'cabecalho', 'default' => $lc['navbar_opacity'] ?? 72, 'min' => 0, 'max' => 100, 'step' => 5, 'hint' => 'Mais alto = barra mais sólida/visível.']),

            // ── Menus & Navegação ────────────────────────────────────────
            self::f(['key' => 'menu_layout', 'label' => 'Estilo do menu', 'type' => 'select', 'group' => 'menus', 'default' => $lc['menu_layout'] ?? 'circular',
                'options' => ['circular' => 'Circular Orbital', 'normal' => 'Barra Horizontal']]),
            self::f(['key' => 'nav_position', 'label' => 'Posição do menu', 'type' => 'select', 'group' => 'menus', 'default' => $lc['nav_position'] ?? 'center',
                'options' => ['left' => 'Esquerda', 'center' => 'Centro', 'right' => 'Direita']]),
            self::f(['key' => 'normal_menu_position', 'label' => 'Posição (barra clássica)', 'type' => 'select', 'group' => 'menus', 'default' => $lc['normal_menu_position'] ?? 'horizontal-right',
                'options' => ['horizontal-left' => 'Horizontal esquerda', 'horizontal-center' => 'Horizontal centro', 'horizontal-right' => 'Horizontal direita']]),
            self::f(['key' => 'submenu_type', 'label' => 'Tipo de submenu', 'type' => 'select', 'group' => 'menus', 'default' => $lc['submenu_type'] ?? 'circular',
                'options' => ['circular' => 'Circular', 'dropdown' => 'Dropdown']]),
            self::f(['key' => 'menu_space_top', 'label' => 'Espaço acima da barra (px)', 'type' => 'range', 'group' => 'menus', 'default' => $lc['menu_space_top'] ?? 24, 'min' => 0, 'max' => 160, 'step' => 4]),
            self::f(['key' => 'menu_space_bottom', 'label' => 'Espaço abaixo da barra (px)', 'type' => 'range', 'group' => 'menus', 'default' => $lc['menu_space_bottom'] ?? 24, 'min' => 0, 'max' => 160, 'step' => 4]),

            // Customizações do Menu Orbital
            self::f(['key' => 'circular_menu_hub_text', 'label' => 'Menu Orbital — texto do hub central', 'type' => 'text', 'group' => 'menus', 'default' => $lc['circular_menu_hub_text'] ?? 'HOME']),
            self::f(['key' => 'circular_menu_hub_desc', 'label' => 'Menu Orbital — subtítulo do hub central', 'type' => 'text', 'group' => 'menus', 'default' => $lc['circular_menu_hub_desc'] ?? 'Central Hub']),
            self::f(['key' => 'circular_menu_hub_color', 'label' => 'Menu Orbital — cor do hub central', 'type' => 'color', 'group' => 'menus', 'default' => $lc['circular_menu_hub_color'] ?? '#06B6D4']),
            self::f(['key' => 'circular_menu_bg', 'label' => 'Menu Orbital — cor de fundo dos satélites', 'type' => 'color', 'group' => 'menus', 'default' => $lc['circular_menu_bg'] ?? '#0F172A']),
            self::f(['key' => 'circular_menu_text_color', 'label' => 'Menu Orbital — cor do título dos satélites', 'type' => 'color', 'group' => 'menus', 'default' => $lc['circular_menu_text_color'] ?? '#FFFFFF']),
            self::f(['key' => 'circular_menu_desc_color', 'label' => 'Menu Orbital — cor da descrição dos satélites', 'type' => 'color', 'group' => 'menus', 'default' => $lc['circular_menu_desc_color'] ?? '#94A3B8']),
            self::f(['key' => 'circular_menu_font_size', 'label' => 'Menu Orbital — tamanho de fonte (px)', 'type' => 'range', 'group' => 'menus', 'default' => $lc['circular_menu_font_size'] ?? 13, 'min' => 10, 'max' => 18, 'step' => 1]),
            self::f(['key' => 'circular_menu_font_weight', 'label' => 'Menu Orbital — peso da fonte', 'type' => 'select', 'group' => 'menus', 'default' => $lc['circular_menu_font_weight'] ?? 'bold',
                'options' => ['normal' => 'Regular', 'medium' => 'Médio', 'semibold' => 'Semi-negrito', 'bold' => 'Negrito', 'extrabold' => 'Extra-negrito']]),

            self::f(['key' => 'circular_menu_sat1_icon', 'label' => 'Menu Orbital — Satélite 1 (ícone)', 'type' => 'text', 'group' => 'menus', 'default' => $lc['circular_menu_sat1_icon'] ?? '🛸']),
            self::f(['key' => 'circular_menu_sat1_desc', 'label' => 'Menu Orbital — Satélite 1 (descrição)', 'type' => 'text', 'group' => 'menus', 'default' => $lc['circular_menu_sat1_desc'] ?? 'Operações Aéreas']),
            self::f(['key' => 'circular_menu_sat1_color', 'label' => 'Menu Orbital — Satélite 1 (cor)', 'type' => 'color', 'group' => 'menus', 'default' => $lc['circular_menu_sat1_color'] ?? '#06B6D4']),

            self::f(['key' => 'circular_menu_sat2_icon', 'label' => 'Menu Orbital — Satélite 2 (ícone)', 'type' => 'text', 'group' => 'menus', 'default' => $lc['circular_menu_sat2_icon'] ?? '🖼️']),
            self::f(['key' => 'circular_menu_sat2_desc', 'label' => 'Menu Orbital — Satélite 2 (descrição)', 'type' => 'text', 'group' => 'menus', 'default' => $lc['circular_menu_sat2_desc'] ?? 'Missões & Media']),
            self::f(['key' => 'circular_menu_sat2_color', 'label' => 'Menu Orbital — Satélite 2 (cor)', 'type' => 'color', 'group' => 'menus', 'default' => $lc['circular_menu_sat2_color'] ?? '#06B6D4']),

            self::f(['key' => 'circular_menu_sat3_icon', 'label' => 'Menu Orbital — Satélite 3 (ícone)', 'type' => 'text', 'group' => 'menus', 'default' => $lc['circular_menu_sat3_icon'] ?? '📡']),
            self::f(['key' => 'circular_menu_sat3_desc', 'label' => 'Menu Orbital — Satélite 3 (descrição)', 'type' => 'text', 'group' => 'menus', 'default' => $lc['circular_menu_sat3_desc'] ?? 'Centro de Controlo']),
            self::f(['key' => 'circular_menu_sat3_color', 'label' => 'Menu Orbital — Satélite 3 (cor)', 'type' => 'color', 'group' => 'menus', 'default' => $lc['circular_menu_sat3_color'] ?? '#06B6D4']),

            self::f(['key' => 'circular_menu_sat4_icon', 'label' => 'Menu Orbital — Satélite 4 (ícone)', 'type' => 'text', 'group' => 'menus', 'default' => $lc['circular_menu_sat4_icon'] ?? '🌐']),
            self::f(['key' => 'circular_menu_sat4_desc', 'label' => 'Menu Orbital — Satélite 4 (descrição)', 'type' => 'text', 'group' => 'menus', 'default' => $lc['circular_menu_sat4_desc'] ?? 'Missão & Equipa']),
            self::f(['key' => 'circular_menu_sat4_color', 'label' => 'Menu Orbital — Satélite 4 (cor)', 'type' => 'color', 'group' => 'menus', 'default' => $lc['circular_menu_sat4_color'] ?? '#06B6D4']),

            // ── Cores ────────────────────────────────────────────────────
            self::f(['key' => '--color-primary', 'label' => 'Primária (claro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_light', 'default' => $cl['--color-primary'] ?? '#2563EB']),
            self::f(['key' => '--color-accent', 'label' => 'Destaque (claro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_light', 'default' => $cl['--color-accent'] ?? '#06B6D4']),
            self::f(['key' => '--color-background', 'label' => 'Fundo (claro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_light', 'default' => $cl['--color-background'] ?? '#070C18']),
            self::f(['key' => '--color-foreground', 'label' => 'Texto (claro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_light', 'default' => $cl['--color-foreground'] ?? '#F3F4F6']),
            self::f(['key' => '--color-primary', 'label' => 'Primária (escuro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_dark', 'default' => $cd['--color-primary'] ?? '#3B82F6']),
            self::f(['key' => '--color-accent', 'label' => 'Destaque (escuro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_dark', 'default' => $cd['--color-accent'] ?? '#22D3EE']),
            self::f(['key' => '--color-background', 'label' => 'Fundo (escuro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_dark', 'default' => $cd['--color-background'] ?? '#030712']),
            self::f(['key' => '--color-foreground', 'label' => 'Texto (escuro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_dark', 'default' => $cd['--color-foreground'] ?? '#F9FAFB']),
            // Paleta completa — claro
            self::f(['key' => '--color-secondary', 'label' => 'Secundária (claro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_light', 'default' => $cl['--color-secondary'] ?? '#0F172A']),
            self::f(['key' => '--color-muted', 'label' => 'Suave/Muted (claro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_light', 'default' => $cl['--color-muted'] ?? '#1E293B']),
            self::f(['key' => '--color-muted-foreground', 'label' => 'Texto suave (claro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_light', 'default' => $cl['--color-muted-foreground'] ?? '#94A3B8']),
            self::f(['key' => '--color-primary-foreground', 'label' => 'Texto sobre primária (claro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_light', 'default' => $cl['--color-primary-foreground'] ?? '#FFFFFF']),
            self::f(['key' => '--color-success', 'label' => 'Sucesso (claro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_light', 'default' => $cl['--color-success'] ?? '#10B981']),
            self::f(['key' => '--color-warning', 'label' => 'Aviso (claro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_light', 'default' => $cl['--color-warning'] ?? '#F59E0B']),
            self::f(['key' => '--color-destructive', 'label' => 'Erro (claro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_light', 'default' => $cl['--color-destructive'] ?? '#EF4444']),
            // Paleta completa — escuro
            self::f(['key' => '--color-secondary', 'label' => 'Secundária (escuro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_dark', 'default' => $cd['--color-secondary'] ?? '#030712']),
            self::f(['key' => '--color-muted', 'label' => 'Suave/Muted (escuro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_dark', 'default' => $cd['--color-muted'] ?? '#0F172A']),
            self::f(['key' => '--color-muted-foreground', 'label' => 'Texto suave (escuro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_dark', 'default' => $cd['--color-muted-foreground'] ?? '#94A3B8']),
            self::f(['key' => '--color-primary-foreground', 'label' => 'Texto sobre primária (escuro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_dark', 'default' => $cd['--color-primary-foreground'] ?? '#FFFFFF']),
            self::f(['key' => '--color-success', 'label' => 'Sucesso (escuro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_dark', 'default' => $cd['--color-success'] ?? '#34D399']),
            self::f(['key' => '--color-warning', 'label' => 'Aviso (escuro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_dark', 'default' => $cd['--color-warning'] ?? '#FBBF24']),
            self::f(['key' => '--color-destructive', 'label' => 'Erro (escuro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_dark', 'default' => $cd['--color-destructive'] ?? '#F87171']),

            // ── Tipografia ───────────────────────────────────────────────
            self::f(['key' => 'heading', 'label' => 'Fonte dos títulos', 'type' => 'select', 'group' => 'tipografia', 'source' => 'font', 'default' => $fonts['heading'] ?? 'Outfit', 'options' => self::FONT_OPTIONS]),
            self::f(['key' => 'body', 'label' => 'Fonte do corpo', 'type' => 'select', 'group' => 'tipografia', 'source' => 'font', 'default' => $fonts['body'] ?? 'Inter', 'options' => self::FONT_OPTIONS]),

            // ── Fundo & HUD ──────────────────────────────────────────────
            self::f(['key' => 'hud_bg_type', 'label' => 'Tipo de fundo do HUD', 'type' => 'select', 'group' => 'fundo', 'default' => $lc['hud_bg_type'] ?? 'video',
                'options' => ['video' => 'Vídeo', 'photo' => 'Foto única', 'gallery' => 'Galeria de fotos', 'none' => 'Sem vídeo/foto (só gradiente)'], 'hint' => 'Fundo do ecrã inicial (boot / screensaver). Todos os modos têm o mesmo efeito de recuo no hover/foco.']),
            self::f(['key' => 'hud_bg_video', 'label' => 'Vídeo de fundo', 'type' => 'media_video', 'group' => 'fundo', 'default' => $lc['hud_bg_video'] ?? '/videos/aerospace-fundo.mp4', 'hint' => 'Usado quando o tipo = Vídeo.']),
            self::f(['key' => 'hud_bg_single_photo', 'label' => 'Foto de fundo', 'type' => 'media_image', 'group' => 'fundo', 'default' => $lc['hud_bg_single_photo'] ?? '/images/aerospace-hero.svg', 'hint' => 'Usada quando o tipo = Foto única.']),
            self::f(['key' => 'hud_bg_gallery', 'label' => 'Galeria de fundo', 'type' => 'media_gallery', 'group' => 'fundo', 'default' => $lc['hud_bg_gallery'] ?? [], 'hint' => 'Slideshow quando o tipo = Galeria.']),
            self::f(['key' => 'hud_overlay_enabled', 'label' => 'Overlay escuro por cima', 'type' => 'toggle', 'group' => 'fundo', 'default' => $lc['hud_overlay_enabled'] ?? true]),
            self::f(['key' => 'hero_bg_color', 'label' => 'Cor do fundo (base)', 'type' => 'color', 'group' => 'fundo', 'default' => $lc['hero_bg_color'] ?? '#0F1A2E', 'hint' => 'Fundo visível quando o screensaver esmaece ou no modo sem vídeo/foto.']),

            // Brilho do vídeo (estado inicial, antes do hover)
            self::f(['key' => 'screensaver_scrim', 'label' => 'Escurecimento do vídeo (%)', 'type' => 'range', 'group' => 'fundo', 'default' => $lc['screensaver_scrim'] ?? 10, 'min' => 0, 'max' => 100, 'step' => 5, 'hint' => 'Mais alto = vídeo mais escuro antes do hover.']),
            self::f(['key' => 'screensaver_blur', 'label' => 'Desfoque do vídeo (px)', 'type' => 'range', 'group' => 'fundo', 'default' => $lc['screensaver_blur'] ?? 0, 'min' => 0, 'max' => 8, 'step' => 1]),
            self::f(['key' => 'screensaver_video_opacity', 'label' => 'Opacidade do vídeo (%)', 'type' => 'range', 'group' => 'fundo', 'default' => $lc['screensaver_video_opacity'] ?? 100, 'min' => 0, 'max' => 100, 'step' => 5]),

            // Card da mensagem do screensaver
            self::f(['key' => 'info_card_top', 'label' => 'Card — posição vertical (%)', 'type' => 'range', 'group' => 'fundo', 'default' => $lc['info_card_top'] ?? 36, 'min' => 0, 'max' => 100, 'step' => 1, 'hint' => '50 = centro do ecrã; menor = mais acima.']),
            self::f(['key' => 'info_card_title_text', 'label' => 'Card — título (texto)', 'type' => 'text', 'group' => 'fundo', 'default' => $lc['info_card_title_text'] ?? 'AEROSPACE']),
            self::f(['key' => 'info_card_title_size', 'label' => 'Card — título (tamanho px)', 'type' => 'range', 'group' => 'fundo', 'default' => $lc['info_card_title_size'] ?? 20, 'min' => 10, 'max' => 48, 'step' => 1]),
            self::f(['key' => 'info_card_title_color', 'label' => 'Card — título (cor)', 'type' => 'color', 'group' => 'fundo', 'default' => $lc['info_card_title_color'] ?? '#FFFFFF']),
            self::f(['key' => 'info_card_subtitle_text', 'label' => 'Card — subtítulo (texto)', 'type' => 'text', 'group' => 'fundo', 'default' => $lc['info_card_subtitle_text'] ?? 'Operações & Logística Aérea']),
            self::f(['key' => 'info_card_subtitle_size', 'label' => 'Card — subtítulo (tamanho px)', 'type' => 'range', 'group' => 'fundo', 'default' => $lc['info_card_subtitle_size'] ?? 12, 'min' => 8, 'max' => 32, 'step' => 1]),
            self::f(['key' => 'info_card_subtitle_color', 'label' => 'Card — subtítulo (cor)', 'type' => 'color', 'group' => 'fundo', 'default' => $lc['info_card_subtitle_color'] ?? '#06B6D4']),
            self::f(['key' => 'info_card_hint_text', 'label' => 'Card — dica (texto)', 'type' => 'text', 'group' => 'fundo', 'default' => $lc['info_card_hint_text'] ?? 'Passe o cursor ou toque no ecrã para aceder']),
            self::f(['key' => 'info_card_hint_size', 'label' => 'Card — dica (tamanho px)', 'type' => 'range', 'group' => 'fundo', 'default' => $lc['info_card_hint_size'] ?? 10, 'min' => 8, 'max' => 24, 'step' => 1]),
            self::f(['key' => 'info_card_hint_color', 'label' => 'Card — dica (cor)', 'type' => 'color', 'group' => 'fundo', 'default' => $lc['info_card_hint_color'] ?? '#94A3B8']),

            // Telemetria HUD
            self::f(['key' => 'hud_telemetry_alt', 'label' => 'HUD — altitude padrão', 'type' => 'text', 'group' => 'fundo', 'default' => $lc['hud_telemetry_alt'] ?? '124m']),
            self::f(['key' => 'hud_telemetry_spd', 'label' => 'HUD — velocidade padrão', 'type' => 'text', 'group' => 'fundo', 'default' => $lc['hud_telemetry_spd'] ?? '42km/h']),
            self::f(['key' => 'hud_telemetry_bat', 'label' => 'HUD — bateria padrão', 'type' => 'text', 'group' => 'fundo', 'default' => $lc['hud_telemetry_bat'] ?? '88%']),
            self::f(['key' => 'hud_telemetry_althold', 'label' => 'HUD — status do altímetro', 'type' => 'text', 'group' => 'fundo', 'default' => $lc['hud_telemetry_althold'] ?? 'ON']),
            self::f(['key' => 'hud_telemetry_gps', 'label' => 'HUD — sinal GPS', 'type' => 'text', 'group' => 'fundo', 'default' => $lc['hud_telemetry_gps'] ?? 'LOCK']),
            self::f(['key' => 'hud_telemetry_navlock', 'label' => 'HUD — status de navegação', 'type' => 'text', 'group' => 'fundo', 'default' => $lc['hud_telemetry_navlock'] ?? 'OK']),

            // ── Layout & Conteúdo ────────────────────────────────────────
            self::f(['key' => 'layout_type', 'label' => 'Tipo de layout', 'type' => 'select', 'group' => 'layout', 'default' => $lc['layout_type'] ?? 'full-width',
                'options' => ['full-width' => 'Largura total', 'boxed' => 'Em caixa', 'sidebar-left' => 'Sidebar esquerda', 'sidebar-right' => 'Sidebar direita']]),
            self::f(['key' => 'max_width', 'label' => 'Largura máxima do conteúdo', 'type' => 'select', 'group' => 'layout', 'default' => (string) ($lc['max_width'] ?? '1120'),
                'options' => ['960' => '960px', '1120' => '1120px', '1280' => '1280px', '1440' => '1440px', 'full' => 'Total']]),
            self::f(['key' => 'spacing', 'label' => 'Espaçamento das secções', 'type' => 'select', 'group' => 'layout', 'default' => $lc['spacing'] ?? 'normal',
                'options' => ['compact' => 'Compacto', 'normal' => 'Normal', 'spacious' => 'Amplo']]),
            self::f(['key' => 'gallery_layout', 'label' => 'Layout da galeria', 'type' => 'select', 'group' => 'layout', 'default' => $lc['gallery_layout'] ?? '3d-carousel',
                'options' => ['3d-carousel' => 'Carrossel 3D', 'grid' => 'Grelha', 'masonry' => 'Masonry']]),
            self::f(['key' => 'gallery_auto_rotate', 'label' => 'Galeria — rotação automática', 'type' => 'toggle', 'group' => 'layout', 'default' => $lc['gallery_auto_rotate'] ?? true]),
            self::f(['key' => 'gallery_tilt_enabled', 'label' => 'Galeria — efeito de inclinação', 'type' => 'toggle', 'group' => 'layout', 'default' => $lc['gallery_tilt_enabled'] ?? true]),
            self::f(['key' => 'hero_internal_padding_top', 'label' => 'Subpáginas — Início do conteúdo (px)', 'type' => 'range', 'group' => 'layout', 'default' => $lc['hero_internal_padding_top'] ?? 50, 'min' => 0, 'max' => 160, 'step' => 4, 'hint' => 'Ajusta o espaço no topo do conteúdo nas páginas secundárias (evita sobreposição com o cabeçalho).']),

            // ── Rodapé ───────────────────────────────────────────────────
            self::f(['key' => 'footer_type', 'label' => 'Estilo do rodapé', 'type' => 'select', 'group' => 'rodape', 'default' => $lc['footer_type'] ?? 'simple',
                'options' => ['simple' => 'Simples', 'columns' => 'Colunas', 'minimal' => 'Minimal', 'dark' => 'Escuro', 'accent' => 'Destaque']]),
            self::f(['key' => 'footer_copyright', 'label' => 'Texto de copyright', 'type' => 'text', 'group' => 'rodape', 'default' => $lc['footer_copyright'] ?? '', 'hint' => 'Vazio = automático a partir do nome do site.']),
            self::f(['key' => 'contact_show_map', 'label' => 'Mostrar mapa de contacto', 'type' => 'toggle', 'group' => 'rodape', 'default' => $lc['contact_show_map'] ?? true]),
            self::f(['key' => 'contact_map_iframe', 'label' => 'Embed do mapa (iframe src)', 'type' => 'textarea', 'group' => 'rodape', 'default' => $lc['contact_map_iframe'] ?? '', 'hint' => 'URL do Google Maps embed.']),
            self::f(['key' => 'contact_show_newsletter', 'label' => 'Mostrar newsletter', 'type' => 'toggle', 'group' => 'rodape', 'default' => $lc['contact_show_newsletter'] ?? true]),
            self::f(['key' => 'footer_description', 'label' => 'Rodapé — descrição', 'type' => 'textarea', 'group' => 'rodape', 'default' => $lc['footer_description'] ?? 'Logística autónoma de carga, transporte vertical e monitorização orbital.']),
            self::f(['key' => 'footer_location', 'label' => 'Rodapé — localização', 'type' => 'text', 'group' => 'rodape', 'default' => $lc['footer_location'] ?? 'Luanda, Angola']),
            self::f(['key' => 'footer_lat', 'label' => 'Rodapé — latitude', 'type' => 'text', 'group' => 'rodape', 'default' => $lc['footer_lat'] ?? '-8.8124']),
            self::f(['key' => 'footer_lon', 'label' => 'Rodapé — longitude', 'type' => 'text', 'group' => 'rodape', 'default' => $lc['footer_lon'] ?? '13.2306']),
            self::f(['key' => 'footer_alt', 'label' => 'Rodapé — altitude', 'type' => 'text', 'group' => 'rodape', 'default' => $lc['footer_alt'] ?? '0m']),
            self::f(['key' => 'footer_status_text', 'label' => 'Rodapé — status do cockpit', 'type' => 'text', 'group' => 'rodape', 'default' => $lc['footer_status_text'] ?? 'Sistema Operacional']),

            // Contactos adicionais do Rodapé / Site
            self::f(['key' => 'contact_email', 'label' => 'E-mail comercial', 'type' => 'text', 'group' => 'rodape', 'default' => $lc['contact_email'] ?? 'comercial@aerospace.ao']),
            self::f(['key' => 'contact_phone', 'label' => 'Telefone comercial', 'type' => 'text', 'group' => 'rodape', 'default' => $lc['contact_phone'] ?? '+244 923 456 780']),
            self::f(['key' => 'contact_hours', 'label' => 'Horário de atendimento', 'type' => 'text', 'group' => 'rodape', 'default' => $lc['contact_hours'] ?? 'Seg–Sex · 08h00–18h00 (WAT)']),
            self::f(['key' => 'contact_address_hq', 'label' => 'Sede — Linha 1', 'type' => 'text', 'group' => 'rodape', 'default' => $lc['contact_address_hq'] ?? 'Rua Rainha Ginga, Edifício AeroSpace Tower']),
            self::f(['key' => 'contact_address_sub', 'label' => 'Sede — Linha 2', 'type' => 'text', 'group' => 'rodape', 'default' => $lc['contact_address_sub'] ?? 'Miramar, Luanda, Angola']),
            self::f(['key' => 'contact_address_hangar', 'label' => 'Hangar — Linha 1', 'type' => 'text', 'group' => 'rodape', 'default' => $lc['contact_address_hangar'] ?? 'Aeroporto Internacional 4 de Fevereiro']),
            self::f(['key' => 'contact_address_hangar_sub', 'label' => 'Hangar — Linha 2', 'type' => 'text', 'group' => 'rodape', 'default' => $lc['contact_address_hangar_sub'] ?? 'Zona Técnica Norte — Hangar AX, Luanda']),


            // ── Funcionalidades ──────────────────────────────────────────
            self::f(['key' => 'telemetry_enabled', 'label' => 'Painel de telemetria live', 'type' => 'toggle', 'group' => 'funcionalidades', 'default' => $lc['telemetry_enabled'] ?? false]),
            self::f(['key' => 'chat_popup_enabled', 'label' => 'Canal de apoio (popup)', 'type' => 'toggle', 'group' => 'funcionalidades', 'default' => $lc['chat_popup_enabled'] ?? true]),
            self::f(['key' => 'chat_popup_mode', 'label' => 'Canal de apoio — modo', 'type' => 'select', 'group' => 'funcionalidades', 'default' => $lc['chat_popup_mode'] ?? 'form',
                'options' => ['ai' => 'IA (chat)', 'form' => 'Formulário']]),
            self::f(['key' => 'chat_voice_commands', 'label' => 'Comandos por voz', 'type' => 'toggle', 'group' => 'funcionalidades', 'default' => $lc['chat_voice_commands'] ?? true]),
            self::f(['key' => 'preloader_terminal', 'label' => 'Preloader de consola de boot', 'type' => 'toggle', 'group' => 'funcionalidades', 'default' => $lc['preloader_terminal'] ?? true]),
            self::f(['key' => 'hover_sound_effects', 'label' => 'Efeitos sonoros no hover', 'type' => 'toggle', 'group' => 'funcionalidades', 'default' => $lc['hover_sound_effects'] ?? true]),
            self::f(['key' => 'parallax', 'label' => 'Parallax', 'type' => 'toggle', 'group' => 'funcionalidades', 'source' => 'capability', 'default' => $caps['parallax'] ?? true]),
            self::f(['key' => 'animations', 'label' => 'Animações de scroll', 'type' => 'toggle', 'group' => 'funcionalidades', 'source' => 'capability', 'default' => $caps['animations'] ?? true]),
            self::f(['key' => 'lightbox', 'label' => 'Lightbox de imagens', 'type' => 'toggle', 'group' => 'funcionalidades', 'source' => 'capability', 'default' => $caps['lightbox'] ?? true]),
            self::f(['key' => 'scroll_progress', 'label' => 'Barra de progresso de scroll', 'type' => 'toggle', 'group' => 'funcionalidades', 'source' => 'capability', 'default' => $caps['scroll_progress'] ?? true]),
            self::f(['key' => 'cookie_banner', 'label' => 'Banner de cookies', 'type' => 'toggle', 'group' => 'funcionalidades', 'source' => 'capability', 'default' => $caps['cookie_banner'] ?? true]),
        ];
    }

    /** Normaliza um campo, preenchendo defaults seguros. */
    private static function f(array $field): array
    {
        return array_merge([
            'key' => '', 'label' => '', 'type' => 'text', 'group' => 'geral',
            'default' => '', 'source' => 'layout', 'hint' => '',
        ], $field);
    }
}
