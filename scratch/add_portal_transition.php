<?php
/**
 * add_portal_transition.php
 * Adiciona o efeito "Portal" de transição de página + menu normal automático
 * em páginas internas ao tema AeroSpace (aerospace_theme_skill.md).
 *
 * NOTA: Este script (v1) foi substituído pelo portal_transition_v2.php que
 * usa uma abordagem mais robusta (parse JSON). As alterações já estão aplicadas.
 * Este ficheiro foi corrigido apenas para fins de documentação/referência.
 *
 * Problema original: usar \\'  numa string PHP de aspas simples termina a string
 * prematuramente:  '...\\'...'  →  '...\\'  (termina aqui) + '...' (erro de sintaxe)
 *
 * Solução: usar heredoc/nowdoc para strings que contêm aspas simples e barras.
 *
 * Uso: php scratch/add_portal_transition.php
 */

$skillPath = __DIR__ . '/../skills/themes/aerospace_theme_skill.md';

if (!file_exists($skillPath)) {
    echo "❌ Ficheiro não encontrado: $skillPath\n";
    exit(1);
}

$content = file_get_contents($skillPath);
$original = $content;

// ═══════════════════════════════════════════════════════════════════════
// 1. HERO HTML — Adicionar detecção @php + atualizar classes do navbar
//    e circular-menu-wrapper
// ═══════════════════════════════════════════════════════════════════════

// 1a. Bloco @php a injetar antes do normal-navbar header
// CORREÇÃO: usar NOWDOC (<<<'EOT') em vez de strings com aspas simples
// para evitar o problema \\'  → \\ + ' (termina a string)
$newPhpBlock = <<<'NOWDOC'
@php
      $isHome = request()->is('/') || request()->routeIs('home');
      $menuLayoutCfg = $theme->layout_config['menu_layout'] ?? 'circular';
      $effectiveMenuLayout = ($menuLayoutCfg === 'circular' && !$isHome) ? 'normal' : $menuLayoutCfg;
      $effectiveMenuPosition = ($menuLayoutCfg === 'circular' && !$isHome) ? 'horizontal-left' : ($layout['normal_menu_position'] ?? 'horizontal-right');
      $showNormalMenu = ($effectiveMenuLayout === 'normal');
      $showCircularMenu = ($effectiveMenuLayout === 'circular');
    @endphp
    
NOWDOC;

$newHeader = $newPhpBlock
    . '<header class=\"normal-navbar pos-{{ $effectiveMenuPosition }}'
    . ' @if(!$showNormalMenu) hidden-navbar @endif'
    . ' @if(!$isHome && $menuLayoutCfg === \'circular\') portal-active @endif\">';

// Procurar a tag do header actual e substituir
$pos = strpos($content, 'class=\"normal-navbar pos-');
if ($pos !== false) {
    $startOfTag = strrpos(substr($content, 0, $pos), '<header');
    $endOfTag   = strpos($content, '>', $pos) + 1;
    $oldTagFull = substr($content, $startOfTag, $endOfTag - $startOfTag);
    $content    = substr_replace($content, $newHeader, $startOfTag, strlen($oldTagFull));
    echo "✅ 1a. Header normal-navbar atualizado\n";
} else {
    echo "❌ 1a. Não foi possível encontrar o header\n";
}

// 1b. Atualizar circular-menu-wrapper
$newCircular = '<div class=\"circular-menu-wrapper @if(!$showCircularMenu) hidden-menu @endif\">';
$pos2 = strpos($content, 'class=\"circular-menu-wrapper');
if ($pos2 !== false) {
    $startOfDiv = strrpos(substr($content, 0, $pos2), '<div');
    $endOfDiv   = strpos($content, '>', $pos2) + 1;
    $content    = substr_replace($content, $newCircular, $startOfDiv, $endOfDiv - $startOfDiv);
    echo "✅ 1b. Circular menu wrapper atualizado\n";
} else {
    echo "❌ 1b. Não foi possível encontrar circular-menu-wrapper\n";
}

// 1c. Adicionar classe portal-link + data-portal-href aos sat-node links
$content = preg_replace_callback(
    '/<a href="([^"]+)" class="sat-node" onmouseenter="playHoverChirp\(\)">/u',
    function ($matches) {
        $href = $matches[1];
        return "<a href=\"{$href}\" class=\"sat-node portal-link\" onmouseenter=\"playHoverChirp()\" data-portal-href=\"{$href}\">";
    },
    $content
);
$content = preg_replace_callback(
    '/<a href="([^"]+)" class="(sub-node [^"]+)" onmouseenter="playHoverChirp\(\)">/u',
    function ($matches) {
        $href = $matches[1];
        $cls  = $matches[2];
        return "<a href=\"{$href}\" class=\"{$cls} portal-link\" onmouseenter=\"playHoverChirp()\" data-portal-href=\"{$href}\">";
    },
    $content
);
echo "✅ 1c. Classes portal-link adicionadas\n";

