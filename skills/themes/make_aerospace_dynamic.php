<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\StudioTheme;

$theme = StudioTheme::where('label', 'AeroSpace')->first();
if (!$theme) {
    fwrite(STDERR, "❌ Tema 'AeroSpace' não encontrado na BD.\n");
    exit(1);
}

$sections = $theme->sections;

// ─── CTA ─────────────────────────────────────────────────────────────────────
$sections['cta'] = <<<'HTML'
@php
  $c = $content ?? [];
  $heading = $c['heading'] ?? 'Pronto para Lançar a Missão?';
  $text = $c['text'] ?? $c['subheading'] ?? 'Solicite o estudo de viabilidade da sua operação de tráfego aéreo. Entraremos em contacto para detalhar o plano de voo.';
  $btnText = $c['button_text'] ?? $theme->layout_config['contact_form_btn_text'] ?? 'Submeter Plano de Missão';
  
  $phName    = $theme->layout_config['contact_form_placeholder_name']    ?? 'Nome da Empresa / Entidade';
  $phEmail   = $theme->layout_config['contact_form_placeholder_email']   ?? 'E-mail de Contacto';
  $phMessage = $theme->layout_config['contact_form_placeholder_message'] ?? 'Descreva a sua missão (ex: rota de transporte de 100km)';
  $formMailto = $theme->layout_config['contact_mailto'] ?? 'ops@aerospace.ao';
  $formSuccess = $theme->layout_config['contact_form_success_message'] ?? '✅ Missão submetida com sucesso! A nossa equipa de operações entrará em contacto em breve.';
  
  $secBg = $c['bg_color'] ?? $settings['bg_color'] ?? $c['custom_bg_color'] ?? null;
  $secText = $c['text_color'] ?? $settings['text_color'] ?? null;
@endphp
<section id="contacto" class="py-20 bg-gradient-to-br from-[#0F172A] to-[#070C18] border-t border-white/5 relative" style="@if($secBg) background: {{ $secBg }} !important; @endif @if($secText) color: {{ $secText }} !important; @endif">
  <div class="max-w-4xl mx-auto px-6 text-center">
    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 text-xs font-semibold uppercase tracking-wider mb-6">
      ⚡ Pronto para Operar
    </div>
    <h2 class="text-3xl md:text-4xl font-bold text-white mb-6 font-heading">{{ $heading }}</h2>
    <p class="text-slate-400 max-w-xl mx-auto mb-10">{{ $text }}</p>
    <form id="aerospace-contact-form" class="max-w-md mx-auto space-y-4" novalidate
          onsubmit="handleAerospaceContactSubmit(event)"
          data-mailto="{{ $formMailto }}"
          data-success="{{ $formSuccess }}">
      @csrf
      <div class="text-left">
        <label for="contact-name" class="sr-only">Nome da Empresa ou Entidade</label>
        <input type="text" id="contact-name" name="nome" placeholder="{{ $phName }}" class="contact-input" required autocomplete="organization">
      </div>
      <div class="text-left">
        <label for="contact-email" class="sr-only">E-mail de Contacto</label>
        <input type="email" id="contact-email" name="email" placeholder="{{ $phEmail }}" class="contact-input" required autocomplete="email">
      </div>
      <div class="text-left">
        <label for="contact-message" class="sr-only">Descrição da Missão</label>
        <textarea id="contact-message" name="mensagem" placeholder="{{ $phMessage }}" rows="3" class="contact-input" required></textarea>
      </div>
      <div id="contact-form-feedback" class="text-xs py-2 hidden"></div>
      <button type="submit" id="contact-submit-btn" class="contact-btn w-full">{{ $btnText }}</button>
    </form>

    <!-- Google Map Container -->
    @if($theme->layout_config['contact_show_map'] ?? true)
      <div class="contact-map-container mt-12">
        <iframe src="{{ $theme->layout_config['contact_map_iframe'] ?? '' }}" title="Localização AeroSpace — Luanda, Angola" width="100%" height="350" style="border:0; border-radius:1rem; opacity:0.85;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
      </div>
    @endif

    <!-- Newsletter Subscription -->
    @if($theme->layout_config['contact_show_newsletter'] ?? true)
      <div class="contact-newsletter-container mt-16 pt-12 border-t border-white/5">
        <h3 class="text-xl font-bold text-white mb-2 font-heading">{{ $theme->layout_config['newsletter_title'] ?? 'Subscrever Boletim Operacional' }}</h3>
        <p class="text-slate-400 text-xs mb-6 max-w-md mx-auto">{{ $theme->layout_config['newsletter_description'] ?? 'Receba novidades sobre espaço aéreo, legislação de drones e atualizações de investigação tecnológica.' }}</p>
        <form id="aerospace-newsletter-form" class="flex flex-col sm:flex-row gap-2 max-w-md mx-auto" novalidate
              onsubmit="handleNewsletterSubmit(event)"
              data-success="{{ $theme->layout_config['newsletter_success_message'] ?? '📡 Subscrição efectuada! Receberá o próximo boletim operacional em breve.' }}">
          @csrf
          <label for="newsletter-email" class="sr-only">Endereço de e-mail</label>
          <input type="email" id="newsletter-email" name="email" placeholder="Introduza o seu email..." class="contact-input sm:flex-1" required>
          <button type="submit" id="newsletter-submit-btn" class="contact-btn sm:w-auto px-6">{{ $theme->layout_config['newsletter_btn_text'] ?? 'Subscrever' }}</button>
        </form>
        <div id="newsletter-feedback" class="hidden text-xs mt-3 py-1"></div>
      </div>
    @endif
  </div>
</section>
HTML;

// ─── MAP ─────────────────────────────────────────────────────────────────────
$sections['map'] = <<<'HTML'
@php
  $c = $content ?? [];
  $siteName = $site_name ?? 'AeroSpace';
  $heading = $c['heading'] ?? ('Sede — ' . ($theme->layout_config['footer_location'] ?? 'Luanda, Angola'));
  $embedUrl = $c['embed_url'] ?? $theme->layout_config['contact_map_iframe'] ?? 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15760.84157778939!2d13.23005872895697!3d-8.813958742512686!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x1a51f3c83786adbf%3A0x6b772c676bb7db4b!2sLuanda!5e0!3m2!1spt-PT!2sao!4v1700000000000!5m2!1spt-PT!2sao';
  $address = $c['address'] ?? ($theme->layout_config['contact_address_hq'] ?? 'Miramar, Luanda, Angola');
  $lat = $theme->layout_config['footer_lat'] ?? '-8.8124';
  $lon = $theme->layout_config['footer_lon'] ?? '13.2306';
  
  $secBg = $c['bg_color'] ?? $settings['bg_color'] ?? $c['custom_bg_color'] ?? null;
  $secText = $c['text_color'] ?? $settings['text_color'] ?? null;
@endphp
<section class="py-16 bg-[#030712] relative" style="@if($secBg) background: {{ $secBg }} !important; @endif @if($secText) color: {{ $secText }} !important; @endif">
  <div class="max-w-5xl mx-auto px-6">
    <div class="text-center mb-10">
      <span class="inline-flex items-center gap-2 text-[11px] font-mono uppercase tracking-[0.25em] text-[#06B6D4] mb-3">
        <span class="w-2 h-2 rounded-full bg-[#06B6D4] animate-pulse"></span> Localização
      </span>
      <h2 class="text-2xl font-bold text-white font-heading">{{ $heading }}</h2>
    </div>
    <div class="rounded-2xl overflow-hidden border border-white/8 relative">
      <iframe
        src="{{ $embedUrl }}"
        width="100%" height="380"
        style="border:0; filter: invert(90%) hue-rotate(180deg) brightness(0.85) contrast(1.1); opacity:0.9;"
        allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
        title="Localização {{ $siteName }} — {{ $theme->layout_config['footer_location'] ?? 'Luanda, Angola' }}">
      </iframe>
      <div class="absolute bottom-4 left-4 bg-[#030712]/90 backdrop-blur border border-[#06B6D4]/20 rounded-xl px-4 py-3">
        <div class="text-white font-semibold text-sm font-heading">{{ $siteName }} HQ</div>
        <div class="text-slate-400 text-xs mt-0.5">{{ $address }}</div>
        <div class="text-[#06B6D4] text-[10px] font-mono mt-1">LAT {{ $lat }} · LON {{ $lon }}</div>
      </div>
    </div>
  </div>
</section>
HTML;

// ─── HERO ────────────────────────────────────────────────────────────────────
$sections['hero'] = <<<'HTML'
@php
    $c = $content ?? [];
    $heading = $c['heading'] ?? 'Soberania nos Céus,<br>Precisão na Terra';
    $subheading = $c['subheading'] ?? 'A primeira plataforma integrada de tecnologia aeroespacial focada em logística aérea autónoma de carga e monitorização orbital de alta resolução.';
    $ctaText = $c['cta_text'] ?? 'Os Nossos Serviços';
    $ctaUrl = $c['cta_url'] ?? '/servicos';
    $cta2Text = $c['cta2_text'] ?? 'Entrar em Contacto';
    $cta2Url = $c['cta2_url'] ?? '/contactos';

    $isHome = request()->is('/') || request()->routeIs('home') || str_contains(request()->path(), 'preview-home') || (str_contains(request()->path(), 'preview/theme') && !str_contains(request()->path(), 'sobre') && !str_contains(request()->path(), 'servicos') && !str_contains(request()->path(), 'galeria') && !str_contains(request()->path(), 'contactos'));

    $navHex = ltrim((string)($theme->layout_config['navbar_color'] ?? '#1E293B'), '#');
    if (strlen($navHex) === 3) { $navHex = $navHex[0].$navHex[0].$navHex[1].$navHex[1].$navHex[2].$navHex[2]; }
    $navR = hexdec(substr($navHex, 0, 2)); $navG = hexdec(substr($navHex, 2, 2)); $navB = hexdec(substr($navHex, 4, 2));
    $navOpacity = max(0, min(100, (int)($theme->layout_config['navbar_opacity'] ?? 72))) / 100;
    $navbarBg = "rgba({$navR}, {$navG}, {$navB}, {$navOpacity})";
    $heroBg = $theme->layout_config['hero_bg_color'] ?? '#0F1A2E';

    $hudLat = $theme->layout_config['footer_lat'] ?? '08°50\'S';
    $hudLon = $theme->layout_config['footer_lon'] ?? '13°14\'E';
    if (is_numeric($hudLat)) {
        $hudLatVal = floatval($hudLat);
        $hudLat = sprintf("%02d°%02d'%s", abs((int)$hudLatVal), abs((int)(round(($hudLatVal - (int)$hudLatVal) * 60))), $hudLatVal >= 0 ? 'N' : 'S');
    }
    if (is_numeric($hudLon)) {
        $hudLonVal = floatval($hudLon);
        $hudLon = sprintf("%02d°%02d'%s", abs((int)$hudLonVal), abs((int)(round(($hudLonVal - (int)$hudLonVal) * 60))), $hudLonVal >= 0 ? 'E' : 'W');
    }
