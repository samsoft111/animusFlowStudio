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
  $btnText = $c['button_text'] ?? 'Submeter Plano de Missão';
@endphp
<section id="contacto" class="py-20 bg-gradient-to-br from-[#0F172A] to-[#070C18] border-t border-white/5 relative">
  <div class="max-w-4xl mx-auto px-6 text-center">
    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 text-xs font-semibold uppercase tracking-wider mb-6">
      ⚡ Pronto para Operar
    </div>
    <h2 class="text-3xl md:text-4xl font-bold text-white mb-6 font-heading">{{ $heading }}</h2>
    <p class="text-slate-400 max-w-xl mx-auto mb-10">{{ $text }}</p>
    <form id="aerospace-contact-form" class="max-w-md mx-auto space-y-4" novalidate onsubmit="handleAerospaceContactSubmit(event)">
      @csrf
      <div class="text-left">
        <label for="contact-name" class="sr-only">Nome da Empresa ou Entidade</label>
        <input type="text" id="contact-name" name="nome" placeholder="Nome da Empresa / Entidade" class="contact-input" required autocomplete="organization">
      </div>
      <div class="text-left">
        <label for="contact-email" class="sr-only">E-mail de Contacto</label>
        <input type="email" id="contact-email" name="email" placeholder="E-mail de Contacto" class="contact-input" required autocomplete="email">
      </div>
      <div class="text-left">
        <label for="contact-message" class="sr-only">Descrição da Missão</label>
        <textarea id="contact-message" name="mensagem" placeholder="Descreva a sua missão (ex: rota de transporte de 100km)" rows="3" class="contact-input" required></textarea>
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
        <h3 class="text-xl font-bold text-white mb-2 font-heading">Subscrever Boletim Operacional</h3>
        <p class="text-slate-400 text-xs mb-6 max-w-md mx-auto">Receba novidades sobre espaço aéreo, legislação de drones e atualizações de investigação tecnológica.</p>
        <form class="flex flex-col sm:flex-row gap-2 max-w-md mx-auto" onsubmit="event.preventDefault(); alert('Subscrição efectuada com sucesso.');">
          <input type="email" placeholder="Introduza o seu email..." class="contact-input sm:flex-1" required>
          <button type="submit" class="contact-btn sm:w-auto px-6">Subscrever</button>
        </form>
      </div>
    @endif
  </div>
</section>
HTML;

// ─── MAP ─────────────────────────────────────────────────────────────────────
$sections['map'] = <<<'HTML'
@php
  $c = $content ?? [];
  $heading = $c['heading'] ?? 'Sede — Luanda, Angola';
  $embedUrl = $c['embed_url'] ?? 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15760.84157778939!2d13.23005872895697!3d-8.813958742512686!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x1a51f3c83786adbf%3A0x6b772c676bb7db4b!2sLuanda!5e0!3m2!1spt-PT!2sao!4v1700000000000!5m2!1spt-PT!2sao';
  $address = $c['address'] ?? 'Miramar, Luanda, Angola';