// ═══════════════════════════════════════════════════════════════════════
// 2. CUSTOM_CSS — Adicionar animações de portal
// CORREÇÃO: usar NOWDOC para o bloco de CSS multi-linha
// ═══════════════════════════════════════════════════════════════════════

// NOWDOC preserva o conteúdo literalmente (sem expansão de variáveis nem escapes)
$portalCss = <<<'NOWDOC'


/* ── Portal Page Transition Effects ── */
@keyframes portalExit {
  0%   { transform: scale(1) translateZ(0); filter: blur(0px); opacity: 1; }
  25%  { transform: scale(1.06) translateZ(0); filter: blur(1px); opacity: 0.95; }
  100% { transform: scale(3.2) translateZ(0); filter: blur(24px); opacity: 0; }
}
@keyframes portalEntry {
  0%   { transform: scale(1.15) translateZ(0); filter: blur(16px); opacity: 0; }
  55%  { transform: scale(1.02) translateZ(0); filter: blur(4px); opacity: 0.75; }
  100% { transform: scale(1) translateZ(0); filter: blur(0px); opacity: 1; }
}
@keyframes navbarPortalEntry {
  0%   { opacity: 0; transform: translateY(-32px) scale(0.94); filter: blur(8px); }
  65%  { opacity: 0.9; transform: translateY(5px) scale(1.01); filter: blur(1px); }
  100% { opacity: 1; transform: translateY(0) scale(1); filter: blur(0px); }
}
@keyframes circularWrapperExit {
  0%   { transform: scale(1); opacity: 1; filter: blur(0px); }
  40%  { transform: scale(1.1); opacity: 0.9; filter: blur(2px); }
  100% { transform: scale(4); opacity: 0; filter: blur(20px); }
}
body.portal-exit {
  animation: portalExit 0.52s cubic-bezier(0.55, 0, 1, 0.45) forwards;
  pointer-events: none;
  overflow: hidden;
}
body.portal-exit .circular-menu-wrapper {
  animation: circularWrapperExit 0.52s cubic-bezier(0.55, 0, 1, 0.45) forwards;
}
body.portal-entry {
  animation: portalEntry 0.55s cubic-bezier(0, 0, 0.25, 1) forwards;
}
.normal-navbar.portal-active {
  opacity: 0;
  animation: navbarPortalEntry 0.5s cubic-bezier(0, 0, 0.2, 1) 0.28s forwards;
}

NOWDOC;

$cssMarker = '/* Mobile responsive adjustments */';
if (strpos($content, $cssMarker) !== false) {
    $content = str_replace($cssMarker, $portalCss . "\n" . $cssMarker, $content);
    echo "✅ 2. CSS do portal adicionado\n";
} else {
    $content .= $portalCss;
    echo "✅ 2. (fallback) CSS do portal adicionado no final\n";
}

// ═══════════════════════════════════════════════════════════════════════
// 3. CUSTOM_JS — Interceptar cliques + portal entry
// CORREÇÃO: usar NOWDOC para o bloco de JS multi-linha
// ═══════════════════════════════════════════════════════════════════════

$portalJs = <<<'NOWDOC'

  // ── Portal Page Transition — Intercept circular menu link clicks ──
  function portalNavigate(href) {
    if (!href || href === '#' || href === window.location.pathname) return;
    document.body.classList.add('portal-exit');
    playSynthSound(260, 'sawtooth', 0.55, 0.06);
    setTimeout(() => playSynthSound(540, 'sine', 0.3, 0.04), 90);
    setTimeout(() => playSynthSound(1100, 'sine', 0.12, 0.025), 210);
    setTimeout(() => { window.location.href = href; }, 490);
  }

  document.querySelectorAll('.portal-link').forEach(link => {
    link.addEventListener('click', function(e) {
      const href = this.getAttribute('data-portal-href') || this.getAttribute('href');
      if (this.getAttribute('target') === '_blank' || !href || href.startsWith('#')) return;
      e.preventDefault();
      portalNavigate(href);
    });
  });

  const portalNavbar = document.querySelector('.normal-navbar.portal-active');
  if (portalNavbar) {
    requestAnimationFrame(() => {
      document.body.classList.add('portal-entry');
    });
  }

NOWDOC;

$jsMarker = '// ── Mobile Menu Toggler ──';
if (strpos($content, $jsMarker) !== false) {
    $content = str_replace($jsMarker, $portalJs . "\n  " . $jsMarker, $content);
    echo "✅ 3. JS do portal adicionado\n";
} else {
    echo "❌ 3. Marcador JS não encontrado — verificar portal_transition_v2.php\n";
}

// ═══════════════════════════════════════════════════════════════════════
// 4. Guardar
// ═══════════════════════════════════════════════════════════════════════

if ($content === $original) {
    echo "\n⚠️  AVISO: Ficheiro não modificado (as alterações já foram aplicadas pelo v2).\n";
    exit(0);
}

file_put_contents($skillPath, $content);
echo "\n✅ Ficheiro guardado: " . number_format(filesize($skillPath)) . " bytes\n";
