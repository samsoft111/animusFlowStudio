<?php
/**
 * portal_transition_v2.php
 * Abordagem robusta: parseia o JSON do skill e modifica as secções
 * de forma programática e segura.
 */

$skillPath = __DIR__ . '/../skills/themes/aerospace_theme_skill.md';

if (!file_exists($skillPath)) {
    echo "❌ Ficheiro não encontrado: $skillPath\n";
    exit(1);
}

$raw = file_get_contents($skillPath);

// Extrair o bloco json_updates
if (!preg_match('/```json_updates\s*([\s\S]+?)\s*```/', $raw, $m)) {
    echo "❌ Bloco json_updates não encontrado\n";
    exit(1);
}

$jsonStr = $m[1];
$data = json_decode($jsonStr, true);
if (!$data) {
    echo "❌ JSON inválido: " . json_last_error_msg() . "\n";
    exit(1);
}

echo "✅ JSON parseado com sucesso\n";

// ═══════════════════════════════════════════════════════════════════════
// 1. MODIFICAR HERO HTML
// ═══════════════════════════════════════════════════════════════════════

$heroHtml = $data['sections']['hero'];

// 1a. Adicionar bloco @php de detecção ANTES do header normal-navbar
// Encontrar a tag do normal-navbar header e injetar @php antes
$phpBlock = <<<'BLADE'
@php
      $isHome = request()->is('/') || request()->routeIs('home');
      $menuLayoutCfg = $theme->layout_config['menu_layout'] ?? 'circular';
      $effectiveMenuLayout = ($menuLayoutCfg === 'circular' && !$isHome) ? 'normal' : $menuLayoutCfg;
      $effectiveMenuPosition = ($menuLayoutCfg === 'circular' && !$isHome) ? 'horizontal-left' : ($layout['normal_menu_position'] ?? 'horizontal-right');
      $showNormalMenu = ($effectiveMenuLayout === 'normal');
      $showCircularMenu = ($effectiveMenuLayout === 'circular');
    @endphp
    
BLADE;

// Substituir o header do normal-navbar
$oldHeader = '<header class="normal-navbar pos-{{ $layout[\'normal_menu_position\'] ?? \'horizontal-right\' }} @if(($theme->layout_config[\'menu_layout\'] ?? \'circular\') !== \'normal\') hidden-navbar @endif">';
$newHeader = $phpBlock . '<header class="normal-navbar pos-{{ $effectiveMenuPosition }} @if(!$showNormalMenu) hidden-navbar @endif @if(!$isHome && $menuLayoutCfg === \'circular\') portal-active @endif">';

if (strpos($heroHtml, $oldHeader) !== false) {
    $heroHtml = str_replace($oldHeader, $newHeader, $heroHtml);
    echo "✅ 1a. Normal navbar header atualizado\n";
} else {
    // Try searching without quotes nuance
    $pos = strpos($heroHtml, 'class="normal-navbar pos-');
    if ($pos !== false) {
        // Find the end of this tag
        $startOfTag = strrpos(substr($heroHtml, 0, $pos), '<header');
        $endOfTag = strpos($heroHtml, '>', $pos) + 1;
        $oldTagFull = substr($heroHtml, $startOfTag, $endOfTag - $startOfTag);
        $heroHtml = substr_replace($heroHtml, $phpBlock . '<header class="normal-navbar pos-{{ $effectiveMenuPosition }} @if(!$showNormalMenu) hidden-navbar @endif @if(!$isHome && $menuLayoutCfg === \'circular\') portal-active @endif">', $startOfTag, strlen($oldTagFull));
        echo "✅ 1a. (fallback position search) Normal navbar header atualizado\n";
    } else {
        echo "❌ 1a. Não foi possível encontrar o header\n";
    }
}

// 1b. Atualizar circular-menu-wrapper
$oldCircular = '<div class="circular-menu-wrapper @if(($theme->layout_config[\'menu_layout\'] ?? \'circular\') === \'normal\') hidden-menu @endif">';
$newCircular = '<div class="circular-menu-wrapper @if(!$showCircularMenu) hidden-menu @endif">';

