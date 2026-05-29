<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview — {{ $theme->label }}</title>

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

        $sections = $theme->sections ?? [];
    @endphp

    @if($googleUrl)
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="{{ $googleUrl }}" rel="stylesheet">
    @endif

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

            --font-heading: {{ $heading ?: 'system-ui' }};
            --font-body:    {{ $body    ?: 'system-ui' }};
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
    </style>
</head>
<body>
    <div class="preview-banner">
        🎨 PREVIEW — {{ $theme->label }} v{{ $theme->version ?? '1.0.0' }}
        &nbsp;·&nbsp;
        <a href="javascript:window.close()" style="color:inherit;text-decoration:underline;">Close</a>
    </div>

    <div class="preview-content">

        {{-- Hero --}}
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

        {{-- Features --}}
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

        {{-- Testimonials --}}
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

        {{-- Pricing --}}
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

        {{-- CTA --}}
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

        {{-- Any extra AI-generated sections --}}
        @foreach($sections as $type => $html)
            @if(!in_array($type, ['hero', 'features', 'testimonials', 'pricing', 'cta']))
                <div class="ai-section">{!! $html !!}</div>
            @endif
        @endforeach

    </div>
</body>
</html>
