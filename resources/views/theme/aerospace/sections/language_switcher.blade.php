@php
    $c = $content ?? [];
    $s = $block->settings ?? [];
    
    $placement = $s['placement'] ?? $c['placement'] ?? 'block';
    $secBg = $s['bg_color'] ?? $c['bg_color'] ?? '#070C18';
    $secText = $s['text_color'] ?? $c['text_color'] ?? null;
    
    $blockUuid = $block->uuid ?? $block->id ?? 'lang-switch-aerospace';
@endphp

@if($placement === 'navbar' || $placement === 'footer')
    {{-- Rendered directly by layout.blade.php in navbar / footer --}}
@else
<section class="py-20 relative" id="{{ $s['html_id'] ?? '' }}" style="background: {{ $secBg }} !important; @if($secText) color: {{ $secText }} !important; @endif">
    <div class="af-container relative z-10">
        <!-- Mount Point para a "Ilha" Vue de Seletor de Idiomas -->
        <div data-vue-component="LanguageSwitcher" data-props="{{ json_encode(['content' => $c, 'settings' => $s]) }}"></div>
    </div>
</section>
@endif