if (strpos($heroHtml, $oldCircular) !== false) {
    $heroHtml = str_replace($oldCircular, $newCircular, $heroHtml);
    echo "✅ 1b. Circular menu wrapper atualizado\n";
} else {
    $pos2 = strpos($heroHtml, 'class="circular-menu-wrapper');
    if ($pos2 !== false) {
        $startOfDiv = strrpos(substr($heroHtml, 0, $pos2), '<div');
        $endOfDiv = strpos($heroHtml, '>', $pos2) + 1;
        $heroHtml = substr_replace($heroHtml, $newCircular, $startOfDiv, $endOfDiv - $startOfDiv);
        echo "✅ 1b. (fallback) Circular menu wrapper atualizado\n";
    } else {
        echo "❌ 1b. Não foi possível encontrar circular-menu-wrapper\n";
    }
}

// 1c. Adicionar classe portal-link + data-portal-href aos sat-node e sub-node links
// Procurar todos os <a href="..." class="sat-node" ...> e adicionar portal-link
$heroHtml = preg_replace_callback(
    '/<a href="([^"]+)" class="sat-node" onmouseenter="playHoverChirp\(\)">/u',
    function($matches) {
        $href = $matches[1];
        return "<a href=\"{$href}\" class=\"sat-node portal-link\" onmouseenter=\"playHoverChirp()\" data-portal-href=\"{$href}\">";
    },
    $heroHtml
);

// sub-nodes
$heroHtml = preg_replace_callback(
    '/<a href="([^"]+)" class="(sub-node [^"]+)" onmouseenter="playHoverChirp\(\)">/u',
    function($matches) {
        $href = $matches[1];
        $cls = $matches[2];
        return "<a href=\"{$href}\" class=\"{$cls} portal-link\" onmouseenter=\"playHoverChirp()\" data-portal-href=\"{$href}\">";
    },
    $heroHtml
);

echo "✅ 1c. Classes portal-link adicionadas aos nós do menu circular\n";

$data['sections']['hero'] = $heroHtml;

// ═══════════════════════════════════════════════════════════════════════
// 2. ADICIONAR CSS DO PORTAL
// ═══════════════════════════════════════════════════════════════════════

$portalCss = <<<'CSS'


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
@keyframes portalFlash {
  0%   { opacity: 0; }
  30%  { opacity: 1; }
  100% { opacity: 0; }
}

