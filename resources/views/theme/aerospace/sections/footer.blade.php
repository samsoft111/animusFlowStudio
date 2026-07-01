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
      @if(!empty($footerLangBlock))
        @php
            $flbS = $footerLangBlock->settings ?? [];
            $flbC = $footerLangBlock->content  ?? [];
            $flbProps = array_merge((array)$flbC, (array)$flbS, ['layout' => 'inline']);
        @endphp
        <div data-vue-component="LanguageSwitcher"
             data-props="{{ json_encode(['content' => $flbC, 'settings' => $flbProps]) }}"
             style="display:flex;align-items:center"></div>
      @endif
      <p class="font-mono text-[11px] text-slate-600">LAT {{ $footerLat }} &middot; LON {{ $footerLon }} &middot; ALT {{ $footerAlt }}</p>
    </div>
  </div>
</footer>