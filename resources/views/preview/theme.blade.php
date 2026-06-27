<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview — {{ $theme->label }}</title>
    <link rel="icon" type="image/x-icon" href="{{ $theme->layout_config['favicon_image'] ?? '/favicon.ico' }}">

    @php
        $colors  = $theme->colors  ?? [];
        $fonts   = $theme->fonts   ?? [];
        $light   = $colors['light'] ?? [];
        $dark    = $colors['dark']  ?? [];
        $heading = $fonts['heading'] ?? '';
        $body    = $fonts['body']    ?? '';

        // Build Google Fonts URL
        $families = array_filter([$heading, $body]);
        $googleUrl = '';
        if ($families) {
            $googleUrl = 'https://fonts.googleapis.com/css2?'
                . implode('&', array_map(
                    fn($f) => 'family=' . rawurlencode($f) . ':wght@400;500;600;700',
                    $families
                )) . '&display=swap';
        }
        // Determine current page based on the request path
        $currentPath = request()->path();
        $currentPage = 'home';
        if (str_contains($currentPath, 'sobre')) {
            $currentPage = 'sobre';
        } elseif (str_contains($currentPath, 'servicos')) {
            $currentPage = 'servicos';
        } elseif (str_contains($currentPath, 'galeria')) {
            $currentPage = 'galeria';
        } elseif (str_contains($currentPath, 'contactos')) {
            $currentPage = 'contactos';
        }

        // Page section maps
        $pageSectionsMap = [
            'home'      => ['hero', 'stats', 'features', 'testimonials', 'cta'],
            'sobre'     => ['hero', 'text', 'stats', 'features', 'team', 'cta'],
            'servicos'  => ['hero', 'features', 'stats', 'steps', 'cta'],
            'galeria'   => ['hero', 'gallery', 'cta'],
            'contactos' => ['hero', 'features', 'contact', 'map']
        ];

        $allowedSections = $pageSectionsMap[$currentPage] ?? ['hero', 'stats', 'features', 'testimonials', 'cta'];

        // Sample data for each section
        $sampleData = [
            'hero' => [
                'heading'  => $theme->label . ' Theme',
                'subtext'  => 'A beautiful AnimusFlow theme built with AnimusFlowStudio.',
                'cta_text' => 'Get Started',
                'cta_url'  => '#',
            ],
            'features' => [
                'heading' => 'Key Features',
                'items'   => [
                    ['icon' => '⚡', 'title' => 'Lightning Fast', 'text' => 'Built for performance from the ground up.'],
                    ['icon' => '🎨', 'title' => 'Beautiful Design', 'text' => 'Pixel-perfect on every screen size.'],
                    ['icon' => '🤖', 'title' => 'AI Powered',      'text' => 'Intelligent content generation built in.'],
                ],
            ],
            'cta' => [
                'heading'  => 'Ready to get started?',
                'subtext'  => 'Join thousands of users building amazing websites.',
                'cta_text' => 'Start for free',
                'cta_url'  => '#',
            ],
            'testimonials' => [
                'heading' => 'What people say',
                'items'   => [
                    ['quote' => 'AnimusFlow changed how we build websites. Incredibly fast.', 'author' => 'Alex Kim', 'role' => 'CEO, Startup'],
                    ['quote' => 'The AI features are next-level. Saves us hours every week.',  'author' => 'Sara Lee',  'role' => 'Designer'],
                ],
            ],
            'pricing' => [
                'heading' => 'Simple pricing',
                'items'   => [
                    ['label' => 'Free',       'price' => '$0',  'features' => ['1 site', '10 pages', 'Community support']],
                    ['label' => 'Pro',        'price' => '$19', 'features' => ['10 sites', 'Unlimited pages', 'AI features', 'Priority support']],
                    ['label' => 'Agency',     'price' => '$49', 'features' => ['Unlimited sites', 'White-label', 'API access', 'Dedicated support']],
                ],
            ],
        ];

        // Carrega o conteúdo demo (ex: tema AeroSpace)
        $demoData = null;
        $demoPath = base_path('skills/themes/aerospace-demo-content.json');
        if (file_exists($demoPath)) {
            $demoData = json_decode(file_get_contents($demoPath), true);
        }

        $pageBlocksMap = [];
        if ($demoData && isset($demoData['pages'])) {
            foreach ($demoData['pages'] as $page) {
                if ($page['slug'] === $currentPage) {
                    foreach ($page['blocks'] ?? [] as $block) {
                        $pageBlocksMap[$block['type']] = $block;
                    }
                    break;
                }
            }
        }

        // Compilamos cada secção para que @if/@foreach/{{ }} sejam resolvidos no preview,
        // passando o conteúdo/settings específico de cada página
        $sections = collect($theme->sections ?? [])
            ->map(function ($html, $type) use ($theme, $pageBlocksMap, $sampleData) {
                if (!is_string($html) || trim($html) === '') {
                    return $html;
                }
                
                // Vai buscar o bloco da página ou fallback para sampleData
                $block = $pageBlocksMap[$type] ?? null;
                $content = $block['content'] ?? ($sampleData[$type] ?? []);
                $settings = $block['settings'] ?? [];

                try {
                    return \Illuminate\Support\Facades\Blade::render(
                        $html,
                        [
                            'theme' => $theme,
                            'nav_links' => $theme->layout_config['nav_links'] ?? [],
                            'content' => $content,
                            'settings' => $settings
                        ],
                        deleteCachedView: true
                    );
                } catch (\Throwable $e) {
                    return '<!-- Blade render error: ' . e($e->getMessage()) . " -->\n" . $html;
                }
            })
            ->all();
    @endphp

    @if($googleUrl)
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="{{ $googleUrl }}" rel="stylesheet">
    @endif

    {{-- Tailwind (Play CDN) — para que as classes utilitárias dos temas (py-24, bg-[#070C18], md:grid-cols-2, …) funcionem no preview --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        heading: ['var(--font-heading)', 'system-ui', 'sans-serif'],
                        body:    ['var(--font-body)', 'system-ui', 'sans-serif'],
                    },
                },
            },
        };
    </script>

    <style>
        :root {
            /* Default tokens */
            --color-primary:            oklch(0.55 0.22 265);
            --color-primary-foreground: oklch(1 0 0);
            --color-background:         oklch(0.99 0.003 265);
            --color-foreground:         oklch(0.13 0.02 265);
            --color-card:               oklch(1 0 0);
            --color-muted:              oklch(0.96 0.005 265);
            --color-muted-foreground:   oklch(0.50 0.02 265);
            --color-border:             oklch(0.91 0.005 265);
            --color-success:            oklch(0.65 0.20 150);
            --color-warning:            oklch(0.75 0.18 80);
            --color-destructive:        oklch(0.60 0.22 25);

            /* Override with theme tokens */
            @foreach($light as $var => $value)
            {{ $var }}: {{ $value }};
            @endforeach

            {{-- Companions -rgb para usos com alpha: rgba(var(--x-rgb), 0.15) --}}
            @foreach($light as $var => $value)
            @php $hx = ltrim(trim((string) $value), '#'); @endphp
            @if(preg_match('/^[0-9a-fA-F]{6}$/', $hx))
            {{ $var }}-rgb: {{ hexdec(substr($hx, 0, 2)) }}, {{ hexdec(substr($hx, 2, 2)) }}, {{ hexdec(substr($hx, 4, 2)) }};
            @endif
            @endforeach

            --font-heading: {{ $heading ?: 'system-ui' }};
            --font-body:    {{ $body    ?: 'system-ui' }};

            {{-- Layout & Content variables --}}
            @php
                $maxWidthRaw = $theme->layout_config['max_width'] ?? '1120';
                $maxWidth = ($maxWidthRaw === 'full') ? '100%' : ($maxWidthRaw . 'px');
                $spacingRaw = $theme->layout_config['spacing'] ?? 'normal';
                $paddingY = ($spacingRaw === 'compact') ? '2.5rem' : (($spacingRaw === 'spacious') ? '7.5rem' : '5rem');
            @endphp
            --layout-max-width: {{ $maxWidth }};
            --section-padding-y: {{ $paddingY }};
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: var(--color-background);
            color: var(--color-foreground);
            font-family: var(--font-body), system-ui, sans-serif;
            line-height: 1.6;
        }

        h1, h2, h3, h4 {
            font-family: var(--font-heading), system-ui, sans-serif;
            line-height: 1.2;
        }

        /* ── Estilos de Layout Dinâmico AnimusFlow ── */
        .layout-boxed {
            max-width: 1200px;
            margin: 0 auto;
            background: var(--color-background);
            box-shadow: 0 0 60px rgba(0,0,0,0.5);
            border-left: 1px solid var(--color-border);
            border-right: 1px solid var(--color-border);
            position: relative;
        }

        .layout-with-sidebar {
            display: flex;
            min-height: 100vh;
        }
        .layout-with-sidebar.pos-sidebar-left {
            flex-direction: row;
        }
        .layout-with-sidebar.pos-sidebar-right {
            flex-direction: row-reverse;
        }
        .layout-sidebar {
            width: 260px;
            flex-shrink: 0;
            background: var(--color-card);
            border-right: 1px solid var(--color-border);
            padding: 2rem 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 2rem;
            z-index: 99;
        }
        .layout-with-sidebar.pos-sidebar-right .layout-sidebar {
            border-right: none;
            border-left: 1px solid var(--color-border);
        }
        .layout-main {
            flex: 1;
            min-width: 0;
        }
        .sidebar-widget h4 {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--color-accent, var(--color-primary));
            margin-bottom: 0.75rem;
            font-family: var(--font-heading), monospace;
        }
        .sidebar-widget ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .sidebar-widget a {
            color: var(--color-muted-foreground);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: color 0.2s;
        }
        .sidebar-widget a:hover {
            color: var(--color-foreground);
        }
        .sidebar-widget p {
            color: var(--color-muted-foreground);
            font-size: 0.8rem;
            margin: 0.25rem 0;
        }

        @media (max-width: 768px) {
            .layout-with-sidebar {
                flex-direction: column !important;
            }
            .layout-sidebar {
                width: 100% !important;
                border-right: none !important;
                border-left: none !important;
                border-bottom: 1px solid var(--color-border) !important;
            }
        }

        /* ── Preview banner ── */
        .preview-banner {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 9999;
            background: var(--color-primary);
            color: var(--color-primary-foreground);
            text-align: center;
            font-size: 12px;
            font-weight: 600;
            padding: 6px 12px;
            letter-spacing: 0.05em;
        }

        .preview-content { padding-top: 32px; }

        /* ── Default section styles (used when no AI section override) ── */
        section { padding: 5rem 2rem; }
        .container { max-width: 1100px; margin: 0 auto; }

        /* Hero */
        .default-hero {
            background: var(--color-primary);
            color: var(--color-primary-foreground);
            text-align: center;
            padding: 8rem 2rem;
        }
        .default-hero h1 { font-size: clamp(2rem, 5vw, 4rem); margin-bottom: 1.5rem; }
        .default-hero p  { font-size: 1.25rem; opacity: 0.85; margin-bottom: 2.5rem; }
        .default-hero a  {
            display: inline-block;
            background: var(--color-primary-foreground);
            color: var(--color-primary);
            padding: 0.875rem 2.5rem;
            border-radius: 0.5rem;
            font-weight: 700;
            text-decoration: none;
        }

        /* Features */
        .default-features { background: var(--color-card); }
        .default-features h2 { text-align: center; font-size: 2rem; margin-bottom: 3rem; }
        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; }
        .feature-card {
            background: var(--color-muted);
            border: 1px solid var(--color-border);
            border-radius: 1rem;
            padding: 2rem;
        }
        .feature-card .icon { font-size: 2rem; margin-bottom: 1rem; }
        .feature-card h3 { font-size: 1.1rem; margin-bottom: 0.5rem; }
        .feature-card p { color: var(--color-muted-foreground); font-size: 0.9rem; }

        /* CTA */
        .default-cta {
            background: var(--color-primary);
            color: var(--color-primary-foreground);
            text-align: center;
        }
        .default-cta h2 { font-size: 2.25rem; margin-bottom: 1rem; }
        .default-cta p  { opacity: 0.85; margin-bottom: 2rem; font-size: 1.1rem; }
        .default-cta a  {
            display: inline-block;
            background: var(--color-primary-foreground);
            color: var(--color-primary);
            padding: 0.875rem 2.5rem;
            border-radius: 0.5rem;
            font-weight: 700;
            text-decoration: none;
        }

        /* Testimonials */
        .default-testimonials { background: var(--color-background); }
        .default-testimonials h2 { text-align: center; font-size: 2rem; margin-bottom: 3rem; }
        .testimonials-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; }
        .testimonial-card {
            background: var(--color-card);
            border: 1px solid var(--color-border);
            border-radius: 1rem;
            padding: 2rem;
        }
        .testimonial-card blockquote { font-size: 1rem; font-style: italic; margin-bottom: 1.5rem; }
        .testimonial-card cite { font-size: 0.85rem; color: var(--color-muted-foreground); font-style: normal; }
        .testimonial-card strong { display: block; color: var(--color-foreground); font-weight: 600; }

        /* Pricing */
        .default-pricing { background: var(--color-muted); }
        .default-pricing h2 { text-align: center; font-size: 2rem; margin-bottom: 3rem; }
        .pricing-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; }
        .pricing-card {
            background: var(--color-card);
            border: 1px solid var(--color-border);
            border-radius: 1rem;
            padding: 2rem;
            text-align: center;
        }
        .pricing-card h3 { font-size: 1.25rem; margin-bottom: 0.5rem; }
        .pricing-card .price { font-size: 3rem; font-weight: 800; color: var(--color-primary); margin: 1rem 0; }
        .pricing-card ul { list-style: none; text-align: left; margin-bottom: 2rem; }
        .pricing-card li { padding: 0.4rem 0; color: var(--color-muted-foreground); font-size: 0.9rem; }
        .pricing-card li::before { content: '✓ '; color: var(--color-success); font-weight: 700; }
        .pricing-card a {
            display: block;
            background: var(--color-primary);
            color: var(--color-primary-foreground);
            padding: 0.75rem;
            border-radius: 0.5rem;
            font-weight: 600;
            text-decoration: none;
        }

        /* AI override placeholder */
        .ai-section { background: var(--color-background); }

        /* ── Editor overlay styles ── */
        .af-edit-highlight {
            outline: 2px solid var(--color-primary) !important;
            outline-offset: 2px !important;
            cursor: crosshair !important;
            transition: outline 0.1s;
        }
        .af-edit-hover {
            outline: 2px dashed var(--color-primary) !important;
            outline-offset: 2px !important;
            cursor: crosshair !important;
        }
        .af-tooltip {
            position: fixed;
            background: #1e1e2e;
            color: #cdd6f4;
            font: 11px/1.4 monospace;
            padding: 4px 8px;
            border-radius: 6px;
            pointer-events: none;
            z-index: 99998;
            max-width: 220px;
            border: 1px solid #313244;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        #af-inspector {
            position: fixed;
            top: 40px; right: 0;
            width: 280px;
            height: calc(100vh - 40px);
            background: #1e1e2e;
            color: #cdd6f4;
            font-family: system-ui, sans-serif;
            font-size: 12px;
            z-index: 99999;
            display: flex;
            flex-direction: column;
            border-left: 1px solid #313244;
            transform: translateX(100%);
            transition: transform 0.25s ease;
            box-shadow: -4px 0 20px rgba(0,0,0,0.4);
        }
        #af-inspector.af-open { transform: translateX(0); }
        #af-inspector-header {
            padding: 12px 14px;
            background: #181825;
            border-bottom: 1px solid #313244;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }
        #af-inspector-header h3 {
            flex: 1;
            font-size: 12px;
            font-weight: 700;
            color: #cba6f7;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        #af-close-btn {
            width: 22px; height: 22px;
            background: #313244;
            border: none; border-radius: 6px;
            color: #cdd6f4; cursor: pointer;
            font-size: 12px; display: flex;
            align-items: center; justify-content: center;
        }
        #af-close-btn:hover { background: #45475a; }
        #af-inspector-body { flex: 1; overflow-y: auto; padding: 12px; display: flex; flex-direction: column; gap: 12px; }
        .af-section-title {
            font-size: 9px; font-weight: 700; letter-spacing: 0.1em;
            text-transform: uppercase; color: #6c7086; margin-bottom: 6px;
        }
        .af-token-row {
            display: flex; align-items: center; gap: 8px;
            padding: 6px 8px; background: #181825;
            border-radius: 8px; border: 1px solid #313244;
            transition: border-color 0.15s;
        }
        .af-token-row:hover { border-color: #cba6f7; }
        .af-token-row.af-active { border-color: #cba6f7; background: #1e1e2e; }
        .af-color-swatch {
            width: 22px; height: 22px; border-radius: 6px;
            border: 1px solid rgba(255,255,255,0.1);
            flex-shrink: 0; cursor: pointer;
            position: relative; overflow: hidden;
        }
        .af-color-swatch input[type=color] {
            position: absolute; inset: 0;
            opacity: 0; width: 100%; height: 100%; cursor: pointer;
        }
        .af-token-info { flex: 1; min-width: 0; }
        .af-token-name { font-size: 10px; color: #89b4fa; font-family: monospace; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .af-token-value { font-size: 10px; color: #a6e3a1; font-family: monospace; }
        .af-token-input {
            width: 64px; background: #313244; border: 1px solid #45475a;
            border-radius: 4px; color: #cdd6f4; font-size: 10px;
            font-family: monospace; padding: 2px 4px; text-align: center;
        }
        .af-token-input:focus { outline: 1px solid #cba6f7; }
        .af-element-badge {
            display: inline-flex; align-items: center; gap: 4px;
            background: #313244; border-radius: 6px;
            padding: 4px 8px; font-family: monospace; font-size: 11px; color: #f38ba8;
        }
        #af-save-btn {
            margin: 12px; padding: 10px;
            background: #cba6f7; color: #1e1e2e;
            border: none; border-radius: 10px;
            font-weight: 700; font-size: 12px; cursor: pointer;
            flex-shrink: 0; transition: opacity 0.2s;
        }
        #af-save-btn:hover { opacity: 0.85; }
        #af-mode-toggle {
            position: fixed; bottom: 16px; left: 50%; transform: translateX(-50%);
            background: #1e1e2e; border: 1px solid #cba6f7;
            color: #cba6f7; padding: 8px 18px; border-radius: 20px;
            font-size: 12px; font-weight: 700; cursor: pointer;
            z-index: 99997; display: none; align-items: center; gap: 6px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.4);
            font-family: system-ui, sans-serif;
        }
        body.af-edit-active #af-mode-toggle { background: #cba6f7; color: #1e1e2e; }
        .af-font-input {
            flex: 1; background: #313244; border: 1px solid #45475a;
            border-radius: 4px; color: #cdd6f4; font-size: 10px; padding: 3px 6px;
        }
        .af-font-input:focus { outline: 1px solid #cba6f7; }
    </style>

    {{-- CSS personalizado do tema (injetado depois dos estilos default para poder sobrepô-los) --}}
    @if(!empty($theme->custom_css))
    <style id="af-theme-custom-css">
{!! $theme->custom_css !!}
    </style>
    @endif
</head>
<body>
    <div class="preview-banner">
        🎨 PREVIEW — {{ $theme->label }} v{{ $theme->version ?? '1.0.0' }}
        &nbsp;·&nbsp;
        <a href="javascript:window.close()" style="color:inherit;text-decoration:underline;">Close</a>
    </div>

    @php
        $layoutType = $theme->layout_config['layout_type'] ?? 'full-width';
    @endphp
    <div class="preview-content @if($layoutType === 'boxed') layout-boxed @elseif(str_contains($layoutType, 'sidebar')) layout-with-sidebar pos-{{ $layoutType }} @endif">
        @if(str_contains($layoutType, 'sidebar'))
            <aside class="layout-sidebar">
                <div class="sidebar-widget">
                    <h4>Navegação</h4>
                    <ul>
                        @foreach($theme->layout_config['nav_links'] ?? [] as $link)
                            <li><a href="{{ $link['url'] }}">{{ $link['label'] }}</a></li>
                        @endforeach
                    </ul>
                </div>
                <div class="sidebar-widget">
                    <h4>Telemetria</h4>
                    <p>Status: Operacional</p>
                    <p>Conexão: Segura</p>
                    <p>Sede: Luanda, AO</p>
                </div>
            </aside>
            <main class="layout-main">
        @endif

        {{-- Hero --}}
        @if(in_array('hero', $allowedSections))
            @if(isset($sections['hero']))
                <div class="ai-section">{!! $sections['hero'] !!}</div>
            @else
                <section class="default-hero">
                    <div class="container">
                        <h1>{{ $sampleData['hero']['heading'] }}</h1>
                        <p>{{ $sampleData['hero']['subtext'] }}</p>
                        <a href="{{ $sampleData['hero']['cta_url'] }}">{{ $sampleData['hero']['cta_text'] }}</a>
                    </div>
                </section>
            @endif
        @endif

        {{-- Features --}}
        @if(in_array('features', $allowedSections))
            @if(isset($sections['features']))
                <div class="ai-section">{!! $sections['features'] !!}</div>
            @else
                <section class="default-features">
                    <div class="container">
                        <h2>{{ $sampleData['features']['heading'] }}</h2>
                        <div class="features-grid">
                            @foreach($sampleData['features']['items'] as $item)
                                <div class="feature-card">
                                    <div class="icon">{{ $item['icon'] }}</div>
                                    <h3>{{ $item['title'] }}</h3>
                                    <p>{{ $item['text'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif
        @endif

        {{-- Testimonials --}}
        @if(in_array('testimonials', $allowedSections))
            @if(isset($sections['testimonials']))
                <div class="ai-section">{!! $sections['testimonials'] !!}</div>
            @else
                <section class="default-testimonials">
                    <div class="container">
                        <h2>{{ $sampleData['testimonials']['heading'] }}</h2>
                        <div class="testimonials-grid">
                            @foreach($sampleData['testimonials']['items'] as $item)
                                <div class="testimonial-card">
                                    <blockquote>"{{ $item['quote'] }}"</blockquote>
                                    <cite>
                                        <strong>{{ $item['author'] }}</strong>
                                        {{ $item['role'] }}
                                    </cite>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif
        @endif

        {{-- Pricing --}}
        @if(in_array('pricing', $allowedSections))
            @if(isset($sections['pricing']))
                <div class="ai-section">{!! $sections['pricing'] !!}</div>
            @else
                <section class="default-pricing">
                    <div class="container">
                        <h2>{{ $sampleData['pricing']['heading'] }}</h2>
                        <div class="pricing-grid">
                            @foreach($sampleData['pricing']['items'] as $item)
                                <div class="pricing-card">
                                    <h3>{{ $item['label'] }}</h3>
                                    <div class="price">{{ $item['price'] }}</div>
                                    <ul>
                                        @foreach($item['features'] as $feature)
                                            <li>{{ $feature }}</li>
                                        @endforeach
                                    </ul>
                                    <a href="#">Get started</a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif
        @endif

        {{-- CTA --}}
        @if(in_array('cta', $allowedSections))
            @if(isset($sections['cta']))
                <div class="ai-section">{!! $sections['cta'] !!}</div>
            @else
                <section class="default-cta">
                    <div class="container">
                        <h2>{{ $sampleData['cta']['heading'] }}</h2>
                        <p>{{ $sampleData['cta']['subtext'] }}</p>
                        <a href="{{ $sampleData['cta']['cta_url'] }}">{{ $sampleData['cta']['cta_text'] }}</a>
                    </div>
                </section>
            @endif
        @endif

        {{-- About --}}
        @if(in_array('about', $allowedSections) && isset($sections['about']))
            <div class="ai-section">{!! $sections['about'] !!}</div>
        @endif

        {{-- Stats --}}
        @if(in_array('stats', $allowedSections) && isset($sections['stats']))
            <div class="ai-section">{!! $sections['stats'] !!}</div>
        @endif

        {{-- Text --}}
        @if(in_array('text', $allowedSections) && isset($sections['text']))
            <div class="ai-section">{!! $sections['text'] !!}</div>
        @endif

        {{-- Team --}}
        @if(in_array('team', $allowedSections) && isset($sections['team']))
            <div class="ai-section">{!! $sections['team'] !!}</div>
        @endif

        {{-- Steps --}}
        @if(in_array('steps', $allowedSections) && isset($sections['steps']))
            <div class="ai-section">{!! $sections['steps'] !!}</div>
        @endif

        {{-- Gallery --}}
        @if(in_array('gallery', $allowedSections) && isset($sections['gallery']))
            <div class="ai-section">{!! $sections['gallery'] !!}</div>
        @endif

        {{-- Contact --}}
        @if(in_array('contact', $allowedSections) && isset($sections['contact']))
            <div class="ai-section">{!! $sections['contact'] !!}</div>
        @endif

        {{-- Map --}}
        @if(in_array('map', $allowedSections) && isset($sections['map']))
            <div class="ai-section">{!! $sections['map'] !!}</div>
        @endif

        {{-- Footer --}}
        @if(isset($sections['footer']))
            <div class="ai-section">{!! $sections['footer'] !!}</div>
        @endif

        {{-- Any extra AI-generated sections --}}
        @foreach($sections as $type => $html)
            @if(!in_array($type, ['hero', 'features', 'testimonials', 'pricing', 'cta', 'text', 'team', 'steps', 'gallery', 'contact', 'map', 'about', 'stats', 'footer']))

                <div class="ai-section">
                    @if(empty($html))
                        @if($type === 'ai_chatbox')
                            <!-- AI Chatbox fallback mockup -->
                            <section class="default-ai-chatbox" style="padding: 4rem 2rem; background: var(--color-muted); border-top: 1px solid var(--color-border); border-bottom: 1px solid var(--color-border);">
                                <div class="container" style="max-width: 600px; margin: 0 auto;">
                                    <div style="background: var(--color-card); border: 1px solid var(--color-border); border-radius: 1.5rem; overflow: hidden; box-shadow: 0 10px 30px -10px rgba(0,0,0,0.1); border-left: 4px solid var(--color-primary);">
                                        <div style="background: var(--color-primary); color: var(--color-primary-foreground); padding: 1.25rem; font-weight: 700; display: flex; align-items: center; justify-content: space-between;">
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <span style="font-size: 1.5rem;">🤖</span>
                                                <div>
                                                    <div style="font-size: 0.95rem; line-height: 1.2;">Assistente Inteligente</div>
                                                    <div style="font-size: 0.75rem; opacity: 0.8; font-weight: normal;">Online • Responde na hora</div>
                                                </div>
                                            </div>
                                            <span style="background: rgba(255,255,255,0.2); font-size: 0.7rem; padding: 2px 8px; border-radius: 9999px;">AI Chat</span>
                                        </div>
                                        <div style="padding: 2rem; height: 260px; display: flex; flex-direction: column; justify-content: flex-end; gap: 1rem; overflow-y: auto; background: var(--color-card);">
                                            <div style="align-self: flex-start; max-width: 80%;">
                                                <div style="background: var(--color-muted); color: var(--color-foreground); padding: 0.85rem 1.15rem; border-radius: 0.25rem 1rem 1rem 1rem; font-size: 0.875rem; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                                                    Olá! Sou o assistente virtual do site. Em que posso ser útil hoje?
                                                </div>
                                                <span style="font-size: 0.65rem; color: var(--color-muted-foreground); margin-left: 4px; margin-top: 4px; display: block;">Assistente • Agora mesmo</span>
                                            </div>
                                        </div>
                                        <div style="padding: 1rem 1.5rem; border-top: 1px solid var(--color-border); display: flex; gap: 8px; background: var(--color-muted);">
                                            <input type="text" placeholder="Escreva a sua pergunta aqui..." disabled style="flex: 1; padding: 0.75rem 1rem; border: 1px solid var(--color-border); border-radius: 0.75rem; font-size: 0.875rem; background: var(--color-card); color: var(--color-foreground); outline: none;">
                                            <button disabled style="background: var(--color-primary); color: var(--color-primary-foreground); border: none; padding: 0.75rem 1.25rem; border-radius: 0.75rem; font-weight: 700; font-size: 0.875rem; opacity: 0.6; cursor: not-allowed; display: flex; align-items: center; gap: 4px;">
                                                Enviar <span>➔</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        @elseif($type === 'ai_recommendations')
                            <!-- AI Recommendations fallback mockup -->
                            <section class="default-ai-reco" style="padding: 5rem 2rem; background: var(--color-background);">
                                <div class="container" style="max-width: 1100px; margin: 0 auto;">
                                    <div style="text-align: center; margin-bottom: 3.5rem;">
                                        <span style="background: linear-gradient(135deg, var(--color-primary), var(--color-secondary)); color: var(--color-primary-foreground); padding: 4px 12px; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;">✨ Recomendado Para Si</span>
                                        <h2 style="font-size: 2.25rem; margin-top: 0.75rem; color: var(--color-foreground);">Destaques Personalizados</h2>
                                        <p style="color: var(--color-muted-foreground); margin-top: 0.5rem; font-size: 0.95rem;">Sugestões inteligentes com base na sua navegação</p>
                                    </div>
                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                                        <div style="background: var(--color-card); border: 1px solid var(--color-border); border-radius: 1.25rem; padding: 2rem; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); display: flex; flex-direction: column; justify-content: space-between; border-top: 4px solid var(--color-secondary);">
                                            <div>
                                                <span style="font-size: 1.5rem; display: block; margin-bottom: 1rem;">📖</span>
                                                <h3 style="font-size: 1.2rem; margin-bottom: 0.75rem; color: var(--color-foreground); font-weight: 700;">Guia Avançado de Funcionalidades</h3>
                                                <p style="color: var(--color-muted-foreground); font-size: 0.875rem; line-height: 1.6; margin-bottom: 1.5rem;">Descubra como potenciar os seus resultados utilizando o nosso ecossistema inteligente de forma integrada.</p>
                                            </div>
                                            <a href="#" style="color: var(--color-primary); font-weight: 700; font-size: 0.875rem; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">Ler Artigo <span style="font-size: 1rem;">➔</span></a>
                                        </div>
                                        <div style="background: var(--color-card); border: 1px solid var(--color-border); border-radius: 1.25rem; padding: 2rem; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); display: flex; flex-direction: column; justify-content: space-between; border-top: 4px solid var(--color-primary);">
                                            <div>
                                                <span style="font-size: 1.5rem; display: block; margin-bottom: 1rem;">⚙️</span>
                                                <h3 style="font-size: 1.2rem; margin-bottom: 0.75rem; color: var(--color-foreground); font-weight: 700;">Configurações Recomendadas</h3>
                                                <p style="color: var(--color-muted-foreground); font-size: 0.875rem; line-height: 1.6; margin-bottom: 1.5rem;">Recomendado com base nas suas preferências recentes. Otimize o seu workspace com apenas 3 passos.</p>
                                            </div>
                                            <a href="#" style="color: var(--color-primary); font-weight: 700; font-size: 0.875rem; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">Configurar <span style="font-size: 1rem;">➔</span></a>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        @elseif($type === 'ai_summary')
                            <!-- AI Summary fallback mockup -->
                            <section class="default-ai-summary" style="padding: 4rem 2rem; background: var(--color-background);">
                                <div class="container" style="max-width: 750px; margin: 0 auto;">
                                    <div style="background: var(--color-card); border: 1px solid var(--color-border); border-radius: 1.5rem; padding: 2.5rem; box-shadow: 0 4px 20px -2px rgba(0,0,0,0.05); position: relative; overflow: hidden;">
                                        <div style="position: absolute; top: 0; left: 0; width: 6px; height: 100%; background: linear-gradient(to bottom, var(--color-primary), var(--color-secondary));"></div>
                                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 1.25rem;">
                                            <span style="background: var(--color-primary); color: var(--color-primary-foreground); padding: 3px 10px; border-radius: 9999px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">🤖 RESUMO DA PÁGINA</span>
                                            <span style="font-size: 0.75rem; color: var(--color-muted-foreground);">Atualizado automaticamente</span>
                                        </div>
                                        <h3 style="font-size: 1.5rem; margin-bottom: 1rem; color: var(--color-foreground);">Pontos Chave</h3>
                                        <ul style="list-style: none; padding: 0; display: flex; flex-direction: column; gap: 12px; font-size: 0.95rem; color: var(--color-foreground);">
                                            <li style="display: flex; align-items: flex-start; gap: 10px;">
                                                <span style="background: rgba(16,185,129,0.1); color: #10b981; width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.75rem; flex-shrink: 0; margin-top: 2px;">✓</span>
                                                <span><strong>Otimização de Fluxo:</strong> A arquitetura nativa permite gerir secções e templates sem impacto nos tempos de carregamento do utilizador.</span>
                                            </li>
                                            <li style="display: flex; align-items: flex-start; gap: 10px;">
                                                <span style="background: rgba(16,185,129,0.1); color: #10b981; width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.75rem; flex-shrink: 0; margin-top: 2px;">✓</span>
                                                <span><strong>Estilo Dinâmico:</strong> Integração fluida com as variáveis CSS de cores oklch e tipografias do tema ativo.</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </section>
                        @elseif($type === 'ai_faq')
                            <!-- AI FAQ fallback mockup -->
                            <section class="default-ai-faq" style="padding: 5rem 2rem; background: var(--color-muted); border-top: 1px solid var(--color-border); border-bottom: 1px solid var(--color-border);">
                                <div class="container" style="max-width: 800px; margin: 0 auto;">
                                    <div style="text-align: center; margin-bottom: 3rem;">
                                        <span style="background: var(--color-primary); color: var(--color-primary-foreground); padding: 4px 12px; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">❓ FAQ IA</span>
                                        <h2 style="font-size: 2rem; margin-top: 0.75rem; color: var(--color-foreground);">Dúvidas Frequentes Resolvidas</h2>
                                        <p style="color: var(--color-muted-foreground); margin-top: 0.5rem; font-size: 0.9rem;">Perguntas geradas de forma dinâmica e automática sobre esta secção</p>
                                    </div>
                                    <div style="display: flex; flex-direction: column; gap: 12px;">
                                        <div style="background: var(--color-card); border: 1px solid var(--color-border); border-radius: 1rem; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                                            <div style="padding: 1.25rem 1.5rem; font-weight: 700; color: var(--color-foreground); display: flex; justify-content: space-between; align-items: center; font-size: 0.95rem;">
                                                <span>Como configuro o chatbot com o Pinecone?</span>
                                                <span style="color: var(--color-primary); font-size: 1.25rem;">▾</span>
                                            </div>
                                            <div style="padding: 0 1.5rem 1.25rem 1.5rem; color: var(--color-muted-foreground); font-size: 0.875rem; line-height: 1.6;">
                                                A configuração é feita no painel administrativo do AnimusFlow CMS em Definições de IA, inserindo a API key do Pinecone, a região/host do index e ativando o serviço.
                                            </div>
                                        </div>
                                        <div style="background: var(--color-card); border: 1px solid var(--color-border); border-radius: 1rem; overflow: hidden; opacity: 0.9;">
                                            <div style="padding: 1.25rem 1.5rem; font-weight: 700; color: var(--color-foreground); display: flex; justify-content: space-between; align-items: center; font-size: 0.95rem; cursor: not-allowed;">
                                                <span>Posso personalizar os templates blade destas secções?</span>
                                                <span style="color: var(--color-primary); font-size: 1.25rem;">▸</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        @elseif($type === 'ai_search')
                            <!-- AI Search fallback mockup -->
                            <section class="default-ai-search" style="padding: 5rem 2rem; background: var(--color-background);">
                                <div class="container" style="max-width: 700px; margin: 0 auto; text-align: center;">
                                    <span style="background: var(--color-secondary); color: var(--color-primary-foreground); padding: 4px 12px; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">🔍 Procura Inteligente</span>
                                    <h2 style="font-size: 2rem; margin-top: 0.75rem; margin-bottom: 2rem; color: var(--color-foreground);">O que procura hoje?</h2>
                                    <div style="display: flex; border: 2px solid var(--color-primary); border-radius: 9999px; overflow: hidden; background: var(--color-card); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); padding: 6px;">
                                        <span style="display: flex; align-items: center; padding-left: 1.25rem; font-size: 1.2rem;">🔍</span>
                                        <input type="text" placeholder="Pergunte-me qualquer detalhe sobre o serviço..." disabled style="flex: 1; padding: 0.85rem 1rem; border: none; background: transparent; font-size: 1rem; outline: none; color: var(--color-foreground);">
                                        <button disabled style="background: var(--color-primary); color: var(--color-primary-foreground); border: none; padding: 0.85rem 2rem; border-radius: 9999px; font-weight: 700; font-size: 0.95rem; opacity: 0.9; cursor: not-allowed;">Pesquisar</button>
                                    </div>
                                    <div style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 1.25rem; flex-wrap: wrap;">
                                        <span style="font-size: 0.8rem; color: var(--color-muted-foreground);">Sugestões:</span>
                                        <button disabled style="background: var(--color-muted); border: 1px solid var(--color-border); padding: 4px 12px; border-radius: 9999px; font-size: 0.75rem; color: var(--color-foreground);">Preços de adesão</button>
                                        <button disabled style="background: var(--color-muted); border: 1px solid var(--color-border); padding: 4px 12px; border-radius: 9999px; font-size: 0.75rem; color: var(--color-foreground);">Funcionalidades SaaS</button>
                                    </div>
                                </div>
                            </section>
                        @elseif($type === 'ai_personalized')
                            <!-- AI Personalized fallback mockup -->
                            <section class="default-ai-perso" style="padding: 6rem 2rem; background: linear-gradient(135deg, var(--color-primary), var(--color-secondary)); color: var(--color-primary-foreground); text-align: center;">
                                <div class="container" style="max-width: 800px; margin: 0 auto;">
                                    <span style="background: var(--color-primary-foreground); color: var(--color-primary); padding: 4px 14px; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">👤 Experiência Customizada</span>
                                    <h2 style="font-size: 2.5rem; font-weight: 800; margin-top: 1.5rem; line-height: 1.1;">Olá! Obrigado por regressar ao nosso site.</h2>
                                    <p style="opacity: 0.9; margin-top: 1rem; font-size: 1.2rem; max-width: 600px; margin-left: auto; margin-right: auto; line-height: 1.6;">Esta secção adaptou-se automaticamente com base na sua visita anterior. Temos novas sugestões preparadas especificamente para si.</p>
                                    <div style="margin-top: 2.5rem; display: flex; gap: 1rem; justify-content: center;">
                                        <a href="#" style="background: var(--color-primary-foreground); color: var(--color-primary); padding: 0.85rem 2rem; border-radius: 0.75rem; font-weight: 700; text-decoration: none; font-size: 0.95rem; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">Ver O Que Há De Novo</a>
                                        <a href="#" style="background: transparent; color: var(--color-primary-foreground); border: 2px solid var(--color-primary-foreground); padding: 0.85rem 2rem; border-radius: 0.75rem; font-weight: 700; text-decoration: none; font-size: 0.95rem;">Saber Mais</a>
                                    </div>
                                </div>
                            </section>
                        @else
                            <div style="padding: 3rem; background: var(--color-muted); border: 2px dashed var(--color-border); text-align: center; border-radius: 1.5rem; font-size: 0.875rem; color: var(--color-muted-foreground); margin: 2rem auto; max-width: 600px;">
                                Bloco Personalizado Activo: <strong>{{ $type }}</strong> (Vazio)
                            </div>
                        @endif
                    @else
                        {!! $html !!}
                    @endif
                </div>
            @endif
        @endforeach

        @if(str_contains($layoutType, 'sidebar'))
            </main>
        @endif
    </div>

    <!-- ── CSS Token Editor Overlay ── -->
    <button id="af-mode-toggle">✦ Modo Edição</button>

    <div id="af-inspector">
        <div id="af-inspector-header">
            <span>✦</span>
            <h3>Design Inspector</h3>
            <button id="af-close-btn" title="Fechar">✕</button>
        </div>
        <div id="af-inspector-body">
            <div id="af-element-info" style="display:none">
                <div class="af-section-title">Elemento seleccionado</div>
                <div id="af-element-badge" class="af-element-badge"></div>
            </div>
            <div id="af-element-tokens" style="display:none">
                <div class="af-section-title">Tokens deste elemento</div>
                <div id="af-element-tokens-list"></div>
            </div>
            <div id="af-all-tokens">
                <div class="af-section-title">Cores do tema</div>
                <div id="af-colors-list"></div>
            </div>
            <div id="af-font-tokens">
                <div class="af-section-title">Tipografia</div>
                <div id="af-fonts-list"></div>
            </div>
            <div id="af-empty" style="text-align:center;padding:24px 0;color:#6c7086;">
                <div style="font-size:24px;margin-bottom:8px;">🖱</div>
                <div style="font-size:11px;">Clica em qualquer elemento<br>para inspeccionar os seus tokens</div>
            </div>
        </div>
        <button id="af-save-btn">💾 Guardar tema</button>
    </div>

    <div class="af-tooltip" id="af-tooltip" style="display:none"></div>

    @php
        // Build the known CSS vars map to pass to JS
        $allVars = array_merge(
            $light,
            ['--font-heading' => $fonts['heading'] ?? '', '--font-body' => $fonts['body'] ?? '']
        );
    @endphp

    <script>
    (function() {
        // ── Theme token registry ────────────────────────────────────────
        const THEME_VARS = @json($allVars);

        // All color + font vars defined on :root
        const COLOR_VARS  = Object.keys(THEME_VARS).filter(k => k.startsWith('--color-'));
        const FONT_VARS   = Object.keys(THEME_VARS).filter(k => k.startsWith('--font-'));

        // ── State ───────────────────────────────────────────────────────
        let editActive   = false;
        let selectedEl   = null;
        let hoveredEl    = null;
        const tooltip    = document.getElementById('af-tooltip');
        const inspector  = document.getElementById('af-inspector');
        const toggle     = document.getElementById('af-mode-toggle');
        const emptyMsg   = document.getElementById('af-empty');
        const elInfo     = document.getElementById('af-element-info');
        const elBadge    = document.getElementById('af-element-badge');
        const elTokens   = document.getElementById('af-element-tokens');
        const elTokenList= document.getElementById('af-element-tokens-list');

        // ── Read live CSS var value ─────────────────────────────────────
        function getVar(name) {
            return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
        }
        function setVar(name, value) {
            document.documentElement.style.setProperty(name, value);
        }
        function isColor(val) {
            return /^#|^rgb|^oklch|^hsl/.test(val.trim());
        }

        // ── Detect CSS vars used by an element (via stylesheet rules) ───
        function detectElementVars(el) {
            const found = new Set();
            const sheets = document.styleSheets;
            for (const sheet of sheets) {
                let rules;
                try { rules = sheet.cssRules || []; } catch(e) { continue; }
                for (const rule of rules) {
                    if (!rule.selectorText || !rule.style) continue;
                    try {
                        if (el.matches(rule.selectorText) || el.closest(rule.selectorText)) {
                            const text = rule.style.cssText;
                            const matches = text.matchAll(/var\((--[\w-]+)\)/g);
                            for (const m of matches) {
                                if (COLOR_VARS.includes(m[1]) || FONT_VARS.includes(m[1])) {
                                    found.add(m[1]);
                                }
                            }
                        }
                    } catch(e) {}
                }
            }
            // Also check parent chain
            let parent = el.parentElement;
            while (parent && parent !== document.body) {
                for (const sheet of sheets) {
                    let rules;
                    try { rules = sheet.cssRules || []; } catch(e) { continue; }
                    for (const rule of rules) {
                        if (!rule.selectorText || !rule.style) continue;
                        try {
                            if (parent.matches(rule.selectorText)) {
                                const text = rule.style.cssText;
                                const matches = text.matchAll(/var\((--[\w-]+)\)/g);
                                for (const m of matches) {
                                    if (COLOR_VARS.includes(m[1]) || FONT_VARS.includes(m[1])) {
                                        found.add(m[1]);
                                    }
                                }
                            }
                        } catch(e) {}
                    }
                }
                parent = parent.parentElement;
            }
            return [...found];
        }

        // ── Build a single token row ────────────────────────────────────
        function buildTokenRow(varName, container, isElementRow) {
            const value = getVar(varName);
            const isColorVar = varName.startsWith('--color-');
            const isFontVar  = varName.startsWith('--font-');

            const row = document.createElement('div');
            row.className = 'af-token-row';
            row.dataset.var = varName;

            if (isColorVar) {
                // Resolve oklch/rgb to hex for the color picker
                const tempDiv = document.createElement('div');
                tempDiv.style.color = value;
                document.body.appendChild(tempDiv);
                const computed = getComputedStyle(tempDiv).color;
                document.body.removeChild(tempDiv);
                const hex = rgbToHex(computed);

                row.innerHTML = `
                    <div class="af-color-swatch" style="background:${value}" title="Clica para mudar cor">
                        <input type="color" value="${hex}" data-var="${varName}" />
                    </div>
                    <div class="af-token-info">
                        <div class="af-token-name">${varName}</div>
                        <div class="af-token-value">${value.length > 20 ? value.substring(0,20)+'…' : value}</div>
                    </div>
                    <input type="text" class="af-token-input" value="${hex}" data-var="${varName}" maxlength="9" />
                `;

                // Color input change
                const colorInput = row.querySelector('input[type=color]');
                const textInput  = row.querySelector('.af-token-input');
                const swatch     = row.querySelector('.af-color-swatch');

                colorInput.addEventListener('input', (e) => {
                    const v = e.target.value;
                    textInput.value = v;
                    swatch.style.background = v;
                    applyVarChange(varName, v);
                });
                textInput.addEventListener('change', (e) => {
                    const v = e.target.value.trim();
                    if (/^#[0-9a-fA-F]{3,8}$/.test(v)) {
                        colorInput.value = v;
                        swatch.style.background = v;
                        applyVarChange(varName, v);
                    }
                });

            } else if (isFontVar) {
                row.innerHTML = `
                    <div style="font-size:16px;width:22px;text-align:center">𝐀</div>
                    <div class="af-token-info">
                        <div class="af-token-name">${varName}</div>
                    </div>
                    <input type="text" class="af-font-input" value="${value}" data-var="${varName}" placeholder="Font family" />
                `;
                const fontInput = row.querySelector('.af-font-input');
                fontInput.addEventListener('change', (e) => applyVarChange(varName, e.target.value.trim()));
            }

            container.appendChild(row);
        }

        // ── Populate all tokens panel ───────────────────────────────────
        function populateAllTokens() {
            const colorsList = document.getElementById('af-colors-list');
            const fontsList  = document.getElementById('af-fonts-list');
            colorsList.innerHTML = '';
            fontsList.innerHTML  = '';
            COLOR_VARS.forEach(v => buildTokenRow(v, colorsList, false));
            FONT_VARS.forEach(v  => buildTokenRow(v, fontsList,  false));
        }

        // ── Show inspector for clicked element ──────────────────────────
        function inspectElement(el) {
            selectedEl = el;
            el.classList.add('af-edit-highlight');

            // Element badge
            const tag     = el.tagName.toLowerCase();
            const classes = el.className.split(' ').filter(c => c && !c.startsWith('af-')).slice(0,2).map(c => '.'+c).join('');
            elBadge.textContent = tag + (classes || '');
            elInfo.style.display = 'block';

            // Detect vars
            const vars = detectElementVars(el);
            if (vars.length > 0) {
                elTokenList.innerHTML = '';
                vars.forEach(v => buildTokenRow(v, elTokenList, true));
                elTokens.style.display = 'block';
                emptyMsg.style.display = 'none';
            } else {
                elTokens.style.display = 'none';
                emptyMsg.style.display = 'block';
                emptyMsg.innerHTML = '<div style="font-size:20px;margin-bottom:8px">🔍</div><div style="font-size:11px;color:#6c7086">Nenhum token detectado<br>neste elemento.<br><br>Usa os tokens globais abaixo.</div>';
            }

            // Refresh all tokens live values
            populateAllTokens();
        }

        // ── Apply a var change live + notify parent ─────────────────────
        function applyVarChange(varName, value) {
            setVar(varName, value);
            // Refresh swatch/value displays for same var in other rows
            document.querySelectorAll(`[data-var="${varName}"]`).forEach(input => {
                if (input.type === 'color') input.value = value;
            });
            // Notify parent frame
            window.parent.postMessage({ type: 'af-token-change', var: varName, value }, '*');
        }

        // ── Activate / deactivate edit mode ─────────────────────────────
        function activateEdit() {
            editActive = true;
            document.body.classList.add('af-edit-active');
            toggle.style.display = 'flex';
            toggle.textContent   = '✦ Edição Activa — clica para desactivar';
            inspector.classList.add('af-open');
            populateAllTokens();
            window.parent.postMessage({ type: 'af-edit-activated' }, '*');
        }

        function deactivateEdit() {
            editActive = false;
            document.body.classList.remove('af-edit-active');
            toggle.textContent = '✦ Modo Edição';
            inspector.classList.remove('af-open');
            clearHighlight();
            tooltip.style.display = 'none';
            window.parent.postMessage({ type: 'af-edit-deactivated' }, '*');
        }

        function clearHighlight() {
            if (selectedEl) { selectedEl.classList.remove('af-edit-highlight'); selectedEl = null; }
            if (hoveredEl)  { hoveredEl.classList.remove('af-edit-hover');      hoveredEl  = null; }
        }

        // ── Mouse events ────────────────────────────────────────────────
        document.addEventListener('mousemove', (e) => {
            if (!editActive) return;
            const el = document.elementFromPoint(e.clientX, e.clientY);
            if (!el || el.id?.startsWith('af-') || el.closest('#af-inspector') || el.closest('#af-mode-toggle')) return;

            if (hoveredEl && hoveredEl !== el) hoveredEl.classList.remove('af-edit-hover');
            if (el !== selectedEl) {
                el.classList.add('af-edit-hover');
                hoveredEl = el;
            }

            // Tooltip
            const tag = el.tagName.toLowerCase();
            const cls = el.className.split(' ').filter(c => c && !c.startsWith('af-')).slice(0,1).join('');
            tooltip.textContent = tag + (cls ? '.'+cls : '');
            tooltip.style.display = 'block';
            tooltip.style.left = (e.clientX + 12) + 'px';
            tooltip.style.top  = (e.clientY - 28) + 'px';
        });

        document.addEventListener('mouseleave', () => { tooltip.style.display = 'none'; });

        document.addEventListener('click', (e) => {
            if (!editActive) return;
            const el = e.target;
            if (el.id?.startsWith('af-') || el.closest('#af-inspector') || el.closest('#af-mode-toggle')) return;
            e.preventDefault(); e.stopPropagation();

            if (selectedEl) selectedEl.classList.remove('af-edit-highlight');
            inspectElement(el);
        }, true);

        // ── Toggle button ───────────────────────────────────────────────
        toggle.addEventListener('click', () => editActive ? deactivateEdit() : activateEdit());
        document.getElementById('af-close-btn').addEventListener('click', deactivateEdit);
        document.getElementById('af-save-btn').addEventListener('click', () => {
            window.parent.postMessage({ type: 'af-save-request' }, '*');
        });

        // ── postMessage from parent ─────────────────────────────────────
        window.addEventListener('message', (e) => {
            const d = e.data;
            if (!d || typeof d !== 'object') return;
            if (d.type === 'af-enable-edit')  activateEdit();
            if (d.type === 'af-disable-edit') deactivateEdit();
            if (d.type === 'af-apply-vars' && d.vars) {
                Object.entries(d.vars).forEach(([k, v]) => { if (v) setVar(k, v); });
                if (editActive) populateAllTokens();
            }
        });

        // ── Activate if ?edit=1 in URL ──────────────────────────────────
        if (new URLSearchParams(location.search).get('edit') === '1') {
            toggle.style.display = 'flex';
            setTimeout(activateEdit, 300);
        }

        // ── Signal ready to parent ──────────────────────────────────────
        window.parent.postMessage({ type: 'af-ready' }, '*');

        // ── Utility: rgb() → #hex ───────────────────────────────────────
        function rgbToHex(rgb) {
            const m = rgb.match(/\d+/g);
            if (!m || m.length < 3) return '#000000';
            return '#' + [m[0],m[1],m[2]].map(x => parseInt(x).toString(16).padStart(2,'0')).join('');
        }
    })();
    </script>

    {{-- JS personalizado do tema (interações: preloader, malha 3D, efeitos sonoros, comandos de voz, …) --}}
    @if(!empty($theme->custom_js))
    <script id="af-theme-custom-js">
{!! $theme->custom_js !!}
    </script>
    @endif
    {{-- Interceptar cliques em links no preview para evitar sair do preview ao clicar na Home --}}
    <script>
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a');
        if (link && link.href) {
            try {
                const url = new URL(link.href);
                if (url.origin === window.location.origin) {
                    if (url.pathname === '/') {
                        e.preventDefault();
                        window.location.href = '/preview-home';
                    }
                }
            } catch(err) {}
        }
    }, true);
    </script>
</body>
</html>