/* Portal Exit — applied to body on circular menu click */
body.portal-exit {
  animation: portalExit 0.52s cubic-bezier(0.55, 0, 1, 0.45) forwards;
  pointer-events: none;
  overflow: hidden;
}
body.portal-exit .circular-menu-wrapper {
  animation: circularWrapperExit 0.52s cubic-bezier(0.55, 0, 1, 0.45) forwards;
}
body.portal-exit::before {
  content: '';
  position: fixed;
  inset: 0;
  z-index: 99999;
  background: radial-gradient(ellipse at center,
    rgba(6, 182, 212, 0.45) 0%,
    rgba(37, 99, 235, 0.28) 35%,
    rgba(7, 12, 24, 0.6) 65%,
    transparent 100%
  );
  pointer-events: none;
  animation: portalFlash 0.52s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

/* Portal Entry — applied to body on page load (by JS) */
body.portal-entry {
  animation: portalEntry 0.55s cubic-bezier(0, 0, 0.25, 1) forwards;
}

/* Normal navbar on internal pages — slide in with delay */
.normal-navbar.portal-active {
  opacity: 0;
  animation: navbarPortalEntry 0.5s cubic-bezier(0, 0, 0.2, 1) 0.28s forwards;
}

CSS;

// Inserir o CSS antes do bloco "/* Mobile responsive adjustments */"
$cssMarker = '/* Mobile responsive adjustments */';
if (strpos($data['custom_css'], $cssMarker) !== false) {
    $data['custom_css'] = str_replace(
        $cssMarker,
        $portalCss . "\n" . $cssMarker,
        $data['custom_css']
    );
    echo "✅ 2. CSS do portal adicionado\n";
} else {
    // Fallback: adicionar no final
    $data['custom_css'] .= $portalCss;
    echo "✅ 2. (fallback) CSS do portal adicionado no final\n";
}

// ═══════════════════════════════════════════════════════════════════════
// 3. ADICIONAR JS DO PORTAL
// ═══════════════════════════════════════════════════════════════════════

$portalJs = <<<'JS'

  // ── Portal Page Transition — Intercept circular menu link clicks ──
  function portalNavigate(href) {
    if (!href || href === '#' || href === window.location.pathname) return;
    document.body.classList.add('portal-exit');
    // Layered synth launch audio
    playSynthSound(260, 'sawtooth', 0.55, 0.06);
    setTimeout(() => playSynthSound(540, 'sine', 0.3, 0.04), 90);
    setTimeout(() => playSynthSound(1100, 'sine', 0.12, 0.025), 210);
    setTimeout(() => { window.location.href = href; }, 490);
  }

  // Attach portal transition to all .portal-link elements
  document.querySelectorAll('.portal-link').forEach(link => {
    link.addEventListener('click', function(e) {
      const href = this.getAttribute('data-portal-href') || this.getAttribute('href');
      // Skip external links (target="_blank") and pure anchors
      if (this.getAttribute('target') === '_blank' || !href || href.startsWith('#')) return;
      e.preventDefault();
      portalNavigate(href);
    });
  });

  // ── Portal Entry — trigger body animation on internal pages ──
  // .portal-active on navbar is set by Blade when on internal page + circular layout
  const portalNavbar = document.querySelector('.normal-navbar.portal-active');
  if (portalNavbar) {
    // Trigger portal entry body animation (CSS handles the rest)
    requestAnimationFrame(() => {
      document.body.classList.add('portal-entry');
    });
  }

JS;

// Inserir ANTES do bloco de Mobile Menu Toggler
$jsMarker = '// ── Mobile Menu Toggler ──';
if (strpos($data['custom_js'], $jsMarker) !== false) {
    $data['custom_js'] = str_replace(
        $jsMarker,
        $portalJs . "\n  " . $jsMarker,
        $data['custom_js']
    );
    echo "✅ 3. JS do portal adicionado\n";
} else {
    // Fallback: inserir antes do fecho do DOMContentLoaded
    $jsMarker2 = '});' . "\n\n" . '// ── Synthesized Sound FX';
    if (strpos($data['custom_js'], $jsMarker2) !== false) {
        $data['custom_js'] = str_replace(
            $jsMarker2,
            $portalJs . "\n});\n\n// ── Synthesized Sound FX",
            $data['custom_js']
        );
        echo "✅ 3. (fallback) JS do portal adicionado\n";
    } else {
        echo "❌ 3. Não foi possível inserir o JS\n";
    }
}

// ═══════════════════════════════════════════════════════════════════════
// 4. RESERIALIZAR E ESCREVER
// ═══════════════════════════════════════════════════════════════════════

$newJson = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if (!$newJson) {
    echo "❌ Falha ao serializar JSON: " . json_last_error_msg() . "\n";
    exit(1);
}

// Reconstruir o ficheiro skill completo
$preamble = substr($raw, 0, strpos($raw, '```json_updates'));
$epilogue  = '';
$endPos = strpos($raw, '```', strpos($raw, '```json_updates') + 3);
if ($endPos !== false) {
    $epilogue = substr($raw, $endPos + 3);
}

$newRaw = $preamble . "```json_updates\n" . $newJson . "\n```" . $epilogue;

// Fazer backup
$backupPath = $skillPath . '.bak_portal_' . date('Ymd_His');
file_put_contents($backupPath, $raw);
echo "\n📋 Backup criado: $backupPath\n";

file_put_contents($skillPath, $newRaw);
$newSize = filesize($skillPath);
echo "✅ Ficheiro guardado: $skillPath\n";
echo "   Tamanho original: " . number_format(strlen($raw)) . " bytes\n";
echo "   Tamanho novo:     " . number_format($newSize) . " bytes\n\n";
echo "Próximos passos:\n";
echo "  1. Verificar o ficheiro para confirmar as alterações\n";
echo "  2. php scratch/portal_transition_v2.php (se necessário re-executar)\n";
echo "  3. Rebuild do animusflow.skill\n";
echo "  4. Git commit + push\n";