@endphp
<section class="aerospace-hero group {{ !$isHome ? 'hero-internal hero-revealed' : '' }}" style="--menu-space-top: {{ (int)($theme->layout_config['menu_space_top'] ?? 24) }}px; --menu-space-bottom: {{ (int)($theme->layout_config['menu_space_bottom'] ?? 24) }}px; --scrim-opacity: {{ (int)($theme->layout_config['screensaver_scrim'] ?? 10) / 100 }}; --scrim-blur: {{ (int)($theme->layout_config['screensaver_blur'] ?? 0) }}px; --video-opacity: {{ (int)($theme->layout_config['screensaver_video_opacity'] ?? 100) / 100 }}; --info-card-top: {{ (int)($theme->layout_config['info_card_top'] ?? 36) }}%; --circular-menu-y: {{ (int)($theme->layout_config['menu_space_top'] ?? 24) - 84 }}px; --navbar-bg: {{ $navbarBg }}; --hero-bg: {{ $heroBg }}; --subpage-padding-top: {{ (int)($theme->layout_config['hero_internal_padding_top'] ?? 50) }}px;">
  <!-- Preloader de Consola de Boot -->
  @if($theme->layout_config['preloader_terminal'] ?? true)
    <div id="aerospace-preloader" class="preloader-terminal">
      <div class="terminal-container">
        <div class="terminal-header">
          <span class="terminal-dot red"></span>
          <span class="terminal-dot yellow"></span>
          <span class="terminal-dot green"></span>
          <span class="terminal-title">{{ strtoupper($siteName ?? 'AEROSPACE') }} COCKPIT INIT v1.0.0</span>
        </div>
        <div class="terminal-body" id="preloader-terminal-log"></div>
      </div>
    </div>
  @endif

  <!-- Background Element Layer based on configuration -->
  <div class="screensaver-container">
    @if(($theme->layout_config['hud_bg_type'] ?? 'video') === 'video')
      <video autoplay loop muted playsinline class="screensaver-video" poster="{{ $theme->layout_config['hud_bg_single_photo'] ?? '/images/aerospace-hero.svg' }}">
        <source src="{{ $theme->layout_config['hud_bg_video'] ?? '/videos/aerospace-fundo.mp4' }}" type="video/mp4">
      </video>
    @elseif(($theme->layout_config['hud_bg_type'] ?? 'video') === 'gallery')
      <div class="screensaver-gallery">
        @foreach(($theme->layout_config['hud_bg_gallery'] ?? []) as $index => $slide)
          <div class="slide-item @if($index === 0) active-slide @endif" style="background-image: url('{{ $slide }}')"></div>
        @endforeach
      </div>
    @elseif(($theme->layout_config['hud_bg_type'] ?? 'video') === 'photo')
      <div class="screensaver-photo" style="background-image: url('{{ $theme->layout_config['hud_bg_single_photo'] ?? '/images/aerospace-hero.svg' }}')"></div>
    @endif

    <!-- HUD Overlay Dashboard (Cockpit telemetry) -->
    @if($theme->layout_config['hud_overlay_enabled'] ?? true)
      <div class="hud-overlay-dashboard">
        <div class="hud-crosshair"></div>
        <div class="hud-horizon-line"></div>
        <div class="hud-telemetry-left">
          ALT: {{ $theme->layout_config['hud_telemetry_alt'] ?? '124m' }}<br>
          SPD: {{ $theme->layout_config['hud_telemetry_spd'] ?? '42km/h' }}<br>
          BAT: {{ $theme->layout_config['hud_telemetry_bat'] ?? '88%' }}<br>
          ALT-HOLD: {{ $theme->layout_config['hud_telemetry_althold'] ?? 'ON' }}
        </div>
        <div class="hud-telemetry-right">
          LAT: {{ $hudLat }}<br>
          LNG: {{ $hudLon }}<br>
          GPS: {{ $theme->layout_config['hud_telemetry_gps'] ?? 'LOCK' }}<br>
          NAV-LOCK: {{ $theme->layout_config['hud_telemetry_navlock'] ?? 'OK' }}
        </div>
      </div>
    @endif

    <div class="info-panel">
      <div class="info-card animate-pulse">
        <div class="radar-ping"></div>
        <h3 class="font-bold mb-2 tracking-widest" style="font-size: {{ (int)($theme->layout_config['info_card_title_size'] ?? 20) }}px; color: {{ $theme->layout_config['info_card_title_color'] ?? '#FFFFFF' }};">{{ $theme->layout_config['info_card_title_text'] ?? 'AEROSPACE' }}</h3>
        <p class="uppercase tracking-wider mb-3" style="font-size: {{ (int)($theme->layout_config['info_card_subtitle_size'] ?? 12) }}px; color: {{ $theme->layout_config['info_card_subtitle_color'] ?? '#06B6D4' }};">{{ $theme->layout_config['info_card_subtitle_text'] ?? 'Operações & Logística Aérea' }}</p>
        <p style="font-size: {{ (int)($theme->layout_config['info_card_hint_size'] ?? 10) }}px; color: {{ $theme->layout_config['info_card_hint_color'] ?? '#94A3B8' }};">{{ $theme->layout_config['info_card_hint_text'] ?? 'Passe o cursor ou toque no ecrã para aceder' }}</p>
      </div>
    </div>
  </div>

  <!-- Main Content Overlay -->
  <div class="hero-content">
    <!-- Grelha 3D Interativa com Perspetiva (Laser Mesh) -->
    @if($theme->layout_config['interactive_mesh_3d'] ?? true)
      <div class="grid-background-3d-wrap">
        <div class="grid-background-3d" id="mesh-grid-3d"></div>
      </div>
    @else
      <div class="grid-background"></div>
    @endif
    
    <!-- Normal Navigation Header -->
    @php
      $menuLayoutCfg = $theme->layout_config['menu_layout'] ?? 'circular';
      $effectiveMenuLayout = ($menuLayoutCfg === 'circular' && !$isHome) ? 'normal' : $menuLayoutCfg;
      $effectiveMenuPosition = ($menuLayoutCfg === 'circular' && !$isHome) ? ($theme->layout_config['normal_menu_position'] ?? 'horizontal-right') : ($layout['normal_menu_position'] ?? 'horizontal-right');
      $showNormalMenu = ($effectiveMenuLayout === 'normal');
      $showCircularMenu = ($effectiveMenuLayout === 'circular');
    @endphp
    <header class="normal-navbar pos-{{ $effectiveMenuPosition }} @if($theme->layout_config['header_sticky'] ?? true) is-sticky @endif @if(!$showNormalMenu) hidden-navbar @endif @if(!$isHome && $menuLayoutCfg === 'circular') portal-active @endif">
      <button class="hamburger-menu-btn" onclick="toggleMobileMenu(event)" aria-label="Abrir menu de navegação" aria-expanded="false">
        <span></span>
        <span></span>
        <span></span>
      </button>
      <div class="logo-area">
        @php
          $logoType = $theme->layout_config['logo_type'] ?? 'both';
          $logoHeight = ($theme->layout_config['logo_height'] ?? 36) . 'px';
          $logoTextSize = ($theme->layout_config['logo_text_size'] ?? 20) . 'px';
          $logoTextWeight = $theme->layout_config['logo_text_weight'] ?? 'bold';
          $siteName = $site_name ?? 'AeroSpace';
          $homepageUrl = $site_homepage_url ?? '/';
        @endphp

        @if($logoType === 'image' || $logoType === 'both')
          <a href="{{ $homepageUrl }}" class="logo-img-link">
            <img src="{{ $theme->layout_config['logo_image_light'] ?? '/images/aerospace-logo.svg' }}" 
                 alt="{{ $siteName }}" 
                 style="height: {{ $logoHeight }};"
                 class="logo-light" />
            @if(!empty($theme->layout_config['logo_image_dark']))
              <img src="{{ $theme->layout_config['logo_image_dark'] }}" 
                   alt="{{ $siteName }}" 
                   style="height: {{ $logoHeight }};"
                   class="logo-dark hidden" />
            @endif
          </a>
        @endif

        @if($logoType === 'text' || $logoType === 'both')
          <a href="{{ $homepageUrl }}" class="logo-text-link" style="font-size: {{ $logoTextSize }}; font-weight: {{ $logoTextWeight }};">
            {{ $siteName }}
          </a>
        @endif
      </div>
      <nav class="horizontal-links">
        <ul>
          @foreach($nav_links ?? [] as $link)
            @php
              $children = $link['children'] ?? [];
              $isActive = request()->is(ltrim($link['url'], '/')) || (request()->is('/') && $link['url'] === '/');
            @endphp
            <li>
              <a href="{{ $link['url'] }}" class="{{ $isActive ? 'active' : '' }}" onmouseenter="playHoverChirp()" @if(($link['target'] ?? '_self') === '_blank') target="_blank" @endif>
                {{ $link['label'] }} @if(!empty($children)) ▾ @endif
              </a>
              @if(!empty($children))
                <ul class="normal-dropdown">
                  @foreach($children as $child)
                    <li>
                      <a href="{{ $child['url'] }}" onmouseenter="playHoverChirp()" @if(($child['target'] ?? '_self') === '_blank') target="_blank" @endif>
                        {{ $child['label'] }}
                      </a>
                    </li>
                  @endforeach
                </ul>
              @endif
            </li>
          @endforeach
        </ul>
      </nav>
      <div class="flex items-center gap-4">
        <button class="sound-toggle-btn text-xs bg-white/5 hover:bg-white/10 px-2 py-1 rounded border border-white/10 text-slate-400" onclick="toggleSoundMute(event)">🔊 Áudio</button>
      </div>
    </header>

    
    <!-- Circular Navigation (HOME centro + 4 Satélites) -->
    @php
      $hubTitle = $theme->layout_config['circular_menu_hub_text'] ?? 'HOME';
      $hubDesc = $theme->layout_config['circular_menu_hub_desc'] ?? 'Central Hub';
      $hubColor = $theme->layout_config['circular_menu_hub_color'] ?? '#06B6D4';

      $hubR = 6; $hubG = 182; $hubB = 212;
      $hubHex = ltrim($hubColor, '#');
      if (preg_match('/^[0-9a-fA-F]{6}$/', $hubHex)) {
          $hubR = hexdec(substr($hubHex, 0, 2));
          $hubG = hexdec(substr($hubHex, 2, 2));
          $hubB = hexdec(substr($hubHex, 4, 2));
      }

      $satBg = $theme->layout_config['circular_menu_bg'] ?? 'rgba(15, 23, 42, 0.85)';
      $satTextColor = $theme->layout_config['circular_menu_text_color'] ?? '#FFFFFF';
      $satDescColor = $theme->layout_config['circular_menu_desc_color'] ?? '#94A3B8';
      $satFontSize = ($theme->layout_config['circular_menu_font_size'] ?? 13) . 'px';

      $satWeightVal = $theme->layout_config['circular_menu_font_weight'] ?? 'bold';
      $weightMap = ['normal' => '400', 'medium' => '500', 'semibold' => '600', 'bold' => '700', 'extrabold' => '800'];
      $satFontWeight = $weightMap[$satWeightVal] ?? '700';
    @endphp
    <div class="circular-menu-wrapper @if(!$showCircularMenu) hidden-menu @endif" style="
      --hub-color: {{ $hubColor }};
      --hub-color-rgb: {{ $hubR }}, {{ $hubG }}, {{ $hubB }};
      --sat-bg: {{ $satBg }};
      --sat-text-color: {{ $satTextColor }};
      --sat-desc-color: {{ $satDescColor }};
      --sat-font-size: {{ $satFontSize }};
      --sat-font-weight: {{ $satFontWeight }};
    ">
      <div class="radar-circles"></div>
      <div class="radial-grid"></div>

      <!-- Central Hub (Home) -->
      <a href="/" class="menu-hub-node" onmouseenter="playHoverChirp()" onclick="playClickChirp()">
        <span class="node-title">{{ $hubTitle }}</span>
        <span class="node-desc">{{ $hubDesc }}</span>
        <div class="hub-glow"></div>
      </a>

      <!-- Satellite Orbiting Nodes -->
      <div class="satellite-orbits">
        @php
          $defaultSats = [
            ['label' => 'Serviços', 'url' => '/servicos', 'icon' => '🛸', 'desc' => 'Operações Aéreas'],
            ['label' => 'Galeria',  'url' => '/galeria',  'icon' => '🖼️', 'desc' => 'Missões & Media'],
            ['label' => 'Contactos','url' => '/contactos','icon' => '📡', 'desc' => 'Centro de Controlo'],
            ['label' => 'Sobre',    'url' => '/sobre',    'icon' => '🌐', 'desc' => 'Missão & Equipa']
          ];
          $menuLinks = $nav_links ?? [];
        @endphp
        @for($i = 0; $i < 4; $i++)
          @php
            $link = $menuLinks[$i] ?? null;
            $title = $link['label'] ?? $defaultSats[$i]['label'];
            $url = $link['url'] ?? $defaultSats[$i]['url'];
            $icon = $theme->layout_config['circular_menu_sat' . ($i + 1) . '_icon'] ?? $defaultSats[$i]['icon'];
            $desc = $theme->layout_config['circular_menu_sat' . ($i + 1) . '_desc'] ?? $defaultSats[$i]['desc'];
            $color = $theme->layout_config['circular_menu_sat' . ($i + 1) . '_color'] ?? '#06B6D4';

            $satR = 6; $satG = 182; $satB = 212;
            $hexColor = ltrim($color, '#');
            if (preg_match('/^[0-9a-fA-F]{6}$/', $hexColor)) {
                $satR = hexdec(substr($hexColor, 0, 2));
                $satG = hexdec(substr($hexColor, 2, 2));
                $satB = hexdec(substr($hexColor, 4, 2));
            }
          @endphp
          <div class="sat-node-container sat-{{ $i + 1 }} group/sat" style="
            --sat-color: {{ $color }};
            --sat-color-rgb: {{ $satR }}, {{ $satG }}, {{ $satB }};
          ">
            <a href="{{ $url }}" class="sat-node portal-link" onmouseenter="playHoverChirp()" data-portal-href="{{ $url }}">
              <div class="sat-icon">{{ $icon }}</div>
              <div class="sat-text">
                <span class="sat-title">{{ $title }}</span>
                <span class="sat-desc">{{ $desc }}</span>
              </div>
            </a>
          </div>
        @endfor
      </div>
    </div>


    <!-- Main Headline area -->
    <div class="hero-text-area">
      <h1 class="glitch-title">{!! $heading !!}</h1>
      <p class="hero-subtitle">{{ $subheading }}</p>
      <div class="flex justify-center gap-4 mt-8">
        @if(!empty($ctaText))
          <a href="{{ $ctaUrl }}" class="hero-btn-primary" onclick="playClickChirp()">{{ $ctaText }}</a>
        @endif
        @if(!empty($cta2Text))
          <a href="{{ $cta2Url }}" class="hero-btn-secondary" onclick="playClickChirp()">{{ $cta2Text }}</a>
        @endif
      </div>
    </div>
  </div>

  <!-- JSON-LD Structured Data (SEO: Organization + WebSite Schema) -->
  <script type="application/ld+json">
  {
    "@@context": "https://schema.org",
    "@@graph": [
      {
        "@@type": "Organization",
        "name": "{{ $site->name ?? 'AeroSpace' }}",
        "url": "{{ config('app.url') }}",
        "logo": "{{ config('app.url') }}/images/aerospace-logo.svg",
        "description": "{{ $seo->description ?? 'Logística autónoma de carga, transporte vertical e monitorização orbital.' }}",
        "address": {
          "@@type": "PostalAddress",
          "addressLocality": "Luanda",
          "addressCountry": "AO"
        },
        "sameAs": []
      },
      {
        "@@type": "WebSite",
        "name": "{{ $site->name ?? 'AeroSpace' }}",
        "url": "{{ config('app.url') }}",
        "potentialAction": {
          "@@type": "SearchAction",
          "target": "{{ config('app.url') }}/?s={search_term_string}",
          "query-input": "required name=search_term_string"
        }
      }
    ]
  }
  </script>

  <!-- Draggable Popup Chat Support -->
  @if($theme->layout_config['chat_popup_enabled'] ?? true)
    <div id="tech-chat-popup" class="chat-popup collapsed">
      <div class="chat-popup-header" id="chat-drag-handle">
        <div class="flex items-center gap-2">
          <span class="chat-status-indicator animate-pulse"></span>
          <span class="font-heading font-bold text-xs tracking-wider">SUPORTE AERO</span>
        </div>
        <div class="flex gap-2 items-center">
          <button class="chat-toggle-size" onclick="toggleChatCollapse(event)">▢</button>
          <button class="chat-close" onclick="toggleChatOpen(false, event)">×</button>
        </div>
      </div>
      <div class="chat-popup-body">
        @if(($theme->layout_config['chat_popup_mode'] ?? 'ai') === 'form')
        <form class="chat-input-area" style="flex-direction:column;align-items:stretch;gap:8px;" onsubmit="handleSupportFormSubmit(event)">
          <input type="text" name="nome" placeholder="Nome" required class="chat-input-field">
          <input type="email" name="email" placeholder="Email" required class="chat-input-field">
          <textarea name="mensagem" rows="3" placeholder="A sua mensagem..." required class="chat-input-field" style="resize:none;"></textarea>
          <button type="submit" class="chat-send-btn" style="width:100%;" onclick="playClickChirp()">Enviar ➔</button>
        </form>
        @else
        <div class="chat-messages" id="chat-messages-container">
          <div class="msg system">Canal encriptado de assistência ativo. Como posso auxiliar a sua missão?</div>
        </div>
        <form class="chat-input-area" onsubmit="handleChatSubmit(event)">
          <input type="text" id="chat-popup-text-input" placeholder="Introduzir directiva..." class="chat-input-field">
          @if($theme->layout_config['chat_voice_commands'] ?? true)
            <button type="button" id="voice-mic-btn" class="chat-send-btn bg-slate-700/50 hover:bg-slate-700" onclick="toggleVoiceListen()" title="Comando de Voz">🎙️</button>
          @endif
          <button type="submit" class="chat-send-btn" onclick="playClickChirp()">➔</button>
        </form>
        @endif
      </div>
    </div>
    <button id="chat-trigger-btn" onclick="toggleChatOpen(true, event)">💬 Canal de Apoio</button>
  @endif

  @if($theme->layout_config['telemetry_enabled'] ?? false)
  <!-- Draggable Live Telemetry Widget -->
  <div id="tech-telemetry-popup" class="chat-popup collapsed">
    <div class="chat-popup-header" id="telemetry-drag-handle">
      <div class="flex items-center gap-2">
        <span class="chat-status-indicator yellow animate-pulse"></span>
        <span class="font-heading font-bold text-xs tracking-wider">TELEMETRIA LIVE</span>
      </div>
      <div class="flex gap-2">
        <button class="chat-toggle-size" onclick="toggleTelemetryCollapse(event)">▢</button>
        <button class="chat-close" onclick="toggleTelemetryOpen(false, event)">×</button>
      </div>
    </div>
    <div class="chat-popup-body">
      <div class="space-y-4 text-left font-mono text-[10px] text-slate-400">
        <div class="flex justify-between border-b border-white/5 pb-1">
          <span>SAT-LINK STATUS:</span> <span class="text-[#06b6d4]">{{ $theme->layout_config['hud_telemetry_navlock'] ?? 'CONNECTED (100%)' }}</span>
        </div>
        <div class="flex justify-between border-b border-white/5 pb-1">
          <span>DRONES OPERACIONAIS:</span> <span class="text-emerald-400">{{ $theme->layout_config['hud_telemetry_drones_count'] ?? '07 ONLINE' }}</span>
        </div>
        <div class="flex justify-between border-b border-white/5 pb-1">
          <span>VELOCIDADE DO VENTO:</span> <span id="telemetry-wind">{{ $theme->layout_config['hud_telemetry_spd'] ?? '14 km/h' }}</span>
        </div>
        <div class="flex justify-between border-b border-white/5 pb-1">
          <span>ALTITUDE MÉDIA:</span> <span>{{ $theme->layout_config['hud_telemetry_alt'] ?? '120m' }}</span>
        </div>
        <div class="flex justify-between">
          <span>SINAL GPS:</span> <span class="text-emerald-400">{{ $theme->layout_config['hud_telemetry_gps'] ?? 'EXCELENTE' }}</span>
        </div>
        <div class="mt-4 pt-2 border-t border-white/10 text-center">
          <button class="px-2 py-1 rounded bg-[#06b6d4]/10 border border-[#06b6d4]/30 text-[#06b6d4] text-[9px] hover:bg-[#06b6d4]/20 transition" onclick="triggerPingSound()">Sonar Ping Manual</button>
        </div>
      </div>
    </div>
  </div>
  <button id="telemetry-trigger-btn" onclick="toggleTelemetryOpen(true, event)">📊 Telemetria</button>
  @endif
