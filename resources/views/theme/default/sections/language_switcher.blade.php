@php
    $c = $content ?? [];
    $s = $block->settings ?? [];
    
    $placement = $s['placement'] ?? $c['placement'] ?? 'block';
    $secBg = $s['bg_color'] ?? $c['bg_color'] ?? null;
    $secText = $s['text_color'] ?? $c['text_color'] ?? null;
    
    $blockUuid = $block->uuid ?? $block->id ?? 'lang-switch-default';
@endphp

@if($placement === 'navbar' || $placement === 'footer')
    {{-- Rendered directly by layout.blade.php in navbar / footer --}}
@else
<section class="af-section {{ ($s['bg_mode'] ?? '') === 'dark' ? 'af-section--dark' : '' }} {{ ($s['bg_mode'] ?? '') === 'muted' ? 'af-section--muted' : '' }}" id="{{ $s['html_id'] ?? '' }}" style="@if($secBg) background: {{ $secBg }} !important; @endif @if($secText) color: {{ $secText }} !important; @endif">
    <div class="af-container">
        <div data-vue-component="LanguageSwitcher" data-props="{{ json_encode(['content' => $c, 'settings' => $s]) }}"></div>
    </div>
</section>
@endif