@endphp
<section class="py-16 bg-[#030712] relative">
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
        title="Localização AeroSpace — Luanda, Angola">
      </iframe>
      <div class="absolute bottom-4 left-4 bg-[#030712]/90 backdrop-blur border border-[#06B6D4]/20 rounded-xl px-4 py-3">
        <div class="text-white font-semibold text-sm font-heading">AeroSpace HQ</div>
        <div class="text-slate-400 text-xs mt-0.5">{{ $address }}</div>
        <div class="text-[#06B6D4] text-[10px] font-mono mt-1">LAT -8.8124 · LON 13.2306</div>
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
          <span class="terminal-title">AEROSPACE COCKPIT INIT v1.0.0</span>
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
        <div class="hud-telemetry-left">ALT: 124m<br>SPD: 42km/h<br>BAT: 88%<br>ALT-HOLD: ON</div>
        <div class="hud-telemetry-right">LAT: 08°50'S<br>LNG: 13°14'E<br>GPS: LOCK<br>NAV-LOCK: OK</div>
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
    <header class="normal-navbar pos-{{ $effectiveMenuPosition }} @if(!$showNormalMenu) hidden-navbar @endif @if(!$isHome && $menuLayoutCfg === 'circular') portal-active @endif">
      <button class="hamburger-menu-btn" onclick="toggleMobileMenu(event)" aria-label="Abrir menu de navegação" aria-expanded="false">
        <span></span>
        <span></span>
        <span></span>
      </button>
      <div class="logo-area">AEROSPACE</div>
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
    <div class="circular-menu-wrapper @if(!$showCircularMenu) hidden-menu @endif">
      <div class="radar-circles"></div>
      <div class="radial-grid"></div>

      <!-- Central Hub (Home) -->
      <a href="/" class="menu-hub-node" onmouseenter="playHoverChirp()" onclick="playClickChirp()">
        <span class="node-title">HOME</span>
        <span class="node-desc">Central Hub</span>
        <div class="hub-glow"></div>
      </a>

      <!-- Satellite Orbiting Nodes -->
      <div class="satellite-orbits">

        <!-- Node 1: Serviços (Top) -->
        <div class="sat-node-container sat-1 group/sat">
          <a href="/servicos" class="sat-node portal-link" onmouseenter="playHoverChirp()" data-portal-href="/servicos">
            <div class="sat-icon">🛸</div>
            <div class="sat-text">
              <span class="sat-title">Serviços</span>
              <span class="sat-desc">Operações Aéreas</span>
            </div>
          </a>
        </div>

        <!-- Node 2: Galeria (Right) -->
        <div class="sat-node-container sat-2 group/sat">
          <a href="/galeria" class="sat-node portal-link" onmouseenter="playHoverChirp()" data-portal-href="/galeria">
            <div class="sat-icon">🖼️</div>
            <div class="sat-text">
              <span class="sat-title">Galeria</span>
              <span class="sat-desc">Missões & Media</span>
            </div>
          </a>
        </div>

        <!-- Node 3: Contactos (Bottom) -->
        <div class="sat-node-container sat-3 group/sat">
          <a href="/contactos" class="sat-node portal-link" onmouseenter="playHoverChirp()" data-portal-href="/contactos">
            <div class="sat-icon">📡</div>
            <div class="sat-text">
              <span class="sat-title">Contactos</span>
              <span class="sat-desc">Centro de Controlo</span>
            </div>
          </a>
        </div>

        <!-- Node 4: Sobre (Left) -->
        <div class="sat-node-container sat-4 group/sat">
          <a href="/sobre" class="sat-node portal-link" onmouseenter="playHoverChirp()" data-portal-href="/sobre">
            <div class="sat-icon">🌐</div>
            <div class="sat-text">
              <span class="sat-title">Sobre</span>
              <span class="sat-desc">Missão & Equipa</span>
            </div>
          </a>
        </div>

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
          <span>SAT-LINK STATUS:</span> <span class="text-[#06b6d4]">CONNECTED (100%)</span>
        </div>
        <div class="flex justify-between border-b border-white/5 pb-1">
          <span>DRONES OPERACIONAIS:</span> <span class="text-emerald-400">07 ONLINE</span>
        </div>
        <div class="flex justify-between border-b border-white/5 pb-1">
          <span>VELOCIDADE DO VENTO:</span> <span id="telemetry-wind">14 km/h</span>
        </div>
        <div class="flex justify-between border-b border-white/5 pb-1">
          <span>ALTITUDE MÉDIA:</span> <span>120m</span>
        </div>
        <div class="flex justify-between">
          <span>SINAL GPS:</span> <span class="text-emerald-400">EXCELENTE</span>
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
@endphp
<section class="py-24 bg-gradient-to-b from-[#030712] to-[#070C18] relative overflow-hidden">
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
          <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-[#2563EB] to-[#06B6D4] flex items-center justify-center text-white font-bold text-xl mx-auto mb-4 group-hover:scale-105 transition-transform duration-300">{{ $initials }}</div>
          <div class="text-white font-semibold font-heading">{{ $item['name'] }}</div>
          <div class="text-[#06B6D4] text-xs font-mono uppercase tracking-wider mt-1 mb-3">{{ $item['role'] }}</div>
          <p class="text-slate-500 text-xs leading-relaxed">{{ $item['bio'] ?? $item['text'] ?? '' }}</p>
          <a href="#" class="inline-flex items-center gap-1 mt-4 text-[10px] text-slate-600 hover:text-[#06B6D4] transition-colors font-mono uppercase tracking-wider">LinkedIn ↗</a>
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
  $body = $c['body'] ?? 'A AeroSpace nasceu em Luanda em 2019, quando um grupo de engenheiros aeronáuticos e especialistas em inteligência artificial uniram forças com uma missão clara: levar a tecnologia aeroespacial ao serviço do desenvolvimento de Angola e de África.';
@endphp
<section class="py-20 bg-[#070C18] relative">
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
@endphp
<section id="sobre" class="py-24 bg-[#030712] relative overflow-hidden">
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
          <div class="space-y-5">
            <div>
              <div class="flex justify-between text-xs text-slate-400 mb-1"><span>Integridade da Frota</span><span class="text-[#06B6D4]">98%</span></div>
              <div class="h-1.5 rounded-full bg-white/5 overflow-hidden"><div class="h-full bg-gradient-to-r from-[#2563EB] to-[#06B6D4]" style="width:98%"></div></div>
            </div>
            <div>
              <div class="flex justify-between text-xs text-slate-400 mb-1"><span>Cobertura Orbital</span><span class="text-[#06B6D4]">91%</span></div>
              <div class="h-1.5 rounded-full bg-white/5 overflow-hidden"><div class="h-full bg-gradient-to-r from-[#2563EB] to-[#06B6D4]" style="width:91%"></div></div>
            </div>
            <div>
              <div class="flex justify-between text-xs text-slate-400 mb-1"><span>Eficiencia Energetica</span><span class="text-[#06B6D4]">87%</span></div>
              <div class="h-1.5 rounded-full bg-white/5 overflow-hidden"><div class="h-full bg-gradient-to-r from-[#2563EB] to-[#06B6D4]" style="width:87%"></div></div>
            </div>
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
@endphp
<section class="py-20 bg-gradient-to-b from-[#070C18] to-[#030712] border-y border-white/5 relative">
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
@endphp
<section class="py-24 bg-[#070C18] relative overflow-hidden">
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
@endphp
<section class="py-24 bg-[#070C18] relative overflow-hidden">
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
            <div class="text-slate-400 text-sm">ops@aerospace.ao · +244 923 456 789</div>
            <div class="text-[#06B6D4] text-xs font-mono mt-1">Disponível 24/7 for emergencies</div>
          </div>
        </div>
        <div class="bg-[#0A0F1E] border border-white/8 rounded-2xl p-6 flex items-start gap-4 hover:border-[#06B6D4]/25 transition-all duration-300">
          <div class="text-2xl flex-shrink-0 mt-1">💼</div>
          <div>
            <div class="text-white font-semibold font-heading mb-1">Propostas Comerciais</div>
            <div class="text-slate-400 text-sm">comercial@aerospace.ao · +244 923 456 780</div>
            <div class="text-[#06B6D4] text-xs font-mono mt-1">Seg–Sex · 08h00–18h00 (WAT)</div>
          </div>
        </div>
        <div class="bg-[#0A0F1E] border border-white/8 rounded-2xl p-6 flex items-start gap-4 hover:border-[#06B6D4]/25 transition-all duration-300">
          <div class="text-2xl flex-shrink-0 mt-1">📍</div>
          <div>
            <div class="text-white font-semibold font-heading mb-1">Sede — Luanda</div>
            <div class="text-slate-400 text-sm">Rua Rainha Ginga, Edifício AeroSpace Tower</div>
            <div class="text-slate-400 text-sm">Miramar, Luanda, Angola</div>
          </div>
        </div>
        <div class="bg-[#0A0F1E] border border-white/8 rounded-2xl p-6 flex items-start gap-4 hover:border-[#06B6D4]/25 transition-all duration-300">
          <div class="text-2xl flex-shrink-0 mt-1">✈️</div>
          <div>
            <div class="text-white font-semibold font-heading mb-1">Hangar & Testes</div>
            <div class="text-slate-400 text-sm">Aeroporto Internacional 4 de Fevereiro</div>
            <div class="text-slate-400 text-sm">Zona Técnica Norte — Hangar AX, Luanda</div>
          </div>
        </div>
      </div>

      <!-- Formulário -->
      <div class="bg-[#0A0F1E] border border-white/8 rounded-2xl p-8">
        <h3 class="text-white font-bold font-heading text-xl mb-2">Enviar Mensagem</h3>
        <p class="text-slate-500 text-sm mb-6">Descreva a sua necessidade e um especialista entrará em contacto.</p>
        <form class="space-y-4" onsubmit="event.preventDefault(); alert('{{ $c['success_message'] ?? 'Mensagem enviada com sucesso! A nossa equipa responderá em menos de 2 horas.' }}');">
          <div>
            <label class="block text-xs font-mono uppercase tracking-wider text-slate-400 mb-1.5">Nome / Empresa</label>
            <input type="text" placeholder="Ex: Grupo Logística Angola" required
                   class="w-full bg-[#070C18] border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-slate-600 focus:outline-none focus:border-[#06B6D4]/50 transition-colors">
          </div>
          <div>
            <label class="block text-xs font-mono uppercase tracking-wider text-slate-400 mb-1.5">E-mail</label>
            <input type="email" placeholder="missao@empresa.ao" required
                   class="w-full bg-[#070C18] border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-slate-600 focus:outline-none focus:border-[#06B6D4]/50 transition-colors">
          </div>
          <div>
            <label class="block text-xs font-mono uppercase tracking-wider text-slate-400 mb-1.5">Serviço de Interesse</label>
            <select class="w-full bg-[#070C18] border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-[#06B6D4]/50 transition-colors">
              <option>Transporte Autónomo de Carga</option>
              <option>Cartografia &amp; Fotogrametria</option>
              <option>Vigilância &amp; Patrulhamento</option>
              <option>Inspecção Industrial</option>
              <option>Logística de Emergência</option>
              <option>Agricultura de Precisão</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-mono uppercase tracking-wider text-slate-400 mb-1.5">Descrição da Missão</label>
            <textarea rows="4" placeholder="Descreva a sua necessidade operacional..." required
                      class="w-full bg-[#070C18] border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-slate-600 focus:outline-none focus:border-[#06B6D4]/50 transition-colors resize-none"></textarea>
          </div>
          <button type="submit"
                  class="w-full bg-gradient-to-r from-[#2563EB] to-[#06B6D4] text-white font-bold py-3 rounded-xl hover:opacity-90 transition-opacity font-heading tracking-wider text-sm">
            {{ strtoupper($btnText) }} ➔
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
@endphp
<section class="py-12 bg-[#030712] relative overflow-hidden">
  <div class="max-w-6xl mx-auto px-6">
    <h3 class="text-sm font-mono uppercase tracking-wider text-[#06B6D4] mb-6 flex items-center gap-3">
      <span class="w-8 h-px bg-[#06B6D4]/40"></span> {{ $heading }}
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      @foreach($images as $img)
        <div class="group relative overflow-hidden rounded-xl border border-white/8 aspect-video bg-[#070C18] hover:border-[#06B6D4]/30 transition-all duration-300">
          <img src="{{ $img['src'] }}" alt="{{ $img['alt'] ?? '' }}"
               class="w-full h-full object-cover opacity-80 group-hover:opacity-100 group-hover:scale-105 transition-all duration-500">
          <div class="absolute inset-0 bg-gradient-to-t from-[#030712]/90 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end p-4">
            <p class="text-white text-xs font-mono">{{ $img['caption'] ?? '' }}</p>
          </div>
        </div>
      @endforeach
    </div>
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
@endphp
<section id="servicos" class="py-24 bg-[#070C18] relative overflow-hidden">
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
    <div class="drone-blueprint-showcase mt-16 border border-white/10 rounded-2xl bg-slate-900/40 p-8 backdrop-blur-md relative overflow-hidden">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
        <div class="relative flex justify-center">
          <div class="blueprint-vector">
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
            <!-- Interactive Hotspot Dots -->
            <button class="hotspot-dot" style="top: 13%; left: 13%;" data-title="Propulsores Elétricos VTOL" data-desc="Quatro propulsores elétricos independentes de alta eficácia para descolagem e aterragem vertical autónoma em qualquer terreno.">
              <span class="ping-wave"></span>
            </button>
            <button class="hotspot-dot active-hotspot" style="top: 48%; left: 48%;" data-title="Processador de IA & LIDAR" data-desc="Núcleo de computação de bordo com sensor LIDAR de alta frequência para desvio dinâmico de obstáculos a 360º durante voos de baixa altitude.">
              <span class="ping-wave"></span>
            </button>
            <button class="hotspot-dot" style="top: 83%; left: 83%;" data-title="Módulo de Energia de Lítio-Enxofre" data-desc="Baterias aeroespaciais avançadas com densidade de carga triplicada face ao ião de lítio convencional, garantindo autonomias de até 150 km.">
              <span class="ping-wave"></span>
            </button>
          </div>
        </div>
        <div class="blueprint-info text-left">
          <div class="inline-block px-3 py-1 rounded bg-cyan-500/10 border border-cyan-500/20 text-[#06b6d4] text-[10px] uppercase font-bold tracking-widest mb-3">Inspecção de Hardware</div>
          <h3 class="text-2xl font-bold text-white mb-2 font-heading" id="blueprint-item-title">Processador de IA & LIDAR</h3>
          <p class="text-slate-400 text-sm leading-relaxed" id="blueprint-item-desc">Núcleo de computação de bordo com sensor LIDAR de alta frequência para desvio dinâmico de obstáculos a 360º durante voos de baixa altitude.</p>
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
@endphp
<section class="py-24 bg-[#030712] relative overflow-hidden">
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
          <div class="flex gap-1 mb-4 text-[#06B6D4]">★★★★★</div>
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
$theme->custom_css = $css;

$theme->save();

echo "✅ Theme sections updated successfully in database.\n";
exit(0);