</section>
HTML;

// ─── TEAM ────────────────────────────────────────────────────────────────────
$sections['team'] = <<<'HTML'
@php
  $c = $content ?? [];
  $heading = $c['heading'] ?? 'A Nossa Equipa de Comando';
  $subtext = $c['subtext'] ?? 'Engenheiros, cientistas e visionários com décadas de experiência aeroespacial e tecnológica.';
  $items = $c['items'] ?? [
    ['name' => 'Dr. Miguel Afonso', 'role' => 'CEO & Co-Fundador', 'bio' => 'Doutorado em Engenharia Aeroespacial pelo IST Lisboa. Ex-investigador da ESA.'],
    ['name' => 'Eng.ª Beatriz Nkosi', 'role' => 'CTO & Co-Fundadora', 'bio' => 'Especialista em sistemas de IA embarcada e autonomia de voo. Mestre em Robótica.'],
    ['name' => 'Cmdt. João Lopes', 'role' => 'Director de Operações', 'bio' => '20 anos de experiência militar em operações aéreas. Certificado ANAC/CAA.'],
    ['name' => 'Dra. Ana Fernandes', 'role' => 'Directora Comercial', 'bio' => 'MBA pela Nova SBE. Especialista em parcerias estratégicas em mercados emergentes.']
  ];
  
  $secBg = $c['bg_color'] ?? $settings['bg_color'] ?? $c['custom_bg_color'] ?? null;
  $secText = $c['text_color'] ?? $settings['text_color'] ?? null;
