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
            self::f([
                'key' => 'show_dark_toggle', 
                'label' => 'Mostrar alternância claro/escuro', 
                'type' => 'toggle', 
                'group' => 'geral', 
                'default' => $lc['show_dark_toggle'] ?? true, 
                'hint' => 'Recomendado para melhorar a acessibilidade e conforto visual dos utilizadores em ambientes de baixa luminosidade.'
            ]),
            self::f([
                'key' => 'back_to_top', 
                'label' => 'Botão "voltar ao topo"', 
                'type' => 'toggle', 
                'group' => 'geral', 
                'default' => $lc['back_to_top'] ?? true,
                'hint' => 'Altamente recomendado para páginas longas, facilitando a navegação rápida de regresso ao cabeçalho.'
            ]),
            self::f([
                'key' => 'logo_type', 
                'label' => 'Tipo de logótipo', 
                'type' => 'select', 
                'group' => 'geral', 
                'default' => $lc['logo_type'] ?? 'both',
                'options' => ['text' => 'Apenas Texto', 'image' => 'Apenas Imagem', 'both' => 'Imagem e Texto'],
                'hint' => 'Selecione "Imagem e Texto" para maximizar a identidade de marca, ou "Apenas Imagem" para um aspeto ultra-minimalista.'
            ]),
            self::f([
                'key' => 'logo_image_light', 
                'label' => 'Logótipo — imagem (Modo Claro)', 
                'type' => 'media_image', 
                'group' => 'geral', 
                'default' => $lc['logo_image_light'] ?? '/images/aerospace-logo.svg',
                'hint' => 'Carregue o logótipo em formato SVG ou PNG transparente (recomenda-se altura entre 30px e 50px para manter a proporção).'
            ]),
            self::f([
                'key' => 'logo_image_dark', 
                'label' => 'Logótipo — imagem (Modo Escuro)', 
                'type' => 'media_image', 
                'group' => 'geral', 
                'default' => $lc['logo_image_dark'] ?? '',
                'hint' => 'Versão alternativa do logótipo para fundos escuros. Se deixado vazio, o sistema usará o logótipo do modo claro.'
            ]),
            self::f([
                'key' => 'logo_height', 
                'label' => 'Logótipo — altura (px)', 
                'type' => 'range', 
                'group' => 'geral', 
                'default' => $lc['logo_height'] ?? 36, 
                'min' => 20, 
                'max' => 100, 
                'step' => 2,
                'hint' => 'Ajuste fino da altura do logótipo. O padrão recomendado é 36px para garantir harmonia no cabeçalho.'
            ]),
            self::f([
                'key' => 'logo_text_size', 
                'label' => 'Logótipo — tamanho do texto (px)', 
                'type' => 'range', 
                'group' => 'geral', 
                'default' => $lc['logo_text_size'] ?? 20, 
                'min' => 14, 
                'max' => 36, 
                'step' => 1,
                'hint' => 'Tamanho da fonte para o nome da marca. Recomendado entre 18px e 24px para perfeito equilíbrio legível.'
            ]),
            self::f([
                'key' => 'logo_text_weight', 
                'label' => 'Logótipo — espessura do texto', 
                'type' => 'select', 
                'group' => 'geral', 
                'default' => $lc['logo_text_weight'] ?? 'bold',
                'options' => ['normal' => 'Regular', 'medium' => 'Médio', 'semibold' => 'Semi-negrito', 'bold' => 'Negrito', 'extrabold' => 'Extra-negrito'],
                'hint' => 'Espessura do texto do nome da marca. Use "Negrito" para maior destaque ou "Regular" para sofisticação.'
            ]),
            self::f([
                'key' => 'favicon_image', 
                'label' => 'Favicon do site', 
                'type' => 'media_image', 
                'group' => 'geral', 
                'default' => $lc['favicon_image'] ?? '/favicon.ico',
                'hint' => 'Ícone do separador do browser. Carregue um ficheiro quadrado de 32x32px (formatos .ico ou .png) para melhor definição.'
            ]),

            // ── Cabeçalho ────────────────────────────────────────────────
            self::f([
                'key' => 'header_type', 
                'label' => 'Estilo do cabeçalho', 
                'type' => 'select', 
                'group' => 'cabecalho', 
                'default' => $lc['header_type'] ?? 'glass',
                'options' => ['glass' => 'Glass / Blur', 'solid' => 'Sólido', 'transparent' => 'Transparente', 'centered' => 'Logo centrado', 'sidebar' => 'Sidebar'],
                'hint' => 'Estilo de exibição do cabeçalho. O efeito "Glass / Blur" é a assinatura de design recomendada para este tema holográfico.'
            ]),
            self::f([
                'key' => 'header_sticky', 
                'label' => 'Cabeçalho fixo (sticky)', 
                'type' => 'toggle', 
                'group' => 'cabecalho', 
                'default' => $lc['header_sticky'] ?? true,
                'hint' => 'Fixa o cabeçalho no topo ao fazer scroll. Altamente recomendado para manter a barra de navegação sempre acessível.'
            ]),
            self::f([
                'key' => 'header_cta_text', 
                'label' => 'Botão CTA — texto', 
                'type' => 'text', 
                'group' => 'cabecalho', 
                'default' => $lc['header_cta_text'] ?? '', 
                'hint' => 'Texto para o botão de chamada de ação principal no cabeçalho (ex: "Iniciar Missão"). Deixe vazio para ocultar o botão.'
            ]),
            self::f([
                'key' => 'header_cta_url', 
                'label' => 'Botão CTA — URL', 
                'type' => 'text', 
                'group' => 'cabecalho', 
                'default' => $lc['header_cta_url'] ?? '#',
                'hint' => 'Link de destino do botão de ação principal (ex: /contacto ou ancoragem local como #contact).'
            ]),
            self::f([
                'key' => 'navbar_color', 
                'label' => 'Barra de menus — cor', 
                'type' => 'color', 
                'group' => 'cabecalho', 
                'default' => $lc['navbar_color'] ?? '#1E293B',
                'hint' => 'Cor de fundo da barra de navegação. Recomenda-se tons neutros e escuros para manter a legibilidade com a transparência.'
            ]),
            self::f([
                'key' => 'navbar_opacity', 
                'label' => 'Barra de menus — opacidade (%)', 
                'type' => 'range', 
                'group' => 'cabecalho', 
                'default' => $lc['navbar_opacity'] ?? 72, 
                'min' => 0, 
                'max' => 100, 
                'step' => 5, 
                'hint' => 'Opacidade da barra de menus. Recomendado manter entre 65% e 80% para garantir um efeito translúcido visível e elegante.'
            ]),

            // ── Menus & Navegação ────────────────────────────────────────
            self::f([
                'key' => 'menu_layout', 
                'label' => 'Estilo do menu', 
                'type' => 'select', 
                'group' => 'menus', 
                'default' => $lc['menu_layout'] ?? 'circular',
                'options' => ['circular' => 'Circular Orbital', 'normal' => 'Barra Horizontal'],
                'hint' => 'O menu "Circular Orbital" é a assinatura de design premium deste tema. Use "Barra Horizontal" para menus mais extensos.'
            ]),
            self::f([
                'key' => 'nav_position', 
                'label' => 'Posição do menu', 
                'type' => 'select', 
                'group' => 'menus', 
                'default' => $lc['nav_position'] ?? 'center',
                'options' => ['left' => 'Esquerda', 'center' => 'Centro', 'right' => 'Direita'],
                'hint' => 'Alinhamento do menu orbital no ecrã. O alinhamento ao centro oferece o melhor equilíbrio visual de radar.'
            ]),
            self::f([
                'key' => 'normal_menu_position', 
                'label' => 'Posição (barra clássica)', 
                'type' => 'select', 
                'group' => 'menus', 
                'default' => $lc['normal_menu_position'] ?? 'horizontal-right',
                'options' => ['horizontal-left' => 'Horizontal esquerda', 'horizontal-center' => 'Horizontal centro', 'horizontal-right' => 'Horizontal direita'],
                'hint' => 'Posicionamento da barra de menu clássica horizontal em relação ao logótipo.'
            ]),
            self::f([
                'key' => 'submenu_type', 
                'label' => 'Tipo de submenu', 
                'type' => 'select', 
                'group' => 'menus', 
                'default' => $lc['submenu_type'] ?? 'circular',
                'options' => ['circular' => 'Circular', 'dropdown' => 'Dropdown'],
                'hint' => 'Estilo de abertura dos submenus. Recomenda-se "Circular" se o layout do menu principal for orbital.'
            ]),
            self::f([
                'key' => 'menu_space_top', 
                'label' => 'Espaço acima da barra (px)', 
                'type' => 'range', 
                'group' => 'menus', 
                'default' => $lc['menu_space_top'] ?? 24, 
                'min' => 0, 
                'max' => 160, 
                'step' => 4,
                'hint' => 'Espaçamento superior da barra de navegação. Recomendado 24px para dar respirabilidade ao cabeçalho.'
            ]),
            self::f([
                'key' => 'menu_space_bottom', 
                'label' => 'Espaço abaixo da barra (px)', 
                'type' => 'range', 
                'group' => 'menus', 
                'default' => $lc['menu_space_bottom'] ?? 24, 
                'min' => 0, 
                'max' => 160, 
                'step' => 4,
                'hint' => 'Espaçamento inferior da barra de navegação. Evita que o conteúdo inicial da página colida com o menu.'
            ]),

            // Customizações do Menu Orbital
            self::f([
                'key' => 'circular_menu_hub_text', 
                'label' => 'Menu Orbital — texto do hub central', 
                'type' => 'text', 
                'group' => 'menus', 
                'default' => $lc['circular_menu_hub_text'] ?? 'HOME',
                'hint' => 'Texto exibido no círculo central do menu orbital. Curto e em maiúsculas (ex: "HOME" ou "MENU").'
            ]),
            self::f([
                'key' => 'circular_menu_hub_desc', 
                'label' => 'Menu Orbital — subtítulo do hub central', 
                'type' => 'text', 
                'group' => 'menus', 
                'default' => $lc['circular_menu_hub_desc'] ?? 'Central Hub',
                'hint' => 'Legenda secundária no hub central do menu orbital (ex: "Central Hub", "Navegação").'
            ]),
            self::f([
                'key' => 'circular_menu_hub_color', 
                'label' => 'Menu Orbital — cor do hub central', 
                'type' => 'color', 
                'group' => 'menus', 
                'default' => $lc['circular_menu_hub_color'] ?? '#06B6D4',
                'hint' => 'Cor de fundo do círculo central. Recomendado usar a cor de acento do tema (ex: ciano) para maior atratividade.'
            ]),
            self::f([
                'key' => 'circular_menu_bg', 
                'label' => 'Menu Orbital — cor de fundo dos satélites', 
                'type' => 'color', 
                'group' => 'menus', 
                'default' => $lc['circular_menu_bg'] ?? '#0F172A',
                'hint' => 'Cor de fundo dos botões satélites orbitais. Deve contrastar claramente com o fundo geral do site.'
            ]),
            self::f([
                'key' => 'circular_menu_text_color', 
                'label' => 'Menu Orbital — cor do título dos satélites', 
                'type' => 'color', 
                'group' => 'menus', 
                'default' => $lc['circular_menu_text_color'] ?? '#FFFFFF',
                'hint' => 'Cor de fonte das legendas dos botões satélites. Branco ou cinza claro é o ideal para leitura.'
            ]),
            self::f([
                'key' => 'circular_menu_desc_color', 
                'label' => 'Menu Orbital — cor da descrição dos satélites', 
                'type' => 'color', 
                'group' => 'menus', 
                'default' => $lc['circular_menu_desc_color'] ?? '#94A3B8',
                'hint' => 'Cor de fonte da descrição de apoio dos botões satélites. Deve ser mais suave que a cor do título.'
            ]),
            self::f([
                'key' => 'circular_menu_font_size', 
                'label' => 'Menu Orbital — tamanho de fonte (px)', 
                'type' => 'range', 
                'group' => 'menus', 
                'default' => $lc['circular_menu_font_size'] ?? 13, 
                'min' => 10, 
                'max' => 18, 
                'step' => 1,
                'hint' => 'Tamanho da fonte das legendas dos satélites. Mantido entre 12px e 14px para garantir que não quebra em duas linhas.'
            ]),
            self::f([
                'key' => 'circular_menu_font_weight', 
                'label' => 'Menu Orbital — peso da fonte', 
                'type' => 'select', 
                'group' => 'menus', 
                'default' => $lc['circular_menu_font_weight'] ?? 'bold',
                'options' => ['normal' => 'Regular', 'medium' => 'Médio', 'semibold' => 'Semi-negrito', 'bold' => 'Negrito', 'extrabold' => 'Extra-negrito'],
                'hint' => 'Espessura da fonte das legendas. "Negrito" ou "Semi-negrito" é altamente recomendado para legibilidade rápida.'
            ]),

            self::f(['key' => 'circular_menu_sat1_icon', 'label' => 'Menu Orbital — Satélite 1 (ícone)', 'type' => 'text', 'group' => 'menus', 'default' => $lc['circular_menu_sat1_icon'] ?? '🛸', 'hint' => 'Ícone do satélite 1 (Emoji ou caractere unicode).']),
            self::f(['key' => 'circular_menu_sat1_desc', 'label' => 'Menu Orbital — Satélite 1 (descrição)', 'type' => 'text', 'group' => 'menus', 'default' => $lc['circular_menu_sat1_desc'] ?? 'Operações Aéreas', 'hint' => 'Descrição curta do satélite 1 exibida em hover.']),
            self::f(['key' => 'circular_menu_sat1_color', 'label' => 'Menu Orbital — Satélite 1 (cor)', 'type' => 'color', 'group' => 'menus', 'default' => $lc['circular_menu_sat1_color'] ?? '#06B6D4', 'hint' => 'Cor de acento individual do botão satélite 1.']),

            self::f(['key' => 'circular_menu_sat2_icon', 'label' => 'Menu Orbital — Satélite 2 (ícone)', 'type' => 'text', 'group' => 'menus', 'default' => $lc['circular_menu_sat2_icon'] ?? '🖼️', 'hint' => 'Ícone do satélite 2 (Emoji ou caractere unicode).']),
            self::f(['key' => 'circular_menu_sat2_desc', 'label' => 'Menu Orbital — Satélite 2 (descrição)', 'type' => 'text', 'group' => 'menus', 'default' => $lc['circular_menu_sat2_desc'] ?? 'Missões & Media', 'hint' => 'Descrição curta do satélite 2 exibida em hover.']),
            self::f(['key' => 'circular_menu_sat2_color', 'label' => 'Menu Orbital — Satélite 2 (cor)', 'type' => 'color', 'group' => 'menus', 'default' => $lc['circular_menu_sat2_color'] ?? '#06B6D4', 'hint' => 'Cor de acento individual do botão satélite 2.']),

            self::f(['key' => 'circular_menu_sat3_icon', 'label' => 'Menu Orbital — Satélite 3 (ícone)', 'type' => 'text', 'group' => 'menus', 'default' => $lc['circular_menu_sat3_icon'] ?? '📡', 'hint' => 'Ícone do satélite 3 (Emoji ou caractere unicode).']),
            self::f(['key' => 'circular_menu_sat3_desc', 'label' => 'Menu Orbital — Satélite 3 (descrição)', 'type' => 'text', 'group' => 'menus', 'default' => $lc['circular_menu_sat3_desc'] ?? 'Centro de Controlo', 'hint' => 'Descrição curta do satélite 3 exibida em hover.']),
            self::f(['key' => 'circular_menu_sat3_color', 'label' => 'Menu Orbital — Satélite 3 (cor)', 'type' => 'color', 'group' => 'menus', 'default' => $lc['circular_menu_sat3_color'] ?? '#06B6D4', 'hint' => 'Cor de acento individual do botão satélite 3.']),

            self::f(['key' => 'circular_menu_sat4_icon', 'label' => 'Menu Orbital — Satélite 4 (ícone)', 'type' => 'text', 'group' => 'menus', 'default' => $lc['circular_menu_sat4_icon'] ?? '🌐', 'hint' => 'Ícone do satélite 4 (Emoji ou caractere unicode).']),
            self::f(['key' => 'circular_menu_sat4_desc', 'label' => 'Menu Orbital — Satélite 4 (descrição)', 'type' => 'text', 'group' => 'menus', 'default' => $lc['circular_menu_sat4_desc'] ?? 'Missão & Equipa', 'hint' => 'Descrição curta do satélite 4 exibida em hover.']),
            self::f(['key' => 'circular_menu_sat4_color', 'label' => 'Menu Orbital — Satélite 4 (cor)', 'type' => 'color', 'group' => 'menus', 'default' => $lc['circular_menu_sat4_color'] ?? '#06B6D4', 'hint' => 'Cor de acento individual do botão satélite 4.']),

            // ── Cores ────────────────────────────────────────────────────
            self::f(['key' => '--color-primary', 'label' => 'Primária (claro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_light', 'default' => $cl['--color-primary'] ?? '#2563EB', 'hint' => 'Cor de marca principal para o modo claro. Usada em botões e elementos estruturais destacados.']),
            self::f(['key' => '--color-accent', 'label' => 'Destaque (claro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_light', 'default' => $cl['--color-accent'] ?? '#06B6D4', 'hint' => 'Cor de destaque/acento para o modo claro. Ideal para links, ícones e estados ativos.']),
            self::f(['key' => '--color-background', 'label' => 'Fundo (claro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_light', 'default' => $cl['--color-background'] ?? '#070C18', 'hint' => 'Cor de fundo geral do modo claro. Recomenda-se tons neutros muito claros ou off-white para legibilidade.']),
            self::f(['key' => '--color-foreground', 'label' => 'Texto (claro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_light', 'default' => $cl['--color-foreground'] ?? '#F3F4F6', 'hint' => 'Cor principal do texto no modo claro. Deve ser muito escura (ex: cinza grafite) para garantir um contraste confortável.']),
            
            self::f(['key' => '--color-primary', 'label' => 'Primária (escuro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_dark', 'default' => $cd['--color-primary'] ?? '#3B82F6', 'hint' => 'Cor de marca principal para o modo escuro. Geralmente uma variação mais brilhante ou pastel da cor primária original.']),
            self::f(['key' => '--color-accent', 'label' => 'Destaque (escuro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_dark', 'default' => $cd['--color-accent'] ?? '#22D3EE', 'hint' => 'Cor de destaque/acento para o modo escuro. Tons néon (como o ciano elétrico) oferecem o melhor visual holográfico.']),
            self::f(['key' => '--color-background', 'label' => 'Fundo (escuro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_dark', 'default' => $cd['--color-background'] ?? '#030712', 'hint' => 'Cor de fundo geral no modo escuro. Recomenda-se um tom espacial muito escuro (#030712 ou #070C18) para a melhor imersão.']),
            self::f(['key' => '--color-foreground', 'label' => 'Texto (escuro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_dark', 'default' => $cd['--color-foreground'] ?? '#F9FAFB', 'hint' => 'Cor principal do texto no modo escuro. Deve ser branca ou cinza muito claro para evitar cansaço visual.']),
            
            // Paleta completa — claro
            self::f(['key' => '--color-secondary', 'label' => 'Secundária (claro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_light', 'default' => $cl['--color-secondary'] ?? '#0F172A', 'hint' => 'Cor secundária usada em painéis ou cartões secundários de informação no modo claro.']),
            self::f(['key' => '--color-muted', 'label' => 'Suave/Muted (claro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_light', 'default' => $cl['--color-muted'] ?? '#1E293B', 'hint' => 'Cor cinzenta suave usada para bordas, separadores e elementos decorativos discretos.']),
            self::f(['key' => '--color-muted-foreground', 'label' => 'Texto suave (claro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_light', 'default' => $cl['--color-muted-foreground'] ?? '#94A3B8', 'hint' => 'Cor para textos secundários ou legendas. Deve ser mais suave que a cor principal.']),
            self::f(['key' => '--color-primary-foreground', 'label' => 'Texto sobre primária (claro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_light', 'default' => $cl['--color-primary-foreground'] ?? '#FFFFFF', 'hint' => 'Cor do texto quando colocado em cima de um fundo com a Cor Primária (normalmente branco).']),
            self::f(['key' => '--color-success', 'label' => 'Sucesso (claro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_light', 'default' => $cl['--color-success'] ?? '#10B981', 'hint' => 'Cor para mensagens de sucesso ou estados operacionais válidos (verde).']),
            self::f(['key' => '--color-warning', 'label' => 'Aviso (claro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_light', 'default' => $cl['--color-warning'] ?? '#F59E0B', 'hint' => 'Cor para alertas de atenção ou estados intermédios (laranja).']),
            self::f(['key' => '--color-destructive', 'label' => 'Erro (claro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_light', 'default' => $cl['--color-destructive'] ?? '#EF4444', 'hint' => 'Cor para mensagens de erro ou ações críticas destrutivas (vermelho).']),
            
            // Paleta completa — escuro
            self::f(['key' => '--color-secondary', 'label' => 'Secundária (escuro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_dark', 'default' => $cd['--color-secondary'] ?? '#030712', 'hint' => 'Cor secundária usada em painéis ou cartões secundários de informação no modo escuro.']),
            self::f(['key' => '--color-muted', 'label' => 'Suave/Muted (escuro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_dark', 'default' => $cd['--color-muted'] ?? '#0F172A', 'hint' => 'Cor cinzenta suave usada para bordas e separadores no modo escuro.']),
            self::f(['key' => '--color-muted-foreground', 'label' => 'Texto suave (escuro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_dark', 'default' => $cd['--color-muted-foreground'] ?? '#94A3B8', 'hint' => 'Cor para textos secundários ou legendas no modo escuro.']),
            self::f(['key' => '--color-primary-foreground', 'label' => 'Texto sobre primária (escuro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_dark', 'default' => $cd['--color-primary-foreground'] ?? '#FFFFFF', 'hint' => 'Cor do texto quando colocado em cima de um fundo com a Cor Primária (normalmente branco).']),
            self::f(['key' => '--color-success', 'label' => 'Sucesso (escuro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_dark', 'default' => $cd['--color-success'] ?? '#34D399', 'hint' => 'Cor para mensagens de sucesso ou estados operacionais válidos no modo escuro (verde brilhante).']),
            self::f(['key' => '--color-warning', 'label' => 'Aviso (escuro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_dark', 'default' => $cd['--color-warning'] ?? '#FBBF24', 'hint' => 'Cor para alertas de atenção ou estados intermédios no modo escuro (amarelo brilhante).']),
            self::f(['key' => '--color-destructive', 'label' => 'Erro (escuro)', 'type' => 'color', 'group' => 'cores', 'source' => 'color_dark', 'default' => $cd['--color-destructive'] ?? '#F87171', 'hint' => 'Cor para mensagens de erro ou ações críticas destrutivas no modo escuro (vermelho brilhante).']),

            // ── Tipografia ───────────────────────────────────────────────
            self::f([
                'key' => 'heading', 
                'label' => 'Fonte dos títulos', 
                'type' => 'select', 
                'group' => 'tipografia', 
                'source' => 'font', 
                'default' => $fonts['heading'] ?? 'Outfit', 
                'options' => self::FONT_OPTIONS,
                'hint' => 'Tipo de letra utilizado em todos os títulos (H1, H2, H3). Fontes geométricas modernas (ex: Outfit, Sora) são altamente recomendadas.'
            ]),
            self::f([
                'key' => 'body', 
                'label' => 'Fonte do corpo', 
                'type' => 'select', 
                'group' => 'tipografia', 
                'source' => 'font', 
                'default' => $fonts['body'] ?? 'Inter', 
                'options' => self::FONT_OPTIONS,
                'hint' => 'Tipo de letra usado no corpo de texto e parágrafos. Recomenda-se fontes limpas, neutras e de fácil leitura (ex: Inter, DM Sans).'
            ]),

            // ── Fundo & HUD ──────────────────────────────────────────────
            self::f([
                'key' => 'hud_bg_type', 
                'label' => 'Tipo de fundo do HUD', 
                'type' => 'select', 
                'group' => 'fundo', 
                'default' => $lc['hud_bg_type'] ?? 'video',
                'options' => ['video' => 'Vídeo', 'photo' => 'Foto única', 'gallery' => 'Galeria de fotos', 'none' => 'Sem vídeo/foto (só gradiente)'], 
                'hint' => 'Fundo do ecrã inicial (boot / screensaver). Todos os modos têm o mesmo efeito de recuo tridimensional (tilt/parallax) no hover/foco.'
            ]),
            self::f([
                'key' => 'hud_bg_video', 
                'label' => 'Vídeo de fundo', 
                'type' => 'media_video', 
                'group' => 'fundo', 
                'default' => $lc['hud_bg_video'] ?? '/videos/aerospace-fundo.mp4', 
                'hint' => 'Vídeo de fundo do screensaver. Use um ficheiro MP4 leve (idealmente < 5MB) com loop perfeito e sem áudio.'
            ]),
            self::f([
                'key' => 'hud_bg_single_photo', 
                'label' => 'Foto de fundo', 
                'type' => 'media_image', 
                'group' => 'fundo', 
                'default' => $lc['hud_bg_single_photo'] ?? '/images/aerospace-hero.svg', 
                'hint' => 'Imagem de fundo estática. Recomendado usar uma imagem SVG ou WebP de alta resolução (1920x1080px).'
            ]),
            self::f([
                'key' => 'hud_bg_gallery', 
                'label' => 'Galeria de fundo', 
                'type' => 'media_gallery', 
                'group' => 'fundo', 
                'default' => $lc['hud_bg_gallery'] ?? [], 
                'hint' => 'Carregue um conjunto de imagens para criar um slideshow dinâmico de fundo no screensaver.'
            ]),
            self::f([
                'key' => 'hud_overlay_enabled', 
                'label' => 'Overlay escuro por cima', 
                'type' => 'toggle', 
                'group' => 'fundo', 
                'default' => $lc['hud_overlay_enabled'] ?? true,
                'hint' => 'Aplica uma camada escura semi-transparente sobre o vídeo/imagem para garantir que os textos da telemetria e o título são fáceis de ler.'
            ]),
            self::f([
                'key' => 'hero_bg_color', 
                'label' => 'Cor do fundo (base)', 
                'type' => 'color', 
                'group' => 'fundo', 
                'default' => $lc['hero_bg_color'] ?? '#0F1A2E', 
                'hint' => 'Fundo visível quando o screensaver esmaece ou no modo sem vídeo/foto. Recomenda-se um azul-escuro profundo (#0F1A2E).'
            ]),
            self::f([
                'key' => 'screensaver_scrim', 
                'label' => 'Escurecimento do vídeo (%)', 
                'type' => 'range', 
                'group' => 'fundo', 
                'default' => $lc['screensaver_scrim'] ?? 10, 
                'min' => 0, 
                'max' => 100, 
                'step' => 5, 
                'hint' => 'Mais alto = vídeo mais escuro antes do utilizador interagir. Protege a legibilidade do painel central.'
            ]),
            self::f([
                'key' => 'screensaver_blur', 
                'label' => 'Desfoque do vídeo (px)', 
                'type' => 'range', 
                'group' => 'fundo', 
                'default' => $lc['screensaver_blur'] ?? 0, 
                'min' => 0, 
                'max' => 8, 
                'step' => 1,
                'hint' => 'Aplica desfoque no vídeo/imagem antes da interação. Recomendado manter em 0px para máxima nitidez de cockpit.'
            ]),
            self::f([
                'key' => 'screensaver_video_opacity', 
                'label' => 'Opacidade do vídeo (%)', 
                'type' => 'range', 
                'group' => 'fundo', 
                'default' => $lc['screensaver_video_opacity'] ?? 100, 
                'min' => 0, 
                'max' => 100, 
                'step' => 5,
                'hint' => 'Opacidade do vídeo de fundo. Valores inferiores a 100% misturam o vídeo com a Cor do Fundo (base) por trás.'
            ]),

            self::f([
                'key' => 'info_card_top', 
                'label' => 'Card — posição vertical (%)', 
                'type' => 'range', 
                'group' => 'fundo', 
                'default' => $lc['info_card_top'] ?? 36, 
                'min' => 0, 
                'max' => 100, 
                'step' => 1, 
                'hint' => 'Posição do painel central no ecrã. 50% é o centro vertical exato; valores mais baixos movem o painel mais para o topo.'
            ]),
            self::f([
                'key' => 'info_card_title_text', 
                'label' => 'Card — título (texto)', 
                'type' => 'text', 
                'group' => 'fundo', 
                'default' => $lc['info_card_title_text'] ?? 'AEROSPACE',
                'hint' => 'Título principal exibido no centro do screensaver (ex: nome do projeto ou marca).'
            ]),
            self::f([
                'key' => 'info_card_title_size', 
                'label' => 'Card — título (tamanho px)', 
                'type' => 'range', 
                'group' => 'fundo', 
                'default' => $lc['info_card_title_size'] ?? 20, 
                'min' => 10, 
                'max' => 48, 
                'step' => 1,
                'hint' => 'Tamanho da fonte para o título central em pixels. Padrão recomendado: 20px a 28px.'
            ]),
            self::f([
                'key' => 'info_card_title_color', 
                'label' => 'Card — título (cor)', 
                'type' => 'color', 
                'group' => 'fundo', 
                'default' => $lc['info_card_title_color'] ?? '#FFFFFF',
                'hint' => 'Cor do título central do screensaver. Normalmente branco (#FFFFFF) para se destacar sobre fundos escuros.'
            ]),
            self::f([
                'key' => 'info_card_subtitle_text', 
                'label' => 'Card — subtítulo (texto)', 
                'type' => 'text', 
                'group' => 'fundo', 
                'default' => $lc['info_card_subtitle_text'] ?? 'Operações & Logística Aérea',
                'hint' => 'Legenda pequena exibida abaixo do título principal (ex: slogan ou setor).'
            ]),
            self::f([
                'key' => 'info_card_subtitle_size', 
                'label' => 'Card — subtítulo (tamanho px)', 
                'type' => 'range', 
                'group' => 'fundo', 
                'default' => $lc['info_card_subtitle_size'] ?? 12, 
                'min' => 8, 
                'max' => 32, 
                'step' => 1,
                'hint' => 'Tamanho da fonte para o subtítulo central. Mantido entre 11px e 13px para um visual refinado.'
            ]),
            self::f([
                'key' => 'info_card_subtitle_color', 
                'label' => 'Card — subtítulo (cor)', 
                'type' => 'color', 
                'group' => 'fundo', 
                'default' => $lc['info_card_subtitle_color'] ?? '#06B6D4',
                'hint' => 'Cor do subtítulo central. Recomenda-se a cor de acento (ciano) para um aspeto de telemetria científica ativa.'
            ]),
            self::f([
                'key' => 'info_card_hint_text', 
                'label' => 'Card — dica (texto)', 
                'type' => 'text', 
                'group' => 'fundo', 
                'default' => $lc['info_card_hint_text'] ?? 'Passe o cursor ou toque no ecrã para aceder',
                'hint' => 'Instrução para ensinar o utilizador a desbloquear a página (ex: "Passe o cursor para aceder").'
            ]),
            self::f([
                'key' => 'info_card_hint_size', 
                'label' => 'Card — dica (tamanho px)', 
                'type' => 'range', 
                'group' => 'fundo', 
                'default' => $lc['info_card_hint_size'] ?? 10, 
                'min' => 8, 
                'max' => 24, 
                'step' => 1,
                'hint' => 'Tamanho do texto da instrução. Deve ser muito pequeno (ex: 9px a 11px) para não sobrecarregar.'
            ]),
            self::f([
                'key' => 'info_card_hint_color', 
                'label' => 'Card — dica (cor)', 
                'type' => 'color', 
                'group' => 'fundo', 
                'default' => $lc['info_card_hint_color'] ?? '#94A3B8',
                'hint' => 'Cor do texto da instrução. Deve ser discreta (ex: cinza neutro) para indicar que é uma ação secundária.'
            ]),

            // Telemetria HUD
            self::f(['key' => 'hud_telemetry_alt', 'label' => 'HUD — altitude padrão', 'type' => 'text', 'group' => 'fundo', 'default' => $lc['hud_telemetry_alt'] ?? '124m', 'hint' => 'Altitude padrão exibida na telemetria lateral. O JavaScript fará este valor flutuar suavemente para simular voo.']),
            self::f(['key' => 'hud_telemetry_spd', 'label' => 'HUD — velocidade padrão', 'type' => 'text', 'group' => 'fundo', 'default' => $lc['hud_telemetry_spd'] ?? '42km/h', 'hint' => 'Velocidade padrão na telemetria lateral. Flutua dinamicamente com variação de vento via JS.']),
            self::f(['key' => 'hud_telemetry_bat', 'label' => 'HUD — bateria padrão', 'type' => 'text', 'group' => 'fundo', 'default' => $lc['hud_telemetry_bat'] ?? '88%', 'hint' => 'Nível de bateria inicial do painel. Desce ligeiramente ao longo do tempo para simular consumo real.']),
            self::f(['key' => 'hud_telemetry_althold', 'label' => 'HUD — status do altímetro', 'type' => 'text', 'group' => 'fundo', 'default' => $lc['hud_telemetry_althold'] ?? 'ON', 'hint' => 'Estado do estabilizador de altitude no painel (ex: "ON", "LOCK").']),
            self::f(['key' => 'hud_telemetry_gps', 'label' => 'HUD — sinal GPS', 'type' => 'text', 'group' => 'fundo', 'default' => $lc['hud_telemetry_gps'] ?? 'LOCK', 'hint' => 'Estado de fixação de satélites na telemetria (ex: "LOCK", "SEARCHING").']),
            self::f(['key' => 'hud_telemetry_navlock', 'label' => 'HUD — status de navegação', 'type' => 'text', 'group' => 'fundo', 'default' => $lc['hud_telemetry_navlock'] ?? 'OK', 'hint' => 'Status do piloto automático/navegação (ex: "OK", "AUTO").']),
            self::f(['key' => 'hud_telemetry_drones_count', 'label' => 'HUD — drones operacionais', 'type' => 'text', 'group' => 'fundo', 'default' => $lc['hud_telemetry_drones_count'] ?? '07 ONLINE', 'hint' => 'Mensagem de frota ativa na telemetria (ex: "07 ONLINE", "ACTIVE").']),

            // ── Layout & Conteúdo ────────────────────────────────────────
            self::f([
                'key' => 'layout_type', 
                'label' => 'Tipo de layout', 
                'type' => 'select', 
                'group' => 'layout', 
                'default' => $lc['layout_type'] ?? 'full-width',
                'options' => ['full-width' => 'Largura total', 'boxed' => 'Em caixa', 'sidebar-left' => 'Sidebar esquerda', 'sidebar-right' => 'Sidebar direita'],
                'hint' => 'Estrutura de layout das páginas. "Largura total" maximiza a imersão visual do tema AeroSpace. "Boxed" ou "Sidebar" são recomendados para portais de dados.'
            ]),
            self::f([
                'key' => 'max_width', 
                'label' => 'Largura máxima do conteúdo', 
                'type' => 'select', 
                'group' => 'layout', 
                'default' => (string) ($lc['max_width'] ?? '1120'),
                'options' => ['960' => '960px', '1120' => '1120px', '1280' => '1280px', '1440' => '1440px', 'full' => 'Total'],
                'hint' => 'Largura limite para textos e cartões. 1120px ou 1280px é o padrão ideal para leitura confortável em monitores desktop.'
            ]),
            self::f([
                'key' => 'spacing', 
                'label' => 'Espaçamento das secções', 
                'type' => 'select', 
                'group' => 'layout', 
                'default' => $lc['spacing'] ?? 'normal',
                'options' => ['compact' => 'Compacto', 'normal' => 'Normal', 'spacious' => 'Amplo'],
                'hint' => 'Margem vertical entre as diferentes secções do site. O espaçamento "Normal" oferece o melhor respiro visual para o utilizador.'
            ]),
            self::f([
                'key' => 'hero_internal_padding_top', 
                'label' => 'Subpáginas — Início do conteúdo (px)', 
                'type' => 'range', 
                'group' => 'layout', 
                'default' => $lc['hero_internal_padding_top'] ?? 50, 
                'min' => 0, 
                'max' => 160, 
                'step' => 4, 
                'hint' => 'Espaçamento no topo do conteúdo das subpáginas secundárias. Evita que o cabeçalho fixo (sticky) sobreponha os títulos.'
            ]),

            // ── Galeria ──────────────────────────────────────────────────
            self::f([
                'key' => 'gallery_layout', 
                'label' => 'Layout da galeria', 
                'type' => 'select', 
                'group' => 'galeria', 
                'default' => $lc['gallery_layout'] ?? '3d-carousel',
                'options' => ['3d-carousel' => 'Carrossel 3D', 'grid' => 'Grelha', 'masonry' => 'Masonry'], 
                'hint' => 'Estilo da galeria. Nota: O Carrossel 3D é ideal para 3 a 12 imagens (mínimo 3). Mosaico e Grelha suportam dezenas de imagens com carregamento rápido.'
            ]),
            self::f([
                'key' => 'gallery_auto_rotate', 
                'label' => 'Galeria — rotação automática', 
                'type' => 'toggle', 
                'group' => 'galeria', 
                'default' => $lc['gallery_auto_rotate'] ?? true,
                'hint' => 'Ativa a rotação contínua e autónoma das imagens no modo Carrossel 3D.'
            ]),
            self::f([
                'key' => 'gallery_tilt_enabled', 
                'label' => 'Galeria — efeito de inclinação', 
                'type' => 'toggle', 
                'group' => 'galeria', 
                'default' => $lc['gallery_tilt_enabled'] ?? true,
                'hint' => 'Ativa uma inclinação 3D interativa na imagem sob o cursor, respondendo à posição do rato.'
            ]),
            self::f([
                'key' => 'gallery_autoplay_speed', 
                'label' => 'Galeria — velocidade de rotação (ms)', 
                'type' => 'range', 
                'group' => 'galeria', 
                'default' => $lc['gallery_autoplay_speed'] ?? 5000, 
                'min' => 1000, 
                'max' => 10000, 
                'step' => 500, 
                'hint' => 'Tempo de espera antes de rodar para o próximo slide no modo Carrossel 3D. Recomendado entre 4000ms e 6000ms.'
            ]),
            self::f([
                'key' => 'gallery_hover_zoom', 
                'label' => 'Galeria — efeito de zoom nas imagens', 
                'type' => 'toggle', 
                'group' => 'galeria', 
                'default' => $lc['gallery_hover_zoom'] ?? true, 
                'hint' => 'Aplica um zoom suave e realce de brilho ao passar o rato sobre as fotos (funciona em todos os layouts).'
            ]),
            self::f([
                'key' => 'gallery_columns_desktop', 
                'label' => 'Galeria — colunas em Desktop', 
                'type' => 'range', 
                'group' => 'galeria', 
                'default' => $lc['gallery_columns_desktop'] ?? 3, 
                'min' => 2, 
                'max' => 6, 
                'step' => 1, 
                'hint' => 'Número de colunas horizontais exibidas em ecrãs de computador nos modos Grelha e Mosaico (Mosaico).'
            ]),
            self::f([
                'key' => 'gallery_gap', 
                'label' => 'Galeria — espaço entre imagens (px)', 
                'type' => 'range', 
                'group' => 'galeria', 
                'default' => $lc['gallery_gap'] ?? 16, 
                'min' => 4, 
                'max' => 48, 
                'step' => 4, 
                'hint' => 'Distância em pixels entre as fotografias. Recomendado manter entre 12px e 20px para layouts limpos.'
            ]),
            self::f([
                'key' => 'gallery_hologram_effect', 
                'label' => 'Galeria — efeito de varredura HUD', 
                'type' => 'toggle', 
                'group' => 'galeria', 
                'default' => $lc['gallery_hologram_effect'] ?? true, 
                'hint' => 'Injeta linhas de varredura de monitor e uma moldura de sensor nas fotos, reforçando a estética de cockpit da galeria.'
            ]),
            self::f([
                'key' => 'gallery_swipe_enabled', 
                'label' => 'Galeria — rotação por arrasto', 
                'type' => 'toggle', 
                'group' => 'galeria', 
                'default' => $lc['gallery_swipe_enabled'] ?? true, 
                'hint' => 'Permite ao utilizador rodar o Carrossel 3D arrastando livremente as imagens com o rato (computador) ou deslizando o dedo (telemóvel).'
            ]),
            self::f([
                'key' => 'gallery_3d_radius_multiplier', 
                'label' => 'Galeria — raio da órbita 3D', 
                'type' => 'range', 
                'group' => 'galeria', 
                'default' => $lc['gallery_3d_radius_multiplier'] ?? 100, 
                'min' => 60, 
                'max' => 150, 
                'step' => 10, 
                'hint' => 'Ajuste da órbita: menor = imagens mais juntas; maior = mais afastadas. Aumente o raio se tiver mais de 10 imagens no Carrossel 3D para evitar sobreposição.'
            ]),
            self::f([
                'key' => 'gallery_lightbox_blur', 
                'label' => 'Galeria — desfoque do Lightbox (px)', 
                'type' => 'range', 
                'group' => 'galeria', 
                'default' => $lc['gallery_lightbox_blur'] ?? 12, 
                'min' => 0, 
                'max' => 24, 
                'step' => 2, 
                'hint' => 'Intensidade do desfoque de fundo (backdrop filter blur) ao ampliar imagens. O padrão de 12px oferece uma excelente focagem na imagem.'
            ]),
            self::f([
                'key' => 'gallery_sound_fx', 
                'label' => 'Galeria — efeitos sonoros dedicados', 
                'type' => 'toggle', 
                'group' => 'galeria', 
                'default' => $lc['gallery_sound_fx'] ?? true, 
                'hint' => 'Toca efeitos sonoros eletrónicos e de sonar muito suaves e discretos ao deslizar e ao abrir o Lightbox.'
            ]),

            // ── Rodapé ───────────────────────────────────────────────────
            self::f([
                'key' => 'footer_type', 
                'label' => 'Estilo do rodapé', 
                'type' => 'select', 
                'group' => 'rodape', 
                'default' => $lc['footer_type'] ?? 'simple',
                'options' => ['simple' => 'Simples', 'columns' => 'Colunas', 'minimal' => 'Minimal', 'dark' => 'Escuro', 'accent' => 'Destaque'],
                'hint' => 'Estilo visual do rodapé. Os estilos "Escuro" ou "Destaque" encaixam melhor na paleta noturna aeroespacial deste tema.'
            ]),
            self::f([
                'key' => 'footer_copyright', 
                'label' => 'Texto de copyright', 
                'type' => 'text', 
                'group' => 'rodape', 
                'default' => $lc['footer_copyright'] ?? '', 
                'hint' => 'Direitos de autor do rodapé. Deixe em branco para o sistema gerar automaticamente com o nome do site e o ano atual.'
            ]),
            self::f([
                'key' => 'contact_show_map', 
                'label' => 'Mostrar mapa de contacto', 
                'type' => 'toggle', 
                'group' => 'rodape', 
                'default' => $lc['contact_show_map'] ?? true,
                'hint' => 'Ativa a exibição de um mapa interativo na secção de contactos do rodapé. Recomenda-se manter ativado para negócios físicos.'
            ]),
            self::f([
                'key' => 'contact_map_iframe', 
                'label' => 'Embed do mapa (iframe src)', 
                'type' => 'textarea', 
                'group' => 'rodape', 
                'default' => $lc['contact_map_iframe'] ?? '', 
                'hint' => 'Cole apenas o endereço "src" do iframe gerado na opção Partilhar/Incorporar do Google Maps (ex: https://www.google.com/maps/embed?...).'
            ]),
            self::f([
                'key' => 'contact_show_newsletter', 
                'label' => 'Mostrar newsletter', 
                'type' => 'toggle', 
                'group' => 'rodape', 
                'default' => $lc['contact_show_newsletter'] ?? true,
                'hint' => 'Exibe um formulário de subscrição de newsletter no rodapé. Altamente recomendado para conversão de Leads.'
            ]),
            self::f([
                'key' => 'footer_description', 
                'label' => 'Rodapé — descrição', 
                'type' => 'textarea', 
                'group' => 'rodape', 
                'default' => $lc['footer_description'] ?? 'Logística autónoma de carga, transporte vertical e monitorização orbital.',
                'hint' => 'Texto descritivo sobre a atividade ou missão da marca exibido abaixo do logótipo no rodapé.'
            ]),
            self::f([
                'key' => 'footer_location', 
                'label' => 'Rodapé — localização', 
                'type' => 'text', 
                'group' => 'rodape', 
                'default' => $lc['footer_location'] ?? 'Luanda, Angola',
                'hint' => 'Nome da localização (cidade/país) que aparece associado às coordenadas geográficas decorativas no rodapé.'
            ]),
            self::f(['key' => 'footer_lat', 'label' => 'Rodapé — latitude', 'type' => 'text', 'group' => 'rodape', 'default' => $lc['footer_lat'] ?? '-8.8124', 'hint' => 'Coordenada de latitude geográfica decorativa no painel de telemetria do rodapé.']),
            self::f(['key' => 'footer_lon', 'label' => 'Rodapé — longitude', 'type' => 'text', 'group' => 'rodape', 'default' => $lc['footer_lon'] ?? '13.2306', 'hint' => 'Coordenada de longitude geográfica decorativa no painel de telemetria do rodapé.']),
            self::f(['key' => 'footer_alt', 'label' => 'Rodapé — altitude', 'type' => 'text', 'group' => 'rodape', 'default' => $lc['footer_alt'] ?? '0m', 'hint' => 'Coordenada de altitude de referência decorativa no painel de telemetria do rodapé.']),
            self::f(['key' => 'footer_status_text', 'label' => 'Rodapé — status do cockpit', 'type' => 'text', 'group' => 'rodape', 'default' => $lc['footer_status_text'] ?? 'Sistema Operacional', 'hint' => 'Indicador do estado do sistema exibido na barra inferior (ex: "Sistema Operacional", "Ligação Segura").']),

            // Contactos adicionais do Rodapé / Site
            self::f(['key' => 'contact_email', 'label' => 'E-mail comercial', 'type' => 'text', 'group' => 'rodape', 'default' => $lc['contact_email'] ?? 'comercial@aerospace.ao', 'hint' => 'E-mail de contacto público visível no rodapé.']),
            self::f(['key' => 'contact_phone', 'label' => 'Telefone comercial', 'type' => 'text', 'group' => 'rodape', 'default' => $lc['contact_phone'] ?? '+244 923 456 780', 'hint' => 'Telefone de contacto público visível no rodapé.']),
            self::f(['key' => 'contact_hours', 'label' => 'Horário de atendimento', 'type' => 'text', 'group' => 'rodape', 'default' => $lc['contact_hours'] ?? 'Seg–Sex · 08h00–18h00 (WAT)', 'hint' => 'Horário de suporte/funcionamento exibido nos detalhes de contacto.']),
            self::f(['key' => 'contact_address_hq', 'label' => 'Sede — Linha 1', 'type' => 'text', 'group' => 'rodape', 'default' => $lc['contact_address_hq'] ?? 'Rua Rainha Ginga, Edifício AeroSpace Tower', 'hint' => 'Endereço principal da sede/escritório.']),
            self::f(['key' => 'contact_address_sub', 'label' => 'Sede — Linha 2', 'type' => 'text', 'group' => 'rodape', 'default' => $lc['contact_address_sub'] ?? 'Miramar, Luanda, Angola', 'hint' => 'Linha complementar do endereço da sede.']),
            self::f(['key' => 'contact_address_hangar', 'label' => 'Hangar — Linha 1', 'type' => 'text', 'group' => 'rodape', 'default' => $lc['contact_address_hangar'] ?? 'Aeroporto Internacional 4 de Fevereiro', 'hint' => 'Endereço secundário (ex: instalações técnicas, armazém ou Hangar).']),
            self::f(['key' => 'contact_address_hangar_sub', 'label' => 'Hangar — Linha 2', 'type' => 'text', 'group' => 'rodape', 'default' => $lc['contact_address_hangar_sub'] ?? 'Zona Técnica Norte — Hangar AX, Luanda', 'hint' => 'Linha complementar do endereço secundário.']),
            
            self::f(['key' => 'social_linkedin', 'label' => 'Rede Social — LinkedIn (URL)', 'type' => 'text', 'group' => 'rodape', 'default' => $lc['social_linkedin'] ?? 'https://linkedin.com/company/aerospace', 'hint' => 'Link completo para o perfil da empresa no LinkedIn. Deixe em branco para ocultar o ícone.']),
            self::f(['key' => 'social_x', 'label' => 'Rede Social — X / Twitter (URL)', 'type' => 'text', 'group' => 'rodape', 'default' => $lc['social_x'] ?? 'https://x.com/aerospace', 'hint' => 'Link completo para o perfil da empresa no X (Twitter). Deixe em branco para ocultar o ícone.']),
            self::f(['key' => 'social_facebook', 'label' => 'Rede Social — Facebook (URL)', 'type' => 'text', 'group' => 'rodape', 'default' => $lc['social_facebook'] ?? '', 'hint' => 'Link completo para o perfil da empresa no Facebook. Deixe em branco para ocultar o ícone.']),
            self::f(['key' => 'social_instagram', 'label' => 'Rede Social — Instagram (URL)', 'type' => 'text', 'group' => 'rodape', 'default' => $lc['social_instagram'] ?? '', 'hint' => 'Link completo para o perfil da empresa no Instagram. Deixe em branco para ocultar o ícone.']),
            self::f(['key' => 'social_youtube', 'label' => 'Rede Social — YouTube (URL)', 'type' => 'text', 'group' => 'rodape', 'default' => $lc['social_youtube'] ?? '', 'hint' => 'Link completo para o canal da empresa no YouTube. Deixe em branco para ocultar o ícone.']),

            // ── Formulários — Configurações ──────────────────────────────
            self::f([
                'key' => 'contact_mailto', 
                'label' => 'Formulário — e-mail de destino', 
                'type' => 'text', 
                'group' => 'formularios', 
                'default' => $lc['contact_mailto'] ?? 'ops@aerospace.ao', 
                'hint' => 'E-mail para onde os formulários de contacto do site são enviados por omissão.'
            ]),
            self::f([
                'key' => 'contact_form_success_message', 
                'label' => 'Formulário CTA — mensagem de sucesso', 
                'type' => 'text', 
                'group' => 'formularios', 
                'default' => $lc['contact_form_success_message'] ?? '✅ Missão submetida com sucesso! A nossa equipa de operações entrará em contacto em breve.', 
                'hint' => 'Mensagem de confirmação verde exibida após a submissão bem-sucedida do formulário.'
            ]),
            self::f([
                'key' => 'contact_form_btn_text', 
                'label' => 'Formulário CTA — texto do botão', 
                'type' => 'text', 
                'group' => 'formularios', 
                'default' => $lc['contact_form_btn_text'] ?? 'Submeter Plano de Missão',
                'hint' => 'Texto do botão de submissão do formulário de contacto (ex: "Enviar Pedido de Cotação").'
            ]),
            self::f([
                'key' => 'contact_form_placeholder_name', 
                'label' => 'Formulário — placeholder Nome', 
                'type' => 'text', 
                'group' => 'formularios', 
                'default' => $lc['contact_form_placeholder_name'] ?? 'Nome da Empresa / Entidade',
                'hint' => 'Texto explicativo de exemplo dentro do campo "Nome" do formulário de contacto.'
            ]),
            self::f([
                'key' => 'contact_form_placeholder_email', 
                'label' => 'Formulário — placeholder E-mail', 
                'type' => 'text', 
                'group' => 'formularios', 
                'default' => $lc['contact_form_placeholder_email'] ?? 'E-mail de Contacto',
                'hint' => 'Texto explicativo de exemplo dentro do campo "E-mail" do formulário.'
            ]),
            self::f([
                'key' => 'contact_form_placeholder_message', 
                'label' => 'Formulário — placeholder Mensagem', 
                'type' => 'textarea', 
                'group' => 'formularios', 
                'default' => $lc['contact_form_placeholder_message'] ?? 'Descreva a sua missão (ex: rota de transporte de 100km)',
                'hint' => 'Texto explicativo de exemplo dentro da área de texto "Mensagem" do formulário.'
            ]),

            // Newsletter
            self::f(['key' => 'newsletter_title', 'label' => 'Newsletter — título', 'type' => 'text', 'group' => 'formularios', 'default' => $lc['newsletter_title'] ?? 'Subscrever Boletim Operacional', 'hint' => 'Título de cabeçalho da secção de subscrição de newsletter.']),
            self::f(['key' => 'newsletter_description', 'label' => 'Newsletter — descrição', 'type' => 'textarea', 'group' => 'formularios', 'default' => $lc['newsletter_description'] ?? 'Receba novidades sobre espaço aéreo, legislação de drones e atualizações de investigação tecnológica.', 'hint' => 'Texto curto explicativo dos benefícios de subscrever a newsletter.']),
            self::f(['key' => 'newsletter_btn_text', 'label' => 'Newsletter — texto do botão', 'type' => 'text', 'group' => 'formularios', 'default' => $lc['newsletter_btn_text'] ?? 'Subscrever', 'hint' => 'Texto do botão de submissão da newsletter (ex: "Aderir").']),
            self::f(['key' => 'newsletter_success_message', 'label' => 'Newsletter — mensagem de sucesso', 'type' => 'text', 'group' => 'formularios', 'default' => $lc['newsletter_success_message'] ?? '📡 Subscrição efectuada! Receberá o próximo boletim operacional em breve.', 'hint' => 'Mensagem de confirmação verde exibida após a subscrição com sucesso na newsletter.']),

            // Serviços (dropdown do formulário de contacto detalhado)
            self::f([
                'key' => 'contact_services_list', 
                'label' => 'Formulário Contacto — lista de serviços', 
                'type' => 'textarea', 
                'group' => 'formularios', 
                'default' => "Transporte Autónomo de Carga\nCartografia & Fotogrametria\nVigilância & Patrulhamento\nInspecção Industrial\nLogística de Emergência\nAgricultura de Precisão", 
                'hint' => 'Escreva um serviço por linha. Estes serviços vão constituir as opções selecionáveis do dropdown no formulário de contacto.'
            ]),

            // ── Funcionalidades ──────────────────────────────────────────
            self::f([
                'key' => 'telemetry_enabled', 
                'label' => 'Painel de telemetria live', 
                'type' => 'toggle', 
                'group' => 'funcionalidades', 
                'default' => $lc['telemetry_enabled'] ?? false,
                'hint' => 'Exibe um painel flutuante de telemetria de voo ativo no canto do ecrã com rotação de dados dinâmica via JS.'
            ]),
            self::f([
                'key' => 'chat_popup_enabled', 
                'label' => 'Canal de apoio (popup)', 
                'type' => 'toggle', 
                'group' => 'funcionalidades', 
                'default' => $lc['chat_popup_enabled'] ?? true,
                'hint' => 'Exibe o botão de chat de apoio flutuante no canto inferior direito para ajudar a captar o contacto dos utilizadores.'
            ]),
            self::f([
                'key' => 'chat_popup_mode', 
                'label' => 'Canal de apoio — modo', 
                'type' => 'select', 
                'group' => 'funcionalidades', 
                'default' => $lc['chat_popup_mode'] ?? 'form',
                'options' => ['ai' => 'IA (chat)', 'form' => 'Formulário'],
                'hint' => 'Define se o botão de apoio abre um diálogo interativo de inteligência artificial ou um formulário clássico de e-mail.'
            ]),
            self::f([
                'key' => 'chat_voice_commands', 
                'label' => 'Comandos por voz', 
                'type' => 'toggle', 
                'group' => 'funcionalidades', 
                'default' => $lc['chat_voice_commands'] ?? true,
                'hint' => 'Permite ao utilizador controlar o site por voz usando comandos pré-configurados (ex: dizer "sobre", "serviços", "chat").'
            ]),
            self::f([
                'key' => 'preloader_terminal', 
                'label' => 'Preloader de consola de boot', 
                'type' => 'toggle', 
                'group' => 'funcionalidades', 
                'default' => $lc['preloader_terminal'] ?? true,
                'hint' => 'Exibe uma animação simulada de inicialização de comandos de terminal (boot) ao carregar a página principal pela primeira vez.'
            ]),
            self::f([
                'key' => 'hover_sound_effects', 
                'label' => 'Efeitos sonoros no hover', 
                'type' => 'toggle', 
                'group' => 'funcionalidades', 
                'default' => $lc['hover_sound_effects'] ?? true,
                'hint' => 'Toca um bipe eletrónico muito discreto e futurista quando o utilizador passa o rato sobre os links e botões.'
            ]),
            self::f([
                'key' => 'parallax', 
                'label' => 'Parallax', 
                'type' => 'toggle', 
                'group' => 'funcionalidades', 
                'source' => 'capability', 
                'default' => $caps['parallax'] ?? true,
                'hint' => 'Ativa o efeito de movimento tridimensional de fundos de secção ao fazer scroll na página.'
            ]),
            self::f([
                'key' => 'animations', 
                'label' => 'Animações de scroll', 
                'type' => 'toggle', 
                'group' => 'funcionalidades', 
                'source' => 'capability', 
                'default' => $caps['animations'] ?? true,
                'hint' => 'Ativa o aparecimento e animação fluida (fade-in, slide-up) de secções e cartões ao navegar pela página.'
            ]),
            self::f([
                'key' => 'lightbox', 
                'label' => 'Lightbox de imagens', 
                'type' => 'toggle', 
                'group' => 'funcionalidades', 
                'source' => 'capability', 
                'default' => $caps['lightbox'] ?? true,
                'hint' => 'Permite clicar nas imagens das galerias do site para ampliá-las numa janela de visualização escurecida interativa.'
            ]),
            self::f([
                'key' => 'scroll_progress', 
                'label' => 'Barra de progresso de scroll', 
                'type' => 'toggle', 
                'group' => 'funcionalidades', 
                'source' => 'capability', 
                'default' => $caps['scroll_progress'] ?? true,
                'hint' => 'Exibe uma linha fina e brilhante no topo da janela que indica a percentagem lida da página atual.'
            ]),
            self::f([
                'key' => 'cookie_banner', 
                'label' => 'Banner de cookies', 
                'type' => 'toggle', 
                'group' => 'funcionalidades', 
                'source' => 'capability', 
                'default' => $caps['cookie_banner'] ?? true,
                'hint' => 'Exibe o banner flutuante de privacidade e consentimento de cookies RGPD na primeira visita do utilizador.'
            ]),
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
