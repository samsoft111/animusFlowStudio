<?php
/**
 * add_portal_transition.php
 * Adiciona o efeito "Portal" de transição de página + menu normal automático
 * em páginas internas ao tema AeroSpace (aerospace_theme_skill.md).
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

// 1a. Adicionar bloco @php antes da classe do normal-navbar header
// Procuramos a string do normal-navbar e injetamos o @php antes dela
$oldNavbarOpener = '<header class=\\\"normal-navbar pos-{{ $layout[\'normal_menu_position\'] ?? \'horizontal-right\' }} @if(($theme->layout_config[\'menu_layout\'] ?? \'circular\') !== \'normal\') hidden-navbar @endif\\\">';
$newNavbarOpener = '@php\\n      $isHome = request()->is(\'/\') || request()->routeIs(\'home\');\\n      $menuLayoutCfg = $theme->layout_config[\'menu_layout\'] ?? \'circular\';\\n      $effectiveMenuLayout = ($menuLayoutCfg === \'circular\' && !$isHome) ? \'normal\' : $menuLayoutCfg;\\n      $effectiveMenuPosition = ($menuLayoutCfg === \'circular\' && !$isHome) ? \'horizontal-left\' : ($layout[\'normal_menu_position\'] ?? \'horizontal-right\');\\n      $showNormalMenu = ($effectiveMenuLayout === \'normal\');\\n      $showCircularMenu = ($effectiveMenuLayout === \'circular\');\\n    @endphp\\n    <header class=\\\"normal-navbar pos-{{ $effectiveMenuPosition }} @if(!$showNormalMenu) hidden-navbar @endif @if(!$isHome && $menuLayoutCfg === \'circular\') portal-active @endif\\\">';

if (strpos($content, $oldNavbarOpener) !== false) {
    $content = str_replace($oldNavbarOpener, $newNavbarOpener, $content);
    echo "✅ 1a. Header normal-navbar atualizado com deteção de página\n";
} else {
    echo "⚠️  1a. Alvo do header normal-navbar não encontrado — a tentar padrão alternativo...\n";
    // Fallback: procurar padrão mais simples
    $alt = 'pos-{{ $layout[\\\'normal_menu_position\\\'] ?? \\\'horizontal-right\\\' }}';
    if (strpos($content, $alt) !== false) {
        // Substituir toda a linha do header
        $content = preg_replace(
            '/<header class=\\\\"normal-navbar pos-\{\{ \\\$layout\[.+?\n/',
            $newNavbarOpener . "\n",
            $content
        );
        echo "✅ 1a. (fallback) Header normal-navbar atualizado\n";
    } else {
        echo "❌ 1a. Não foi possível encontrar o header — verificar manualmente\n";
    }
}

// 1b. Atualizar a div circular-menu-wrapper para usar $showCircularMenu
$oldCircularWrapper = '<div class=\\\"circular-menu-wrapper @if(($theme->layout_config[\'menu_layout\'] ?? \'circular\') === \'normal\') hidden-menu @endif\\\">';
$newCircularWrapper = '<div class=\\\"circular-menu-wrapper @if(!$showCircularMenu) hidden-menu @endif\\\">';

if (strpos($content, $oldCircularWrapper) !== false) {
    $content = str_replace($oldCircularWrapper, $newCircularWrapper, $content);
    echo "✅ 1b. Circular-menu-wrapper atualizado\n";
} else {
    echo "⚠️  1b. Circular-menu-wrapper alvo não encontrado — a tentar regex...\n";
    $newContent = preg_replace(
        '/<div class=\\\\"circular-menu-wrapper @if\(\(\\\$theme->layout_config\[.+?\) hidden-menu @endif\\\\">/s',
        $newCircularWrapper,
        $content
    );
    if ($newContent !== null && $newContent !== $content) {
        $content = $newContent;
        echo "✅ 1b. (regex) Circular-menu-wrapper atualizado\n";
    } else {
        echo "❌ 1b. Não foi possível encontrar circular-menu-wrapper — verificar manualmente\n";
    }
}

// 1c. Adicionar data-portal-href e classe portal-link a todos os sat-node links
// Substituir: <a href=\"{{ url }}\" class=\"sat-node\" onmouseenter=\"playHoverChirp()\">
// Por: <a href=\"{{ url }}\" class=\"sat-node portal-link\" onmouseenter=\"playHoverChirp()\" data-portal-href=\"{{ url }}\">
$content = preg_replace(
    '/<a href=\\\\\"([^"]+)\\\\\" class=\\\\\"sat-node\\\\\" onmouseenter=\\\\\"playHoverChirp\(\)\\\\\">/u',
    '<a href=\"$1\" class=\"sat-node portal-link\" onmouseenter=\"playHoverChirp()\" data-portal-href=\"$1\">',
    $content
);

// Also for sub-nodes
$content = preg_replace(
    '/<a href=\\\\\"([^"]+)\\\\\" class=\\\\\"sub-node([^"]*?)\\\\\" onmouseenter=\\\\\"playHoverChirp\(\)\\\\\">/u',
    '<a href=\"$1\" class=\"sub-node$2 portal-link\" onmouseenter=\"playHoverChirp()\" data-portal-href=\"$1\">',
    $content
);

// Also for menu-hub-node (Home button) — NO portal on home link
$content = preg_replace(
    '/<a href=\\\\\"\/\\\\\" class=\\\\\"menu-hub-node\\\\\" onmouseenter=\\\\\"playHoverChirp\(\)\\\\\" onclick=\\\\\"playClickChirp\(\)\\\\\">/u',
    '<a href=\"/\" class=\"menu-hub-node\" onmouseenter=\"playHoverChirp()\" onclick=\"playClickChirp()\">',
    $content
);

echo "✅ 1c. Classes portal-link adicionadas aos nós do menu circular\n";

// ═══════════════════════════════════════════════════════════════════════
// 2. CUSTOM_CSS — Adicionar animações de portal e estilos
// ═══════════════════════════════════════════════════════════════════════

$portalCss = '\\n\\n/* ── Portal Page Transition Effects ── */\\n@keyframes portalExit {\\n  0%   { transform: scale(1) translateZ(0); filter: blur(0px); opacity: 1; }\\n  30%  { transform: scale(1.08) translateZ(0); filter: blur(2px); opacity: 0.9; }\\n  100% { transform: scale(2.8) translateZ(0); filter: blur(22px); opacity: 0; }\\n}\\n@keyframes portalEntry {\\n  0%   { transform: scale(1.12) translateZ(0); filter: blur(14px); opacity: 0; }\\n  60%  { transform: scale(1.02) translateZ(0); filter: blur(3px); opacity: 0.8; }\\n  100% { transform: scale(1) translateZ(0); filter: blur(0px); opacity: 1; }\\n}\\n@keyframes navbarPortalEntry {\\n  0%   { opacity: 0; transform: translateY(-28px) scale(0.95); }\\n  60%  { opacity: 0.85; transform: translateY(4px) scale(1.01); }\\n  100% { opacity: 1; transform: translateY(0) scale(1); }\\n}\\n@keyframes circularNodeExit {\\n  0%   { transform: scale(1); opacity: 1; }\\n  100% { transform: scale(3.5); opacity: 0; }\\n}\\nbody.portal-exit {\\n  animation: portalExit 0.52s cubic-bezier(0.4, 0, 1, 1) forwards;\\n  pointer-events: none;\\n  overflow: hidden;\\n}\\nbody.portal-exit .circular-menu-wrapper {\\n  animation: circularNodeExit 0.52s cubic-bezier(0.4, 0, 1, 1) forwards;\\n}\\nbody.portal-entry {\\n  animation: portalEntry 0.55s cubic-bezier(0, 0, 0.2, 1) forwards;\\n}\\n.normal-navbar.portal-active {\\n  opacity: 0;\\n  animation: navbarPortalEntry 0.5s cubic-bezier(0, 0, 0.2, 1) 0.3s forwards;\\n}\\n/* Portal overlay radial flash on exit */\\nbody.portal-exit::after {\\n  content: \'\';\\n  position: fixed;\\n  inset: 0;\\n  background: radial-gradient(ellipse at center, rgba(6, 182, 212, 0.35) 0%, rgba(37, 99, 235, 0.18) 40%, transparent 70%);\\n  pointer-events: none;\\n  z-index: 99999;\\n  animation: portalExit 0.52s cubic-bezier(0.4, 0, 1, 1) forwards;\\n}\\n';

// Inserir ANTES do fecho da string custom_css (antes do último \")
// Procuramos o último bloco de CSS relevante — as media queries mobile
$cssMarker = '/* Mobile responsive adjustments */';
if (strpos($content, $cssMarker) !== false) {
    $content = str_replace(
        $cssMarker,
        ltrim($portalCss, '\\n') . '\\n' . $cssMarker,
        $content
    );
    echo "✅ 2. CSS do portal adicionado antes dos media queries mobile\n";
} else {
    echo "⚠️  2. Marcador CSS não encontrado — a tentar inserção no final do CSS...\n";
    // Fallback: tentar inserir antes do fim do custom_css
    $content = str_replace(
        '/* ── Keyframes & Animations ── */',
        $portalCss . '\n/* ── Keyframes & Animations ── */',
        $content
    );
    echo "✅ 2. (fallback) CSS do portal adicionado\n";
}

// ═══════════════════════════════════════════════════════════════════════
// 3. CUSTOM_JS — Interceptar cliques dos nós circulares + portal entry
// ═══════════════════════════════════════════════════════════════════════

$portalJs = '\\n\\n  // ── Portal Page Transition — Intercept circular menu clicks ──\\n  function portalNavigate(href) {\\n    if (!href || href === \\'#\\' || href === window.location.href) return;\\n    // Don\\'t portal-transition to the same page\\n    document.body.classList.add(\\'portal-exit\\');\\n    playClickChirp();\\n    // Play a deep resonant launch sound\\n    playSynthSound(280, \\'sawtooth\\', 0.55, 0.06);\\n    setTimeout(() => { playSynthSound(520, \\'sine\\', 0.3, 0.04); }, 100);\\n    setTimeout(() => { playSynthSound(900, \\'sine\\', 0.15, 0.03); }, 220);\\n    setTimeout(() => {\\n      window.location.href = href;\\n    }, 480);\\n  }\\n\\n  // Attach portal navigation to all .portal-link elements (sat-nodes + sub-nodes)\\n  document.querySelectorAll(\\'.portal-link\\').forEach(link => {\\n    link.addEventListener(\\'click\\', function(e) {\\n      const href = this.getAttribute(\\'data-portal-href\\') || this.getAttribute(\\'href\\');\\n      // Skip if it\\'s an external link (opens in _blank) or anchor only\\n      if (this.getAttribute(\\'target\\') === \\'_blank\\' || !href || href.startsWith(\\'#\\')) return;\\n      e.preventDefault();\\n      portalNavigate(href);\\n    });\\n  });\\n\\n  // ── Portal Entry — animate navbar into view on internal pages ──\\n  const portalNavbar = document.querySelector(\\'.normal-navbar.portal-active\\');\\n  if (portalNavbar) {\\n    // Body starts portal-entry animation automatically via CSS class added by Blade\\n    // Force browser repaint before adding class to ensure animation plays\\n    requestAnimationFrame(() => {\\n      document.body.classList.add(\\'portal-entry\\');\\n    });\\n  }\\n';

// Inserir antes do bloco do Mobile Menu Toggler
$jsMarker = '// ── Mobile Menu Toggler ──';
if (strpos($content, $jsMarker) !== false) {
    $content = str_replace(
        $jsMarker,
        ltrim($portalJs, '\\n') . '\\n\\n  ' . $jsMarker,
        $content
    );
    echo "✅ 3. JS do portal adicionado antes do Mobile Menu Toggler\n";
} else {
    echo "⚠️  3. Marcador JS não encontrado — a tentar inserção alternativa...\n";
    $jsMarker2 = '});\\n\\n// ── Synthesized Sound FX';
    if (strpos($content, $jsMarker2) !== false) {
        $content = str_replace(
            $jsMarker2,
            $portalJs . '\\n});\\n\\n// ── Synthesized Sound FX',
            $content
        );
        echo "✅ 3. (fallback) JS do portal adicionado\n";
    } else {
        echo "❌ 3. Não foi possível inserir o JS — verificar manualmente\n";
    }
}

// ═══════════════════════════════════════════════════════════════════════
// 4. Verificação e escrita
// ═══════════════════════════════════════════════════════════════════════

if ($content === $original) {
    echo "\n⚠️  AVISO: O ficheiro não foi modificado. Verificar os padrões de procura.\n";
    exit(1);
}

file_put_contents($skillPath, $content);
$newSize = filesize($skillPath);
echo "\n✅ Ficheiro guardado com sucesso: $skillPath\n";
echo "   Tamanho: " . number_format($newSize) . " bytes\n\n";
echo "Próximos passos:\n";
echo "  1. Verificar o ficheiro manualmente para confirmar as alterações\n";
echo "  2. Rebuild do animusflow.skill\n";
echo "  3. Git commit + push\n";