@endphp
<section class="py-24 bg-gradient-to-b from-[#030712] to-[#070C18] relative overflow-hidden" style="@if($secBg) background: {{ $secBg }} !important; @endif @if($secText) color: {{ $secText }} !important; @endif">
  <div class="absolute inset-0 opacity-[0.03]" style="background-image:radial-gradient(#06B6D4 1px,transparent 1px);background-size:32px 32px"></div>
  <div class="max-w-6xl mx-auto px-6 relative z-10">
    <div class="text-center mb-16">
      <span class="inline-flex items-center gap-2 text-[11px] font-mono uppercase tracking-[0.25em] text-[#06B6D4] mb-4">
        <span class="w-2 h-2 rounded-full bg-[#06B6D4] animate-pulse"></span> Liderança
      </span>
      <h2 class="text-4xl font-bold text-white font-heading">{{ $heading }}</h2>
      @if(!empty($subtext))
        <p class="text-slate-400 mt-4 max-w-xl mx-auto text-sm">{{ $subtext }}</p>
      @endif
    </div>
    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
      @foreach($items as $item)
        @php
          $initials = '';
          foreach (explode(' ', $item['name']) as $w) {
              $initials .= strtoupper(substr($w, 0, 1));
          }
          $initials = substr($initials, 0, 2);
        @endphp
        <div class="bg-[#070C18] border border-white/8 rounded-2xl p-6 text-center group hover:border-[#06B6D4]/30 transition-all duration-300">
          @if(!empty($item['image']) || !empty($item['photo']))
            <img src="{{ $item['image'] ?? $item['photo'] }}" alt="{{ $item['name'] }}" class="w-16 h-16 rounded-2xl object-cover mx-auto mb-4 group-hover:scale-105 transition-transform duration-300 border border-white/10" />
          @else
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-[#2563EB] to-[#06B6D4] flex items-center justify-center text-white font-bold text-xl mx-auto mb-4 group-hover:scale-105 transition-transform duration-300">{{ $initials }}</div>
          @endif
          <div class="text-white font-semibold font-heading">{{ $item['name'] }}</div>
          <div class="text-[#06B6D4] text-xs font-mono uppercase tracking-wider mt-1 mb-3">{{ $item['role'] }}</div>
          <p class="text-slate-500 text-xs leading-relaxed">{{ $item['bio'] ?? $item['text'] ?? '' }}</p>
          @if(!empty($item['linkedin']) || !empty($item['url']))
            <a href="{{ $item['linkedin'] ?? $item['url'] }}" target="_blank" class="inline-flex items-center gap-1 mt-4 text-[10px] text-slate-600 hover:text-[#06B6D4] transition-colors font-mono uppercase tracking-wider">LinkedIn ↗</a>
          @endif
        </div>
      @endforeach
    </div>
  </div>
</section>
HTML;

// ─── TEXT ────────────────────────────────────────────────────────────────────
$sections['text'] = <<<'HTML'
@php
  $c = $content ?? [];
  $heading = $c['heading'] ?? 'Missão Orbital, Impacto Terrestre';
  $body = $c['body'] ?? 'A AeroSpace nasceu em Luanda em 2019, quando um grupo de engenheiros aeronáuticos e especialistas in inteligência artificial uniram forças com uma missão clara: levar a tecnologia aeroespacial ao serviço do desenvolvimento de Angola e de África.';
  
  $secBg = $c['bg_color'] ?? $settings['bg_color'] ?? $c['custom_bg_color'] ?? null;
  $secText = $c['text_color'] ?? $settings['text_color'] ?? null;
@endphp
<section class="py-20 bg-[#070C18] relative" style="@if($secBg) background: {{ $secBg }} !important; @endif @if($secText) color: {{ $secText }} !important; @endif">
  <div class="max-w-4xl mx-auto px-6">
    <div class="border-l-2 border-[#06B6D4]/30 pl-8">
      <span class="inline-flex items-center gap-2 text-[11px] font-mono uppercase tracking-[0.25em] text-[#06B6D4] mb-6">
        <span class="w-2 h-2 rounded-full bg-[#06B6D4] animate-pulse"></span> A Nossa História
      </span>
      <h2 class="text-3xl font-bold text-white font-heading mb-6">{{ $heading }}</h2>
      <div class="text-slate-300 leading-relaxed space-y-5 text-[0.95rem]">
        @if(str_contains($body, "\n"))
          @foreach(explode("\n", $body) as $para)
            <p>{{ $para }}</p>
          @endforeach
        @else
          <p>{{ $body }}</p>
        @endif
      </div>
    </div>
  </div>
</section>
HTML;

// ─── ABOUT ───────────────────────────────────────────────────────────────────
$sections['about'] = <<<'HTML'
@php
  $c = $content ?? [];
  $heading = $c['heading'] ?? 'Logistica autonoma <span class="text-[#06B6D4]">para alem do horizonte</span>';
  $body = $c['subheading'] ?? $c['body'] ?? 'A AeroSpace opera uma frota de transporte vertical de mercadorias coordenada por inteligencia artificial e monitorizacao orbital em tempo real. Cada entrega e calculada ao milimetro, com redundancia de rota e telemetria continua.';
  $items = $c['items'] ?? [
    'Corredores aereos certificados e geofencing dinamico',
    'Carga util ate 40kg com estabilizacao giroscopica',
    'Rastreio orbital com precisao sub-metrica'
  ];
  
  $secBg = $c['bg_color'] ?? $settings['bg_color'] ?? $c['custom_bg_color'] ?? null;
  $secText = $c['text_color'] ?? $settings['text_color'] ?? null;
@endphp
<section id="sobre" class="py-24 bg-[#030712] relative overflow-hidden" style="@if($secBg) background: {{ $secBg }} !important; @endif @if($secText) color: {{ $secText }} !important; @endif">
  <div class="absolute inset-0 opacity-[0.04]" style="background-image:radial-gradient(#06B6D4 1px,transparent 1px);background-size:28px 28px"></div>
  <div class="max-w-6xl mx-auto px-6 relative z-10">
    <div class="grid md:grid-cols-2 gap-14 items-center">
      <div>
        <span class="inline-flex items-center gap-2 text-[11px] font-mono uppercase tracking-[0.25em] text-[#06B6D4] mb-4">
          <span class="w-2 h-2 rounded-full bg-[#06B6D4] animate-pulse"></span> Missao Orbital
        </span>
        <h2 class="text-4xl md:text-5xl font-bold text-white font-heading leading-tight mb-6">{!! $heading !!}</h2>
        <p class="text-slate-400 text-lg leading-relaxed mb-6">{{ $body }}</p>
        <ul class="space-y-3">
          @foreach($items as $item)
            <li class="flex items-start gap-3 text-slate-300">
              <span class="text-[#06B6D4] mt-1">&#10142;</span>
              {{ is_array($item) ? ($item['title'] ?? '') : $item }}
            </li>
          @endforeach
        </ul>
      </div>
      <div class="relative">
        <div class="rounded-2xl border border-white/10 bg-[#070C18]/70 backdrop-blur p-8 shadow-2xl">
          <div class="flex items-center justify-between mb-6">
            <span class="font-mono text-[10px] uppercase tracking-widest text-slate-500">Status do Sistema</span>
            <span class="font-mono text-[10px] text-emerald-400">&#9679; OPERACIONAL</span>
          </div>
          @php
            $metrics = $c['metrics'] ?? $c['status_bars'] ?? [
              ['label' => 'Integridade da Frota', 'value' => '98%'],
              ['label' => 'Cobertura Orbital', 'value' => '91%'],
              ['label' => 'Eficiência Energética', 'value' => '87%']
            ];
          @endphp
          <div class="space-y-5">
            @foreach($metrics as $m)
              @php
                $valStr = $m['value'] ?? '100%';
                $valInt = (int)preg_replace('/[^0-9]/', '', $valStr);
              @endphp
              <div>
                <div class="flex justify-between text-xs text-slate-400 mb-1">
                  <span>{{ $m['label'] }}</span>
                  <span class="text-[#06B6D4]">{{ $valStr }}</span>
                </div>
                <div class="h-1.5 rounded-full bg-white/5 overflow-hidden">
                  <div class="h-full bg-gradient-to-r from-[#2563EB] to-[#06B6D4]" style="width: {{ $valInt }}%"></div>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
HTML;

// ─── STATS ───────────────────────────────────────────────────────────────────
$sections['stats'] = <<<'HTML'
@php
  $c = $content ?? [];
  $items = $c['items'] ?? [
    ['icon' => '🛸', 'value' => '1.240+', 'label' => 'Missoes Concluidas'],
    ['icon' => '📡', 'value' => '99.98%', 'label' => 'Disponibilidade'],
    ['icon' => '🚁', 'value' => '48', 'label' => 'Drones na Frota'],
    ['icon' => '🌍', 'value' => '24/7', 'label' => 'Monitorizacao Orbital']
  ];
  
  $secBg = $c['bg_color'] ?? $settings['bg_color'] ?? $c['custom_bg_color'] ?? null;
  $secText = $c['text_color'] ?? $settings['text_color'] ?? null;
@endphp
<section class="py-20 bg-gradient-to-b from-[#070C18] to-[#030712] border-y border-white/5 relative" style="@if($secBg) background: {{ $secBg }} !important; @endif @if($secText) color: {{ $secText }} !important; @endif">
  <div class="max-w-6xl mx-auto px-6">
    @if(!empty($c['heading']))
      <div class="text-center mb-10">
        <h2 class="text-2xl font-bold text-white font-heading">{{ $c['heading'] }}</h2>
      </div>
    @endif
    <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
      @foreach($items as $item)
        <div>
          <div class="text-4xl md:text-5xl font-bold font-heading text-white" data-counter="{{ preg_replace('/[^0-9]/', '', $item['value']) }}">{{ $item['value'] }}</div>
          <div class="mt-2 font-mono text-[11px] uppercase tracking-widest text-[#06B6D4] flex items-center justify-center gap-1">
            @if(!empty($item['icon']))
              <span>{{ $item['icon'] }}</span>
            @endif
            {{ $item['label'] }}
          </div>
        </div>
      @endforeach
    </div>
  </div>
</section>
HTML;

// ─── STEPS ───────────────────────────────────────────────────────────────────
$sections['steps'] = <<<'HTML'
@php
  $c = $content ?? [];
  $heading = $c['heading'] ?? 'Como Funciona';
  $items = $c['items'] ?? [
    ['number' => '01', 'title' => 'Briefing da Missão', 'text' => 'Reunião técnica para definir objectivos, rotas, payload e requisitos específicos da operação.'],
    ['number' => '02', 'title' => 'Planeamento & Licenças', 'text' => 'A nossa equipa trata de toda a burocracia: licenças ANAC, corredores aéreos e planos de contingência.'],
    ['number' => '03', 'title' => 'Execução Autónoma', 'text' => 'Lançamento da missão com monitorização em tempo real pelo nosso centro de controlo em Luanda.'],
    ['number' => '04', 'title' => 'Relatório Técnico', 'text' => 'Entrega de relatório completo com telemetria, imagens, métricas e dados de qualidade da missão.']
  ];
  
  $secBg = $c['bg_color'] ?? $settings['bg_color'] ?? $c['custom_bg_color'] ?? null;
  $secText = $c['text_color'] ?? $settings['text_color'] ?? null;
@endphp
<section class="py-24 bg-[#070C18] relative overflow-hidden" style="@if($secBg) background: {{ $secBg }} !important; @endif @if($secText) color: {{ $secText }} !important; @endif">
  <div class="max-w-4xl mx-auto px-6">
    <div class="text-center mb-16">
      <span class="inline-flex items-center gap-2 text-[11px] font-mono uppercase tracking-[0.25em] text-[#06B6D4] mb-4">
        <span class="w-2 h-2 rounded-full bg-[#06B6D4] animate-pulse"></span> Processo
      </span>
      <h2 class="text-4xl font-bold text-white font-heading">{{ $heading }}</h2>
    </div>
    <div class="space-y-8">
      @foreach($items as $item)
        <div class="flex items-start gap-8 group">
          <div class="flex-shrink-0">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-[#2563EB] to-[#06B6D4] flex items-center justify-center font-mono font-bold text-white text-sm group-hover:scale-110 transition-transform duration-300 shadow-lg shadow-[#06B6D4]/20">
              {{ $item['number'] ?? sprintf('%02d', $loop->iteration) }}
            </div>
          </div>
          <div class="bg-[#0A0F1E] border border-white/8 rounded-2xl p-6 flex-1 group-hover:border-[#06B6D4]/25 transition-all duration-300">
            <h3 class="text-white font-bold font-heading text-lg mb-2">{{ $item['title'] }}</h3>
            <p class="text-slate-400 text-sm leading-relaxed">{{ $item['text'] }}</p>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</section>
HTML;

// ─── CONTACT ─────────────────────────────────────────────────────────────────
$sections['contact'] = <<<'HTML'
@php
  $c = $content ?? [];
  $heading = $c['heading'] ?? 'Iniciar a Sua Missão';
  $subtext = $c['subtext'] ?? 'A nossa equipa está disponível 24/7. Respondemos em menos de 2 horas em dias úteis.';
  $btnText = $c['button_text'] ?? 'SUBMETER PEDIDO DE MISSÃO';

  $cEmail = $theme->layout_config['contact_email'] ?? 'comercial@aerospace.ao';
  $cPhone = $theme->layout_config['contact_phone'] ?? '+244 923 456 780';
  $cHours = $theme->layout_config['contact_hours'] ?? 'Seg–Sex · 08h00–18h00 (WAT)';

  $domainParts = explode('@', $cEmail);
  $domain = isset($domainParts[1]) ? $domainParts[1] : 'aerospace.ao';
  $opsEmail = $theme->layout_config['contact_mailto'] ?? ('ops@' . $domain);
  $opsPhone = (strlen($cPhone) > 5) ? (substr($cPhone, 0, -1) . '9') : '+244 923 456 789';

  $formSuccess = $theme->layout_config['contact_form_success_message'] ?? '✅ Missão submetida com sucesso! A nossa equipa de operações entrará em contacto em breve.';

  // Serviços do dropdown (um por linha)
  $servicesList = $theme->layout_config['contact_services_list'] ?? "Transporte Autónomo de Carga\nCartografia & Fotogrametria\nVigilância & Patrulhamento\nInspecção Industrial\nLogística de Emergência\nAgricultura de Precisão";
  $servicesOptions = array_filter(array_map('trim', explode("\n", $servicesList)));

  $loc = $theme->layout_config['footer_location'] ?? 'Luanda';
  $hq1 = $theme->layout_config['contact_address_hq'] ?? 'Rua Rainha Ginga, Edifício AeroSpace Tower';
  $hq2 = $theme->layout_config['contact_address_sub'] ?? 'Miramar, Luanda, Angola';

  $hgr1 = $theme->layout_config['contact_address_hangar'] ?? 'Aeroporto Internacional 4 de Fevereiro';
  $hgr2 = $theme->layout_config['contact_address_hangar_sub'] ?? 'Zona Técnica Norte — Hangar AX, Luanda';
  
  $secBg = $c['bg_color'] ?? $settings['bg_color'] ?? $c['custom_bg_color'] ?? null;
  $secText = $c['text_color'] ?? $settings['text_color'] ?? null;
@endphp
<section class="py-24 bg-[#070C18] relative overflow-hidden" style="@if($secBg) background: {{ $secBg }} !important; @endif @if($secText) color: {{ $secText }} !important; @endif">
  <div class="absolute inset-0 opacity-[0.03]" style="background-image:radial-gradient(#06B6D4 1px,transparent 1px);background-size:28px 28px"></div>
  <div class="max-w-6xl mx-auto px-6 relative z-10">
    <div class="text-center mb-16">
      <span class="inline-flex items-center gap-2 text-[11px] font-mono uppercase tracking-[0.25em] text-[#06B6D4] mb-4">
        <span class="w-2 h-2 rounded-full bg-[#06B6D4] animate-pulse"></span> Centro de Controlo
      </span>
      <h2 class="text-4xl font-bold text-white font-heading">{{ $heading }}</h2>
      <p class="text-slate-400 mt-4 max-w-xl mx-auto text-sm">{{ $subtext }}</p>
    </div>
    <div class="grid lg:grid-cols-2 gap-16 items-start">

      <!-- Canais de Contacto -->
      <div class="space-y-6">
        <div class="bg-[#0A0F1E] border border-white/8 rounded-2xl p-6 flex items-start gap-4 hover:border-[#06B6D4]/25 transition-all duration-300">
          <div class="text-2xl flex-shrink-0 mt-1">📡</div>
          <div>
            <div class="text-white font-semibold font-heading mb-1">Canal Operacional</div>
            <div class="text-slate-400 text-sm">{{ $opsEmail }} · {{ $opsPhone }}</div>
            <div class="text-[#06B6D4] text-xs font-mono mt-1">Disponível 24/7 for emergencies</div>
          </div>
        </div>
        <div class="bg-[#0A0F1E] border border-white/8 rounded-2xl p-6 flex items-start gap-4 hover:border-[#06B6D4]/25 transition-all duration-300">
          <div class="text-2xl flex-shrink-0 mt-1">💼</div>
          <div>
            <div class="text-white font-semibold font-heading mb-1">Propostas Comerciais</div>
            <div class="text-slate-400 text-sm">{{ $cEmail }} · {{ $cPhone }}</div>
            <div class="text-[#06B6D4] text-xs font-mono mt-1">{{ $cHours }}</div>
          </div>
        </div>
        <div class="bg-[#0A0F1E] border border-white/8 rounded-2xl p-6 flex items-start gap-4 hover:border-[#06B6D4]/25 transition-all duration-300">
          <div class="text-2xl flex-shrink-0 mt-1">📍</div>
          <div>
            <div class="text-white font-semibold font-heading mb-1">Sede — {{ $loc }}</div>
            <div class="text-slate-400 text-sm">{{ $hq1 }}</div>
            <div class="text-slate-400 text-sm">{{ $hq2 }}</div>
          </div>
        </div>
        <div class="bg-[#0A0F1E] border border-white/8 rounded-2xl p-6 flex items-start gap-4 hover:border-[#06B6D4]/25 transition-all duration-300">
          <div class="text-2xl flex-shrink-0 mt-1">✈️</div>
          <div>
            <div class="text-white font-semibold font-heading mb-1">Hangar & Testes</div>
            <div class="text-slate-400 text-sm">{{ $hgr1 }}</div>
            <div class="text-slate-400 text-sm">{{ $hgr2 }}</div>
          </div>
        </div>
      </div>

      <!-- Formulário -->
      <div class="bg-[#0A0F1E] border border-white/8 rounded-2xl p-8">
        <h3 class="text-white font-bold font-heading text-xl mb-2">Enviar Mensagem</h3>
        <p class="text-slate-500 text-sm mb-6">Descreva a sua necessidade e um especialista entrará em contacto.</p>
        <form id="aerospace-contact-detail-form" class="space-y-4" novalidate
              onsubmit="handleAerospaceDetailFormSubmit(event)"
              data-mailto="{{ $opsEmail }}"
              data-success="{{ $formSuccess }}">
          @csrf
          <div>
            <label for="detail-name" class="block text-xs font-mono uppercase tracking-wider text-slate-400 mb-1.5">Nome / Empresa</label>
            <input type="text" id="detail-name" name="nome" placeholder="{{ $theme->layout_config['contact_form_placeholder_name'] ?? 'Ex: Grupo Logística Angola' }}" required
                   class="w-full bg-[#070C18] border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-slate-600 focus:outline-none focus:border-[#06B6D4]/50 transition-colors">
          </div>
          <div>
            <label for="detail-email" class="block text-xs font-mono uppercase tracking-wider text-slate-400 mb-1.5">E-mail</label>
            <input type="email" id="detail-email" name="email" placeholder="{{ $theme->layout_config['contact_form_placeholder_email'] ?? 'missao@empresa.ao' }}" required
                   class="w-full bg-[#070C18] border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-slate-600 focus:outline-none focus:border-[#06B6D4]/50 transition-colors">
          </div>
          <div>
            <label for="detail-service" class="block text-xs font-mono uppercase tracking-wider text-slate-400 mb-1.5">Serviço de Interesse</label>
            <select id="detail-service" name="servico" class="w-full bg-[#070C18] border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-[#06B6D4]/50 transition-colors">
              @foreach($servicesOptions as $srv)
                <option>{{ $srv }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label for="detail-message" class="block text-xs font-mono uppercase tracking-wider text-slate-400 mb-1.5">Descrição da Missão</label>
            <textarea id="detail-message" name="mensagem" rows="4" placeholder="{{ $theme->layout_config['contact_form_placeholder_message'] ?? 'Descreva a sua necessidade operacional...' }}" required
                      class="w-full bg-[#070C18] border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-slate-600 focus:outline-none focus:border-[#06B6D4]/50 transition-colors resize-none"></textarea>
          </div>
          <div id="detail-form-feedback" class="hidden text-xs py-2 rounded-lg px-3"></div>
          <button type="submit" id="detail-submit-btn"
                  class="w-full bg-gradient-to-r from-[#2563EB] to-[#06B6D4] text-white font-bold py-3 rounded-xl hover:opacity-90 transition-opacity font-heading tracking-wider text-sm">
            {{ strtoupper($btnText) }} ➤
          </button>
        </form>
      </div>
    </div>
  </div>
</section>
HTML;

// ─── GALLERY ─────────────────────────────────────────────────────────────────
$sections['gallery'] = <<<'HTML'
@php
  $c = $content ?? [];
  $heading = $c['heading'] ?? 'Arquivo Visual';
  $images = $c['images'] ?? [
    ['src' => 'https://images.unsplash.com/photo-1473968512647-3e447244af8f?w=800&q=80', 'alt' => 'Drone de carga em voo', 'caption' => 'Drone Série AX-7 — Missão Malanje, 2024'],
    ['src' => 'https://images.unsplash.com/photo-1518623489648-a173ef7824f3?w=800&q=80', 'alt' => 'Aterragem de precisão', 'caption' => 'Aterragem em zona remota — Uíge, 2024'],
    ['src' => 'https://images.unsplash.com/photo-1601618440254-abf3e87f87e8?w=800&q=80', 'alt' => 'Frota em hangar', 'caption' => 'Hangar Central — Luanda, 2025']
  ];
  
  $secBg = $c['bg_color'] ?? $settings['bg_color'] ?? $c['custom_bg_color'] ?? null;
  $secText = $c['text_color'] ?? $settings['text_color'] ?? null;
@endphp
<section class="py-12 bg-[#030712] relative overflow-hidden" style="@if($secBg) background: {{ $secBg }} !important; @endif @if($secText) color: {{ $secText }} !important; @endif">
  <div class="max-w-6xl mx-auto px-6">
    <h3 class="text-sm font-mono uppercase tracking-wider text-[#06B6D4] mb-6 flex items-center gap-3">
      <span class="w-8 h-px bg-[#06B6D4]/40"></span> {{ $heading }}
    </h3>
    @php
      $layout = $theme->layout_config['gallery_layout'] ?? '3d-carousel';
      $autoRotate = ($theme->layout_config['gallery_auto_rotate'] ?? true) ? 'true' : 'false';
      $tilt = ($theme->layout_config['gallery_tilt_enabled'] ?? true) ? 'true' : 'false';
    @endphp

    @if($layout === '3d-carousel')
      <!-- 3D CAROUSEL LAYOUT -->
      <div class="relative py-10">
        <div class="gallery-3d-viewport">
          <div class="gallery-3d-scene" data-auto-rotate="{{ $autoRotate }}" data-tilt="{{ $tilt }}">
            @foreach($images as $index => $img)
              <div class="gallery-3d-item group/item" data-index="{{ $index }}">
                <img src="{{ $img['src'] }}" alt="{{ $img['alt'] ?? '' }}" />
                <div class="item-caption opacity-0 group-hover/item:opacity-100 transition-opacity duration-300">
                  <p class="font-bold text-white mb-0.5">{{ $img['alt'] ?? '' }}</p>
                  <p class="text-slate-400 text-[10px]">{{ $img['caption'] ?? '' }}</p>
                </div>
              </div>
            @endforeach
          </div>
          
          <!-- Navegação -->
          <div class="gallery-3d-nav">
            <button class="gallery-3d-btn" onclick="rotate3DGallery('prev')" aria-label="Anterior">❮</button>
            <button class="gallery-3d-btn" onclick="rotate3DGallery('next')" aria-label="Seguinte">❯</button>
          </div>
        </div>
        
        <!-- Pontos Indicadores -->
        <div class="gallery-3d-dots"></div>
      </div>
    @elseif($layout === 'masonry')
      <!-- MASONRY LAYOUT -->
      <div class="gallery-masonry">
        @foreach($images as $img)
          <div class="gallery-masonry-item group relative overflow-hidden aspect-auto">
            <img src="{{ $img['src'] }}" alt="{{ $img['alt'] ?? '' }}" class="w-full object-cover opacity-85 group-hover:opacity-100 group-hover:scale-[1.02] transition-all duration-300">
            <div class="absolute inset-0 bg-gradient-to-t from-[#030712]/95 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end p-4">
              <div>
                <p class="text-white text-xs font-mono font-bold">{{ $img['alt'] ?? '' }}</p>
                <p class="text-slate-400 text-[10px] font-mono mt-0.5">{{ $img['caption'] ?? '' }}</p>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    @else
      <!-- GRID LAYOUT (Default fallback) -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach($images as $img)
          <div class="group relative overflow-hidden rounded-xl border border-white/8 aspect-video bg-[#070C18] hover:border-[#06B6D4]/30 transition-all duration-300">
            <img src="{{ $img['src'] }}" alt="{{ $img['alt'] ?? '' }}"
                 class="w-full h-full object-cover opacity-80 group-hover:opacity-100 group-hover:scale-105 transition-all duration-500">
            <div class="absolute inset-0 bg-gradient-to-t from-[#030712]/90 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end p-4">
              <div>
                <p class="text-white text-xs font-mono font-bold">{{ $img['alt'] ?? '' }}</p>
                <p class="text-slate-400 text-[10px] font-mono mt-0.5">{{ $img['caption'] ?? '' }}</p>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </div>
</section>
HTML;

// ─── FEATURES ────────────────────────────────────────────────────────────────
$sections['features'] = <<<'HTML'
@php
  $c = $content ?? [];
  $heading = $c['heading'] ?? 'Operações de Altitude';
  $subheading = $c['subtext'] ?? $c['subheading'] ?? 'Substituímos a logística tradicional por voos autónomos inteligentes e cartografia computacional a alta velocidade.';
  $items = $c['items'] ?? [
    ['icon' => '🛸', 'title' => 'Logística Autónoma', 'text' => 'Transporte aéreo de cargas pesadas a longa distância com frotas de aeronaves elétricas não tripuladas de alto desempenho.'],
    ['icon' => '🛰️', 'title' => 'Cartografia Orbital', 'text' => 'Mapeamento terrestre detalhado através de satélite e sensores multiespectrais para análise e inteligência geoespacial crítica.'],
    ['icon' => '👁️', 'title' => 'Vigilância e Reconhecimento', 'text' => 'Patrulhamento aéreo ativo com drones de alta altitude com visores térmicos e telemetria por radar laser em tempo real.']
  ];
  $gridCols = count($items) > 3 ? 'md:grid-cols-2 lg:grid-cols-4' : 'md:grid-cols-3';
  
  $secBg = $c['bg_color'] ?? $settings['bg_color'] ?? $c['custom_bg_color'] ?? null;
  $secText = $c['text_color'] ?? $settings['text_color'] ?? null;
@endphp
<section id="servicos" class="py-24 bg-[#070C18] relative overflow-hidden" style="@if($secBg) background: {{ $secBg }} !important; @endif @if($secText) color: {{ $secText }} !important; @endif">
  <div class="max-w-6xl mx-auto px-6">
    <div class="text-center mb-16">
      <h2 class="text-4xl font-bold tracking-tight text-white font-heading">{{ $heading }}</h2>
      @if(!empty($subheading))
        <p class="mt-4 text-lg text-slate-400 max-w-2xl mx-auto">{{ $subheading }}</p>
      @endif
    </div>
    <div class="grid grid-cols-1 {{ $gridCols }} gap-8">
      @foreach($items as $index => $item)
        <div class="feature-card group/card" onmouseenter="playHoverChirp()">
          <div class="card-glow"></div>
          <span class="card-num">{{ sprintf('%02d', $index + 1) }}</span>
          <h3 class="text-xl font-bold text-white mb-2 font-heading flex items-center gap-2">
            @if(!empty($item['icon']))
              <span class="text-cyan-400 text-lg">{{ $item['icon'] }}</span>
            @endif
            {{ $item['title'] }}
          </h3>
          <p class="text-slate-400 text-sm">{{ $item['text'] }}</p>
        </div>
      @endforeach
    </div>

    <!-- Painel de Especificações Interativo (Drone Hotspots Showcase) -->
    @php
      $blueprintImg = $c['blueprint_image'] ?? null;
      $hotspots = $c['hotspots'] ?? $c['blueprint_hotspots'] ?? [
        ['top' => '13%', 'left' => '13%', 'title' => 'Propulsores Elétricos VTOL', 'desc' => 'Quatro propulsores elétricos independentes de alta eficácia para descolagem e aterragem vertical autónoma em qualquer terreno.'],
        ['top' => '48%', 'left' => '48%', 'title' => 'Processador de IA & LIDAR', 'desc' => 'Núcleo de computação de bordo com sensor LIDAR de alta frequência para desvio dinâmico de obstáculos a 360º durante voos de baixa altitude.', 'active' => true],
        ['top' => '83%', 'left' => '83%', 'title' => 'Módulo de Energia de Lítio-Enxofre', 'desc' => 'Baterias aeroespaciais avançadas com densidade de carga triplicada face ao ião de lítio convencional, garantindo autonomias de até 150 km.']
      ];
      $defaultActiveHs = array_values(array_filter($hotspots, fn($hs) => $hs['active'] ?? false))[0] ?? ($hotspots[0] ?? null);
      $defaultTitle = $defaultActiveHs['title'] ?? 'Processador de IA & LIDAR';
      $defaultDesc = $defaultActiveHs['desc'] ?? '';
    @endphp
    <div class="drone-blueprint-showcase mt-16 border border-white/10 rounded-2xl bg-slate-900/40 p-8 backdrop-blur-md relative overflow-hidden">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
        <div class="relative flex justify-center">
          <div class="blueprint-vector">
            @if(!empty($blueprintImg))
              <img src="{{ $blueprintImg }}" alt="Blueprint Inspecção" class="w-64 h-64 object-contain opacity-40" />
            @else
              <!-- SVG Esquema de Drone -->
              <svg class="w-64 h-64 text-cyan-500/15" viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="0.75">
                <circle cx="50" cy="50" r="12" stroke-dasharray="2 2" />
                <circle cx="50" cy="50" r="4" />
                <line x1="15" y1="15" x2="85" y2="85" />
                <line x1="15" y1="85" x2="85" y2="15" />
                <!-- Rotors -->
                <circle cx="15" cy="15" r="8" />
                <circle cx="85" cy="15" r="8" />
                <circle cx="15" cy="85" r="8" />
                <circle cx="85" cy="85" r="8" />
                <!-- Wings -->
                <path d="M25,50 L75,50 M50,25 L50,75" stroke-width="0.5" />
              </svg>
            @endif
            <!-- Interactive Hotspot Dots -->
            @foreach($hotspots as $hs)
              <button class="hotspot-dot @if($hs['active'] ?? false) active-hotspot @endif" 
                      style="top: {{ $hs['top'] }}; left: {{ $hs['left'] }};" 
                      data-title="{{ $hs['title'] }}" 
                      data-desc="{{ $hs['desc'] }}">
                <span class="ping-wave"></span>
              </button>
            @endforeach
          </div>
        </div>
        <div class="blueprint-info text-left">
          <div class="inline-block px-3 py-1 rounded bg-cyan-500/10 border border-cyan-500/20 text-[#06b6d4] text-[10px] uppercase font-bold tracking-widest mb-3">Inspecção de Hardware</div>
          <h3 class="text-2xl font-bold text-white mb-2 font-heading" id="blueprint-item-title">{{ $defaultTitle }}</h3>
          <p class="text-slate-400 text-sm leading-relaxed" id="blueprint-item-desc">{{ $defaultDesc }}</p>
        </div>
      </div>
    </div>
  </div>
</section>
HTML;

// ─── TESTIMONIALS ────────────────────────────────────────────────────────────
$sections['testimonials'] = <<<'HTML'
@php
  $c = $content ?? [];
  $heading = $c['heading'] ?? 'O Que Dizem os Nossos Parceiros';
  $items = $c['items'] ?? [
    ['quote' => 'A AeroSpace transformou completamente a nossa operação de distribuição logística. Os drones chegam antes do prazo, com precisão surpreendente.', 'name' => 'Carlos Fernandes', 'role' => 'Director de Operações', 'company' => 'Grupo Logística Angola'],
    ['quote' => 'A cartografia orbital reduziu em 40% o tempo de planeamento das nossas obras. Tecnologia sem igual no mercado africano.', 'name' => 'Eng.ª Sofia Mendes', 'role' => 'Engenheira Chefe', 'company' => 'Construções INGA'],
    ['quote' => 'Monitorização em tempo real, equipa disponível 24/7 e tecnologia de ponta. Recomendo a AeroSpace sem reservas para missões críticas.', 'name' => 'Maj. António Silva', 'role' => 'Coordenador de Segurança', 'company' => 'Ministério do Interior']
  ];
  
  $secBg = $c['bg_color'] ?? $settings['bg_color'] ?? $c['custom_bg_color'] ?? null;
  $secText = $c['text_color'] ?? $settings['text_color'] ?? null;
@endphp
<section class="py-24 bg-[#030712] relative overflow-hidden" style="@if($secBg) background: {{ $secBg }} !important; @endif @if($secText) color: {{ $secText }} !important; @endif">
  <div class="absolute inset-0 opacity-[0.03]" style="background-image:radial-gradient(#06B6D4 1px,transparent 1px);background-size:24px 24px"></div>
  <div class="max-w-6xl mx-auto px-6 relative z-10">
    <div class="text-center mb-16">
      <span class="inline-flex items-center gap-2 text-[11px] font-mono uppercase tracking-[0.25em] text-[#06B6D4] mb-4">
        <span class="w-2 h-2 rounded-full bg-[#06B6D4] animate-pulse"></span> Parceiros & Clientes
      </span>
      <h2 class="text-4xl font-bold text-white font-heading">{{ $heading }}</h2>
    </div>
    <div class="grid md:grid-cols-3 gap-8">
      @foreach($items as $item)
        @php
          $initials = '';
          foreach (explode(' ', $item['name']) as $w) {
              $initials .= strtoupper(substr($w, 0, 1));
          }
          $initials = substr($initials, 0, 2);
        @endphp
        <div class="bg-[#070C18] border border-white/8 rounded-2xl p-8 relative group hover:border-[#06B6D4]/30 transition-all duration-300">
          <div class="absolute top-6 right-6 text-[#06B6D4]/20 text-5xl font-serif">"</div>
          @php
            $stars = (int)($item['rating'] ?? $item['stars'] ?? 5);
          @endphp
          <div class="flex gap-1 mb-4 text-[#06B6D4]">
            @for($i = 0; $i < 5; $i++)
              <span class="{{ $i < $stars ? 'text-[#06B6D4]' : 'text-slate-700/60' }}">★</span>
            @endfor
          </div>
          <p class="text-slate-300 text-sm leading-relaxed mb-6 italic">"{{ $item['quote'] }}"</p>
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#2563EB] to-[#06B6D4] flex items-center justify-center text-white font-bold text-sm">{{ $initials }}</div>
            <div>
              <div class="text-white font-semibold text-sm">{{ $item['name'] }}</div>
              <div class="text-slate-500 text-xs">{{ $item['role'] }} · {{ $item['company'] }}</div>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</section>
HTML;

// ─── FOOTER ──────────────────────────────────────────────────────────────────
$sections['footer'] = <<<'HTML'
<footer class="bg-[var(--color-secondary,#030712)] border-t border-white/5 pt-16 pb-8 relative overflow-hidden">
  @php
    $logoType = $theme->layout_config['logo_type'] ?? 'both';
    $logoHeight = ($theme->layout_config['logo_height'] ?? 36) . 'px';
    $logoTextSize = ($theme->layout_config['logo_text_size'] ?? 20) . 'px';
    $logoTextWeight = $theme->layout_config['logo_text_weight'] ?? 'bold';
    $siteName = $site_name ?? 'AeroSpace';
    $homepageUrl = $site_homepage_url ?? '/';
    $footerDesc = $theme->layout_config['footer_description'] ?? 'Logística autónoma de carga, transporte vertical e monitorização orbital.';
    $footerLoc = $theme->layout_config['footer_location'] ?? 'Luanda, Angola';
    $footerLat = $theme->layout_config['footer_lat'] ?? '-8.8124';
    $footerLon = $theme->layout_config['footer_lon'] ?? '13.2306';
    $footerAlt = $theme->layout_config['footer_alt'] ?? '0m';
    $footerStatus = $theme->layout_config['footer_status_text'] ?? 'Sistema Operacional';
    $copyrightText = $theme->layout_config['footer_copyright'] ?? '';
    if (empty($copyrightText)) {
        $copyrightText = '&copy; ' . date('Y') . ' ' . $siteName . ' &middot; Todos os direitos reservados';
    }
  @endphp
  <div class="max-w-6xl mx-auto px-6">
    <div class="grid md:grid-cols-4 gap-10 mb-12">
      <div class="md:col-span-1">
        <div class="flex items-center gap-2 mb-4 logo-area">
          @if($logoType === 'image' || $logoType === 'both')
            <a href="{{ $homepageUrl }}" class="logo-img-link">
              <img src="{{ $theme->layout_config['logo_image_light'] ?? '/images/aerospace-logo.svg' }}" 
                   alt="{{ $siteName }}" 
                   style="height: {{ $logoHeight }};"
                   class="logo-light" />
              @if(!empty($theme->layout_config['logo_image_dark']))
                <img src="{{ $theme->layout_config['logo_image_dark'] }}" 
                     alt="{{ $siteName }}" 
                     style="height: {{ $logoHeight }};"
                     class="logo-dark hidden" />
              @endif
            </a>
          @endif

          @if($logoType === 'text' || $logoType === 'both')
            <a href="{{ $homepageUrl }}" class="logo-text-link" style="font-size: {{ $logoTextSize }}; font-weight: {{ $logoTextWeight }};">
              {{ $siteName }}
            </a>
          @endif
        </div>
        <p class="text-slate-500 text-sm leading-relaxed">{{ $footerDesc }}</p>
      </div>
      <div>
        <h4 class="text-white font-semibold mb-4 text-sm">Navegação</h4>
        <ul class="space-y-2 text-sm text-slate-400">
          @foreach($nav_links ?? [] as $link)
            <li>
              <a href="{{ $link['url'] }}" class="hover:text-[var(--color-accent,#06B6D4)] transition" @if(($link['target'] ?? '_self') === '_blank') target="_blank" @endif>
                {{ $link['label'] }}
              </a>
            </li>
          @endforeach
        </ul>
      </div>
      <div>
        <h4 class="text-white font-semibold mb-4 text-sm">Empresa</h4>
        <ul class="space-y-2 text-sm text-slate-400">
          @foreach(array_slice($nav_links ?? [], 0, 3) as $link)
            <li>
              <a href="{{ $link['url'] }}" class="hover:text-[var(--color-accent,#06B6D4)] transition" @if(($link['target'] ?? '_self') === '_blank') target="_blank" @endif>
                {{ $link['label'] }}
              </a>
            </li>
          @endforeach
        </ul>
      </div>
      <div>
        <h4 class="text-white font-semibold mb-4 text-sm">Centro de Controlo</h4>
        <p class="text-slate-400 text-sm mb-3">{{ $footerLoc }}</p>
        <div class="inline-flex items-center gap-2 font-mono text-[11px] text-emerald-400">
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> {{ $footerStatus }}
        </div>
      </div>
    </div>
    <div class="pt-8 border-t border-white/5 flex flex-col md:flex-row items-center justify-between gap-4">
      <p class="font-mono text-[11px] text-slate-600">{!! $copyrightText !!}</p>
      @php
        $socials = [
          'linkedin'  => ['url' => $theme->layout_config['social_linkedin'] ?? '', 'icon' => '🛰️ LinkedIn'],
          'x'         => ['url' => $theme->layout_config['social_x'] ?? '', 'icon' => '🛸 X'],
          'facebook'  => ['url' => $theme->layout_config['social_facebook'] ?? '', 'icon' => '📡 Facebook'],
          'instagram' => ['url' => $theme->layout_config['social_instagram'] ?? '', 'icon' => '👁️ Instagram'],
          'youtube'   => ['url' => $theme->layout_config['social_youtube'] ?? '', 'icon' => '📽️ YouTube'],
        ];
        $activeSocials = array_filter($socials, fn($s) => !empty($s['url']));
      @endphp
      @if(count($activeSocials) > 0)
        <div class="flex gap-4 items-center">
          @foreach($activeSocials as $name => $s)
            <a href="{{ $s['url'] }}" target="_blank" class="font-mono text-[10px] text-slate-500 hover:text-[#06b6d4] transition flex items-center gap-1">
              {{ $s['icon'] }}
            </a>
          @endforeach
        </div>
      @endif
      <p class="font-mono text-[11px] text-slate-600">LAT {{ $footerLat }} &middot; LON {{ $footerLon }} &middot; ALT {{ $footerAlt }}</p>
    </div>
  </div>
</footer>
HTML;

$theme->sections = $sections;

// Fazer o padding-top das subpáginas ser parametrizável dinamicamente nas definições do site
$css = $theme->custom_css;
$css = str_replace(
    '.aerospace-hero.hero-internal .hero-content { min-height: auto; justify-content: flex-start; padding-top: 1.25rem; padding-bottom: 3rem; }',
    '.aerospace-hero.hero-internal .hero-content { min-height: auto; justify-content: flex-start; padding-top: var(--subpage-padding-top, 50px); padding-bottom: 3rem; }',
    $css
);
$css = str_replace(
    '.aerospace-hero.hero-internal .hero-content { min-height: auto; justify-content: flex-start; padding-top: 2.5rem; padding-bottom: 3rem; }',
    '.aerospace-hero.hero-internal .hero-content { min-height: auto; justify-content: flex-start; padding-top: var(--subpage-padding-top, 50px); padding-bottom: 3rem; }',
    $css
);
$css = str_replace(
    '.aerospace-hero.hero-internal .hero-content { min-height: auto; justify-content: flex-start; padding-top: var(--subpage-padding-top, 40px); padding-bottom: 3rem; }',
    '.aerospace-hero.hero-internal .hero-content { min-height: auto; justify-content: flex-start; padding-top: var(--subpage-padding-top, 50px); padding-bottom: 3rem; }',
    $css
);

// Aumentar o espaço abaixo da barra de menus nas subpáginas (+50px → 66px total)
$css = str_replace(
    '.aerospace-hero.hero-internal .hero-text-area { margin-top: var(--menu-space-bottom, 24px); }',
    '.aerospace-hero.hero-internal .hero-text-area { margin-top: 66px; }',
    $css
);

// Adicionar estilos sticky e de layout se em falta
if (!str_contains($css, '/* ── Barra de Menu Fixa (Sticky) ao fazer Scroll ── */')) {
    $css .= "\n\n/* ── Barra de Menu Fixa (Sticky) ao fazer Scroll ── */\n" .
        "@keyframes navbarSlideIn {\n" .
        "  from { transform: translate(-50%, -100%); opacity: 0; }\n" .
        "  to { transform: translate(-50%, 0); opacity: 1; }\n" .
        "}\n\n" .
        ".normal-navbar.is-sticky.scrolled.pos-horizontal-left,\n" .
        ".normal-navbar.is-sticky.scrolled.pos-horizontal-right {\n" .
        "  position: fixed !important;\n" .
        "  top: 1rem !important;\n" .
        "  left: 50% !important;\n" .
        "  transform: translateX(-50%) !important;\n" .
        "  z-index: 100 !important;\n" .
        "  width: calc(100% - 2rem) !important;\n" .
        "  box-shadow: 0 10px 30px rgba(0,0,0,0.5), 0 0 20px rgba(var(--color-accent-rgb, 6, 182, 212), 0.15) !important;\n" .
        "  animation: navbarSlideIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards !important;\n" .
        "}\n\n" .
        "@media (max-width: 640px) {\n" .
        "  .normal-navbar.is-sticky.scrolled.pos-horizontal-left,\n" .
        "  .normal-navbar.is-sticky.scrolled.pos-horizontal-right {\n" .
        "    top: 0.5rem !important;\n" .
        "    width: calc(100% - 1rem) !important;\n" .
        "  }\n" .
        "}\n";
}

if (!str_contains($css, '/* ── Definições de Layout Dinâmico AnimusFlow ── */')) {
    $css .= "\n\n/* ── Definições de Layout Dinâmico AnimusFlow ── */\n" .
        "/* ── Espaçamento Dinâmico de Secções ── */\n" .
        "section:not(.aerospace-hero) {\n" .
        "  padding-top: var(--section-padding-y, 5rem) !important;\n" .
        "  padding-bottom: var(--section-padding-y, 5rem) !important;\n" .
        "}\n\n" .
        "/* ── Largura Máxima Dinâmica do Conteúdo ── */\n" .
        "section:not(.aerospace-hero) > div[class*=\"max-w-\"],\n" .
        "footer > div[class*=\"max-w-\"] {\n" .
        "  max-width: var(--layout-max-width, 1120px) !important;\n" .
        "}\n";
}

if (!str_contains($css, '/* ── Estilos de Logótipo Dinâmico ── */')) {
    $css .= "\n\n/* ── Estilos de Logótipo Dinâmico ── */\n" .
        ".logo-area {\n" .
        "  display: flex !important;\n" .
        "  align-items: center !important;\n" .
        "  gap: 0.75rem !important;\n" .
        "}\n" .
        ".logo-img-link {\n" .
        "  display: flex !important;\n" .
        "  align-items: center !important;\n" .
        "}\n" .
        ".logo-img-link img {\n" .
        "  width: auto !important;\n" .
        "  max-width: 100% !important;\n" .
        "  display: block !important;\n" .
        "}\n" .
        ".logo-text-link {\n" .
        "  color: var(--color-foreground, #ffffff) !important;\n" .
        "  text-decoration: none !important;\n" .
        "  font-family: var(--font-heading), monospace !important;\n" .
        "  letter-spacing: 0.05em !important;\n" .
        "  text-transform: uppercase !important;\n" .
        "  transition: opacity 0.2s !important;\n" .
        "}\n" .
        ".logo-text-link:hover {\n" .
        "  opacity: 0.85 !important;\n" .
        "}\n";
}

if (!str_contains($css, '/* ── Customizações Dinâmicas do Menu Circular ── */')) {
    $css .= "\n\n/* ── Customizações Dinâmicas do Menu Circular ── */\n" .
        ".menu-hub-node {\n" .
        "  border-color: var(--hub-color, var(--color-accent, #06B6D4)) !important;\n" .
        "  box-shadow: 0 0 0 8px rgba(var(--hub-color-rgb, var(--color-accent-rgb, 6, 182, 212)), 0.08),\n" .
        "              0 0 0 20px rgba(var(--hub-color-rgb, var(--color-accent-rgb, 6, 182, 212)), 0.04),\n" .
        "              0 0 50px rgba(var(--hub-color-rgb, var(--color-accent-rgb, 6, 182, 212)), 0.4),\n" .
        "              inset 0 0 30px rgba(var(--hub-color-rgb, var(--color-accent-rgb, 6, 182, 212)), 0.15) !important;\n" .
        "}\n" .
        ".menu-hub-node:hover {\n" .
        "  box-shadow: 0 0 0 10px rgba(var(--hub-color-rgb, var(--color-accent-rgb, 6, 182, 212)), 0.12),\n" .
        "              0 0 0 24px rgba(var(--hub-color-rgb, var(--color-accent-rgb, 6, 182, 212)), 0.06),\n" .
        "              0 0 70px rgba(var(--hub-color-rgb, var(--color-accent-rgb, 6, 182, 212)), 0.6),\n" .
        "              inset 0 0 40px rgba(var(--hub-color-rgb, var(--color-accent-rgb, 6, 182, 212)), 0.25) !important;\n" .
        "}\n" .
        ".hub-glow {\n" .
        "  border-color: rgba(var(--hub-color-rgb, var(--color-accent-rgb, 6, 182, 212)), 0.3) !important;\n" .
        "}\n" .
        ".hub-glow::after {\n" .
        "  border-color: rgba(var(--hub-color-rgb, var(--color-accent-rgb, 6, 182, 212)), 0.1) !important;\n" .
        "}\n" .
        ".radar-circles {\n" .
        "  border-color: rgba(var(--hub-color-rgb, var(--color-accent-rgb, 6, 182, 212)), 0.18) !important;\n" .
        "}\n" .
        ".radar-circles::after {\n" .
        "  background: conic-gradient(from 0deg, rgba(var(--hub-color-rgb, var(--color-accent-rgb, 6, 182, 212)), 0.18) 0%, transparent 50%) !important;\n" .
        "}\n" .
        ".radar-circles::before {\n" .
        "  border-color: rgba(var(--hub-color-rgb, var(--color-accent-rgb, 6, 182, 212)), 0.05) !important;\n" .
        "}\n" .
        ".radial-grid {\n" .
        "  border-color: rgba(var(--hub-color-rgb, var(--color-accent-rgb, 6, 182, 212)), 0.06) !important;\n" .
        "}\n" .
        ".sat-node {\n" .
        "  background: var(--sat-bg, rgba(15, 23, 42, 0.85)) !important;\n" .
        "}\n" .
        ".sat-icon {\n" .
        "  background: rgba(var(--sat-color-rgb, var(--color-accent-rgb, 6, 182, 212)), 0.1) !important;\n" .
        "  border-color: rgba(var(--sat-color-rgb, var(--color-accent-rgb, 6, 182, 212)), 0.2) !important;\n" .
        "}\n" .
        ".sat-title {\n" .
        "  color: var(--sat-text-color, var(--color-primary-foreground, #FFFFFF)) !important;\n" .
        "  font-size: var(--sat-font-size, 0.8rem) !important;\n" .
        "  font-weight: var(--sat-font-weight, 700) !important;\n" .
        "}\n" .
        ".sat-desc {\n" .
        "  color: var(--sat-desc-color, var(--color-muted-foreground, #94A3B8)) !important;\n" .
        "}\n" .
        ".sat-node-container:hover .sat-node {\n" .
        "  border-color: var(--sat-color, var(--color-accent, #06B6D4)) !important;\n" .
        "  box-shadow: 0 15px 30px rgba(var(--sat-color-rgb, var(--color-accent-rgb, 6, 182, 212)), 0.25) !important;\n" .
        "}\n" .
        ".sat-node-container:hover .sat-icon {\n" .
        "  background: var(--sat-color, var(--color-accent, #06B6D4)) !important;\n" .
        "}\n";
}

if (!str_contains($css, '/* ── Galeria 3D Premium Carousel ── */')) {
    $css .= "\n\n/* ── Galeria 3D Premium Carousel ── */\n" .
        ".gallery-3d-viewport {\n" .
        "  perspective: 1000px;\n" .
        "  width: 100%;\n" .
        "  height: 380px;\n" .
        "  position: relative;\n" .
        "  overflow: hidden;\n" .
        "  display: flex;\n" .
        "  align-items: center;\n" .
        "  justify-content: center;\n" .
        "}\n" .
        ".gallery-3d-scene {\n" .
        "  width: 280px;\n" .
        "  height: 180px;\n" .
        "  position: relative;\n" .
        "  transform-style: preserve-3d;\n" .
        "  transition: transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);\n" .
        "}\n" .
        ".gallery-3d-item {\n" .
        "  position: absolute;\n" .
        "  width: 100%;\n" .
        "  height: 100%;\n" .
        "  left: 0;\n" .
        "  top: 0;\n" .
        "  border-radius: 12px;\n" .
        "  overflow: hidden;\n" .
        "  border: 1px solid rgba(255,255,255,0.08);\n" .
        "  background: #070c18;\n" .
        "  box-shadow: 0 15px 35px rgba(0,0,0,0.5);\n" .
        "  backface-visibility: hidden;\n" .
        "  transition: opacity 0.5s, filter 0.5s, transform 0.8s;\n" .
        "  cursor: pointer;\n" .
        "}\n" .
        ".gallery-3d-item img {\n" .
        "  width: 100%;\n" .
        "  height: 100%;\n" .
        "  object-fit: cover;\n" .
        "  opacity: 0.75;\n" .
        "  transition: opacity 0.3s;\n" .
        "}\n" .
        ".gallery-3d-item:hover img {\n" .
        "  opacity: 1;\n" .
        "}\n" .
        ".gallery-3d-item .item-caption {\n" .
        "  position: absolute;\n" .
        "  bottom: 0;\n" .
        "  left: 0;\n" .
        "  right: 0;\n" .
        "  background: linear-gradient(to top, rgba(3,7,18,0.9) 0%, rgba(3,7,18,0) 100%);\n" .
        "  padding: 12px;\n" .
        "  color: #fff;\n" .
        "  text-align: left;\n" .
        "}\n" .
        ".gallery-3d-nav {\n" .
        "  position: absolute;\n" .
        "  top: 50%;\n" .
        "  width: 100%;\n" .
        "  display: flex;\n" .
        "  justify-content: space-between;\n" .
        "  transform: translateY(-50%);\n" .
        "  padding: 0 15px;\n" .
        "  pointer-events: none;\n" .
        "  z-index: 10;\n" .
        "}\n" .
        ".gallery-3d-btn {\n" .
        "  width: 40px;\n" .
        "  height: 40px;\n" .
        "  border-radius: 50%;\n" .
        "  background: rgba(7, 12, 24, 0.75);\n" .
        "  border: 1px solid rgba(255,255,255,0.08);\n" .
        "  color: #fff;\n" .
        "  display: flex;\n" .
        "  align-items: center;\n" .
        "  justify-content: center;\n" .
        "  cursor: pointer;\n" .
        "  pointer-events: auto;\n" .
        "  transition: all 0.3s;\n" .
        "  backdrop-filter: blur(4px);\n" .
        "}\n" .
        ".gallery-3d-btn:hover {\n" .
        "  background: var(--color-accent, #06b6d4);\n" .
        "  border-color: var(--color-accent, #06b6d4);\n" .
        "  box-shadow: 0 0 12px rgba(var(--color-accent-rgb, 6, 182, 212), 0.4);\n" .
        "}\n" .
        ".gallery-3d-dots {\n" .
        "  display: flex;\n" .
        "  justify-content: center;\n" .
        "  gap: 8px;\n" .
        "  margin-top: 15px;\n" .
        "}\n" .
        ".gallery-3d-dot {\n" .
        "  width: 7px;\n" .
        "  height: 7px;\n" .
        "  border-radius: 50%;\n" .
        "  background: rgba(255,255,255,0.2);\n" .
        "  cursor: pointer;\n" .
        "  transition: all 0.3s;\n" .
        "}\n" .
        ".gallery-3d-dot.active {\n" .
        "  background: var(--color-accent, #06b6d4);\n" .
        "  width: 20px;\n" .
        "  border-radius: 3px;\n" .
        "}\n" .
        "/* Masonry Gallery Layout */\n" .
        ".gallery-masonry {\n" .
        "  column-count: 3;\n" .
        "  column-gap: 16px;\n" .
        "}\n" .
        "@media (max-width: 768px) {\n" .
        "  .gallery-masonry { column-count: 2; }\n" .
        "}\n" .
        "@media (max-width: 480px) {\n" .
        "  .gallery-masonry { column-count: 1; }\n" .
        "}\n" .
        ".gallery-masonry-item {\n" .
        "  display: inline-block;\n" .
        "  width: 100%;\n" .
        "  margin-bottom: 16px;\n" .
        "  border-radius: 12px;\n" .
        "  overflow: hidden;\n" .
        "  border: 1px solid rgba(255,255,255,0.08);\n" .
        "  background: #070c18;\n" .
        "  transition: all 0.3s;\n" .
        "}\n" .
        ".gallery-masonry-item:hover {\n" .
        "  border-color: rgba(var(--color-accent-rgb, 6, 182, 212), 0.3);\n" .
        "  transform: translateY(-4px);\n" .
        "}\n";
}

$theme->custom_css = $css;


// Sincronizar JS
$js = $theme->custom_js;
$oldBlock = "  const portalNavbar = document.querySelector('.normal-navbar.portal-active');\n" .
"  if (portalNavbar) {\n" .
"    // Trigger portal entry body animation (CSS handles the rest)\n" .
"    requestAnimationFrame(() => {\n" .
"      document.body.classList.add('portal-entry');\n" .
"    });\n" .
"  }";

$newBlock = "  const portalNavbar = document.querySelector('.normal-navbar.portal-active');\n" .
"  if (portalNavbar) {\n" .
"    // Trigger portal entry body animation (CSS handles the rest)\n" .
"    requestAnimationFrame(() => {\n" .
"      document.body.classList.add('portal-entry');\n" .
"      setTimeout(() => {\n" .
"        document.body.classList.remove('portal-entry');\n" .
"      }, 600);\n" .
"    });\n" .
"  }";

if (str_contains($js, $oldBlock)) {
    $js = str_replace($oldBlock, $newBlock, $js);
}

// Tornar o handler de contacto original dinâmico com base nos data-attributes
$js = str_replace(
    "data.message || '✅ Missão submetida com sucesso! Entraremos em contacto brevemente.'",
    "data.message || form.getAttribute('data-success') || '✅ Missão submetida com sucesso!'",
    $js
);
$js = str_replace(
    "window.location.href = `mailto:ops@aerospace.io?subject=\${subject}&body=\${body}`;",
    "const destEmail = form.getAttribute('data-mailto') || 'ops@aerospace.io'; window.location.href = `mailto:\${destEmail}?subject=\${subject}&body=\${body}`;",
    $js
);
$js = str_replace(
    "submitBtn.textContent = 'Submeter Plano de Missão';",
    "submitBtn.textContent = form.querySelector('button[type=\"submit\"]')?.textContent || 'Submeter Plano de Missão';",
    $js
);

// Tornar o handler de contacto original dinâmico com base nos data-attributes
$js = str_replace(
    "data.message || '✅ Missão submetida com sucesso! Entraremos em contacto brevemente.'",
    "data.message || form.getAttribute('data-success') || '✅ Missão submetida com sucesso!'",
    $js
);
$js = str_replace(
    "window.location.href = `mailto:ops@aerospace.io?subject=\${subject}&body=\${body}`;",
    "const destEmail = form.getAttribute('data-mailto') || 'ops@aerospace.io'; window.location.href = `mailto:\${destEmail}?subject=\${subject}&body=\${body}`;",
    $js
);
$js = str_replace(
    "submitBtn.textContent = 'Submeter Plano de Missão';",
    "submitBtn.textContent = form.querySelector('button[type=\"submit\"]')?.textContent || 'Submeter Plano de Missão';",
    $js
);

// Limpar os handlers antigos do JS para forçar a re-injeção das versões melhoradas/dinâmicas
$js = preg_replace('/\/\* ── Contact Detail Form Handler ── \*\/.*?\};\s*\n/s', '', $js);
$js = preg_replace('/\/\* ── Newsletter Form Handler ── \*\/.*?\};\s*\n/s', '', $js);
$js = preg_replace('/\/\* ── Support Form Handler ── \*\/.*?\};\s*\n/s', '', $js);

if (!str_contains($js, '/* ── Sticky Header on Scroll ── */')) {
    $js .= "\n\n/* ── Sticky Header on Scroll ── */\n" .
        "window.addEventListener('scroll', function() {\n" .
        "  const navbar = document.querySelector('.normal-navbar.is-sticky');\n" .
        "  if (navbar) {\n" .
        "    if (window.scrollY > 50) {\n" .
        "      navbar.classList.add('scrolled');\n" .
        "    } else {\n" .
        "      navbar.classList.remove('scrolled');\n" .
        "    }\n" .
        "  }\n" .
        "});\n";
}

// ── Contact Detail Form Handler ──────────────────────────────────────────────
if (!str_contains($js, '/* ── Contact Detail Form Handler ── */')) {
    $js .= "\n\n/* ── Contact Detail Form Handler ── */\n" .
        "window.handleAerospaceDetailFormSubmit = function(e) {\n" .
        "  e.preventDefault();\n" .
        "  const form = document.getElementById('aerospace-contact-detail-form');\n" .
        "  const feedback = document.getElementById('detail-form-feedback');\n" .
        "  const submitBtn = document.getElementById('detail-submit-btn');\n" .
        "  if (!form || !feedback || !submitBtn) return;\n\n" .
        "  const nome = form.querySelector('#detail-name')?.value.trim();\n" .
        "  const email = form.querySelector('#detail-email')?.value.trim();\n" .
        "  const mensagem = form.querySelector('#detail-message')?.value.trim();\n" .
        "  const servico = form.querySelector('#detail-service')?.value || '';\n\n" .
        "  const showFb = (msg, type) => {\n" .
        "    if (!feedback) return;\n" .
        "    feedback.textContent = msg;\n" .
        "    feedback.className = 'text-xs py-2 rounded-lg px-3 ' +\n" .
        "      (type === 'error' ? 'bg-red-500/10 text-red-400' :\n" .
        "       type === 'success' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-cyan-500/10 text-cyan-400');\n" .
        "    feedback.classList.remove('hidden');\n" .
        "    if (type !== 'error') setTimeout(() => feedback.classList.add('hidden'), 7000);\n" .
        "  };\n\n" .
        "  if (!nome || nome.length < 2) { showFb('Por favor, introduza o nome da empresa ou entidade.', 'error'); return; }\n" .
        "  const emailRe = /^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/;\n" .
        "  if (!email || !emailRe.test(email)) { showFb('Por favor, introduza um e-mail válido.', 'error'); return; }\n" .
        "  if (!mensagem || mensagem.length < 10) { showFb('Descreva a sua necessidade com pelo menos 10 caracteres.', 'error'); return; }\n\n" .
        "  submitBtn.disabled = true;\n" .
        "  const originalText = submitBtn.textContent;\n" .
        "  submitBtn.textContent = 'A enviar...';\n\n" .
        "  const csrfToken = form.querySelector('input[name=\"_token\"]')?.value || '';\n" .
        "  const successMsg = form.getAttribute('data-success') || '✅ Mensagem enviada com sucesso!';\n" .
        "  const destEmail = form.getAttribute('data-mailto') || 'ops@aerospace.io';\n\n" .
        "  fetch('/contacto/enviar', {\n" .
        "    method: 'POST',\n" .
        "    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },\n" .
        "    body: JSON.stringify({ nome, email, mensagem, servico })\n" .
        "  })\n" .
        "  .then(r => { if (!r.ok) throw new Error('Network error'); return r.json(); })\n" .
        "  .then(data => {\n" .
        "    showFb(data.message || successMsg, 'success');\n" .
        "    form.reset();\n" .
        "    submitBtn.disabled = false;\n" .
        "    submitBtn.textContent = originalText;\n" .
        "    try { playClickChirp(); } catch(_) {}\n" .
        "  })\n" .
        "  .catch(() => {\n" .
        "    const subject = encodeURIComponent('Pedido de Informação — ' + nome);\n" .
        "    const body = encodeURIComponent('Nome: ' + nome + '\\nE-mail: ' + email + '\\nServiço: ' + servico + '\\n\\nMensagem:\\n' + mensagem);\n" .
        "    window.location.href = 'mailto:' + destEmail + '?subject=' + subject + '&body=' + body;\n" .
        "    showFb('📨 O seu e-mail padrão foi aberto. Aguardamos a sua mensagem!', 'info');\n" .
        "    submitBtn.disabled = false;\n" .
        "    submitBtn.textContent = originalText;\n" .
        "  });\n" .
        "};\n";
}

// ── Newsletter Form Handler ──────────────────────────────────────────────────
if (!str_contains($js, '/* ── Newsletter Form Handler ── */')) {
    $js .= "\n\n/* ── Newsletter Form Handler ── */\n" .
        "window.handleNewsletterSubmit = function(e) {\n" .
        "  e.preventDefault();\n" .
        "  const form = document.getElementById('aerospace-newsletter-form');\n" .
        "  const emailInput = document.getElementById('newsletter-email');\n" .
        "  const feedback = document.getElementById('newsletter-feedback');\n" .
        "  const btn = document.getElementById('newsletter-submit-btn');\n" .
        "  if (!form || !emailInput || !feedback || !btn) return;\n\n" .
        "  const email = emailInput.value.trim();\n" .
        "  const emailRe = /^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/;\n" .
        "  if (!email || !emailRe.test(email)) {\n" .
        "    feedback.textContent = 'Por favor, introduza um e-mail válido.';\n" .
        "    feedback.className = 'text-xs mt-3 py-1 text-red-400';\n" .
        "    feedback.classList.remove('hidden');\n" .
        "    return;\n" .
        "  }\n\n" .
        "  btn.disabled = true;\n" .
        "  const originalText = btn.textContent;\n" .
        "  btn.textContent = 'A subscrever...';\n\n" .
        "  const csrfToken = form.querySelector('input[name=\"_token\"]')?.value || '';\n" .
        "  const successMsg = form.getAttribute('data-success') || '📡 Subscrição efectuada com sucesso!';\n\n" .
        "  fetch('/newsletter/subscrever', {\n" .
        "    method: 'POST',\n" .
        "    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },\n" .
        "    body: JSON.stringify({ email })\n" .
        "  })\n" .
        "  .then(r => { if (!r.ok) throw new Error('Network error'); return r.json(); })\n" .
        "  .then(data => {\n" .
        "    feedback.textContent = data.message || successMsg;\n" .
        "    feedback.className = 'text-xs mt-3 py-1 text-emerald-400';\n" .
        "    feedback.classList.remove('hidden');\n" .
        "    form.reset();\n" .
        "    btn.disabled = false;\n" .
        "    btn.textContent = originalText;\n" .
        "    try { playClickChirp(); } catch(_) {}\n" .
        "  })\n" .
        "  .catch(() => {\n" .
        "    feedback.textContent = '📨 Subscrição registada. Entraremos em contacto brevemente.';\n" .
        "    feedback.className = 'text-xs mt-3 py-1 text-cyan-400';\n" .
        "    feedback.classList.remove('hidden');\n" .
        "    btn.disabled = false;\n" .
        "    btn.textContent = originalText;\n" .
        "  });\n" .
        "};\n";
}

// ── Support Form Handler (Chat Popup modo form) ──────────────────────────────
if (!str_contains($js, '/* ── Support Form Handler ── */')) {
    // Replace the old minified one-liner if present
    $oldSupportHandler = "function handleSupportFormSubmit(e){ if(e) e.preventDefault(); try{ if(typeof playClickChirp==='function') playClickChirp(); }catch(_){ } var f=e&&e.target; var body=f?f.closest('.chat-popup-body'):null; if(body){ body.innerHTML='<div class=\\\"msg system\\\" style=\\\"text-align:center;padding:24px 12px;\\\">✓ Mensagem enviada. A nossa equipa entrará em contacto.</div>'; } return false; }";
    if (str_contains($js, $oldSupportHandler)) {
        $js = str_replace($oldSupportHandler, '', $js);
    }

    $js .= "\n\n/* ── Support Form Handler ── */\n" .
        "window.handleSupportFormSubmit = function(e) {\n" .
        "  if (e) e.preventDefault();\n" .
        "  const form = e && e.target;\n" .
        "  if (!form) return false;\n\n" .
        "  const nome = form.querySelector('input[name=\"nome\"]')?.value.trim();\n" .
        "  const email = form.querySelector('input[name=\"email\"]')?.value.trim();\n" .
        "  const mensagem = form.querySelector('textarea[name=\"mensagem\"]')?.value.trim();\n\n" .
        "  if (!nome || !email || !mensagem) {\n" .
        "    const errDiv = form.querySelector('.chat-support-error') || document.createElement('div');\n" .
        "    errDiv.className = 'chat-support-error msg system';\n" .
        "    errDiv.style.color = '#f87171';\n" .
        "    errDiv.style.fontSize = '0.75rem';\n" .
        "    errDiv.textContent = '⚠️ Por favor, preencha todos os campos.';\n" .
        "    if (!form.querySelector('.chat-support-error')) form.appendChild(errDiv);\n" .
        "    return false;\n" .
        "  }\n\n" .
        "  const body = form.closest('.chat-popup-body');\n" .
        "  const csrfToken = form.querySelector('input[name=\"_token\"]')?.value || '';\n" .
        "  const successMsg = form.getAttribute('data-success') || 'Mensagem enviada! A nossa equipa entrará em contacto em breve.';\n\n" .
        "  if (body) {\n" .
        "    body.innerHTML = '<div class=\"msg system\" style=\"text-align:center;padding:32px 16px;\"><div style=\"font-size:2rem;margin-bottom:8px;\">✅</div><div style=\"color:#34d399;font-weight:600;margin-bottom:4px;\">' + successMsg + '</div></div>';\n" .
        "  }\n\n" .
        "  fetch('/contacto/enviar', {\n" .
        "    method: 'POST',\n" .
        "    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },\n" .
        "    body: JSON.stringify({ nome, email, mensagem })\n" .
        "  }).catch(() => {});\n\n" .
        "  try { playClickChirp(); } catch(_) {}\n" .
        "  return false;\n" .
        "};\n";
}

// ── 3D Gallery Carousel Slider ───────────────────────────────────────────────
if (!str_contains($js, '/* ── 3D Gallery Carousel Slider ── */')) {
    $js .= "\n\n/* ── 3D Gallery Carousel Slider ── */\n" .
        "window.initAerospace3DGallery = function() {\n" .
        "  const scene = document.querySelector('.gallery-3d-scene');\n" .
        "  const items = document.querySelectorAll('.gallery-3d-item');\n" .
        "  const dotsContainer = document.querySelector('.gallery-3d-dots');\n" .
        "  if (!scene || items.length === 0) return;\n\n" .
        "  let currentIndex = 0;\n" .
        "  const count = items.length;\n" .
        "  const itemWidth = 280; // Matches CSS width\n" .
        "  const angleStep = 360 / count;\n" .
        "  const radius = Math.round((itemWidth / 2) / Math.tan(Math.PI / count));\n\n" .
        "  if (dotsContainer) {\n" .
        "    dotsContainer.innerHTML = '';\n" .
        "    for (let i = 0; i < count; i++) {\n" .
        "      const dot = document.createElement('span');\n" .
        "      dot.className = 'gallery-3d-dot' + (i === 0 ? ' active' : '');\n" .
        "      dot.addEventListener('click', () => {\n" .
        "        currentIndex = i;\n" .
        "        updateCarousel();\n" .
        "        resetAutoRotate();\n" .
        "      });\n" .
        "      dotsContainer.appendChild(dot);\n" .
        "    }\n" .
        "  }\n\n" .
        "  function updateCarousel() {\n" .
        "    scene.style.transform = 'translateZ(-' + radius + 'px) rotateY(' + (-currentIndex * angleStep) + 'deg)';\n\n" .
        "    const dots = document.querySelectorAll('.gallery-3d-dot');\n" .
        "    dots.forEach((dot, idx) => {\n" .
        "      if (idx === currentIndex) dot.classList.add('active');\n" .
        "      else dot.classList.remove('active');\n" .
        "    });\n\n" .
        "    items.forEach((item, idx) => {\n" .
        "      const angle = idx * angleStep;\n" .
        "      item.style.transform = 'rotateY(' + angle + 'deg) translateZ(' + radius + 'px)';\n\n" .
        "      let diff = Math.abs(idx - currentIndex);\n" .
        "      if (diff > count / 2) diff = count - diff;\n\n" .
        "      if (idx === currentIndex) {\n" .
        "        item.style.opacity = '1';\n" .
        "        item.style.filter = 'none';\n" .
        "        item.style.zIndex = '2';\n" .
        "      } else {\n" .
        "        item.style.opacity = diff > 1 ? '0.3' : '0.6';\n" .
        "        item.style.filter = 'blur(1px) grayscale(45%)';\n" .
        "        item.style.zIndex = '1';\n" .
        "      }\n" .
        "    });\n" .
        "  }\n\n" .
        "  updateCarousel();\n\n" .
        "  window.rotate3DGallery = function(direction) {\n" .
        "    if (direction === 'next') currentIndex = (currentIndex + 1) % count;\n" .
        "    else currentIndex = (currentIndex - 1 + count) % count;\n" .
        "    updateCarousel();\n" .
        "    try { playClickChirp(); } catch(_) {}\n" .
        "    resetAutoRotate();\n" .
        "  };\n\n" .
        "  const autoRotateEnabled = scene.getAttribute('data-auto-rotate') === 'true';\n" .
        "  let rotateInterval;\n\n" .
        "  function startAutoRotate() {\n" .
        "    if (autoRotateEnabled) {\n" .
        "      rotateInterval = setInterval(() => {\n" .
        "        currentIndex = (currentIndex + 1) % count;\n" .
        "        updateCarousel();\n" .
        "      }, 5000);\n" .
        "    }\n" .
        "  }\n\n" .
        "  function resetAutoRotate() {\n" .
        "    if (rotateInterval) {\n" .
        "      clearInterval(rotateInterval);\n" .
        "      startAutoRotate();\n" .
        "    }\n" .
        "  }\n\n" .
        "  startAutoRotate();\n\n" .
        "  const tiltEnabled = scene.getAttribute('data-tilt') === 'true';\n" .
        "  if (tiltEnabled) {\n" .
        "    items.forEach(item => {\n" .
        "      item.addEventListener('mousemove', (e) => {\n" .
        "        const itemIndex = parseInt(item.getAttribute('data-index') || '0');\n" .
        "        if (itemIndex !== currentIndex) return;\n" .
        "        const rect = item.getBoundingClientRect();\n" .
        "        const x = e.clientX - rect.left;\n" .
        "        const y = e.clientY - rect.top;\n" .
        "        const xc = rect.width / 2;\n" .
        "        const yc = rect.height / 2;\n" .
        "        const angleX = (yc - y) / 10;\n" .
        "        const angleY = (x - xc) / 10;\n" .
        "        const baseAngle = itemIndex * angleStep;\n" .
        "        item.style.transform = 'rotateY(' + baseAngle + 'deg) translateZ(' + radius + 'px) rotateX(' + angleX + 'deg) rotateY(' + angleY + 'deg)';\n" .
        "      });\n" .
        "      item.addEventListener('mouseleave', () => {\n" .
        "        const itemIndex = parseInt(item.getAttribute('data-index') || '0');\n" .
        "        const baseAngle = itemIndex * angleStep;\n" .
        "        item.style.transform = 'rotateY(' + baseAngle + 'deg) translateZ(' + radius + 'px)';\n" .
        "      });\n" .
        "    });\n" .
        "  }\n" .
        "};\n\n" .
        "if (document.readyState === 'loading') {\n" .
        "  document.addEventListener('DOMContentLoaded', () => window.initAerospace3DGallery());\n" .
        "} else {\n" .
        "  window.initAerospace3DGallery();\n" .
        "}\n";
}

$theme->custom_js = $js;
$theme->save();

echo "✅ Theme sections and custom CSS/JS updated successfully in database.\n";
exit(0);

