<?php
/**
 * verify_portal.php
 * Verificação simples das modificações no ficheiro da skill
 */

$skillPath = __DIR__ . '/../skills/themes/aerospace_theme_skill.md';
$raw = file_get_contents($skillPath);

echo "=== Verificação Portal Transition ===\n\n";
echo "Tamanho do ficheiro: " . number_format(strlen($raw)) . " bytes\n\n";

$checks = [
    '@php block (isHome)'           => '$isHome',
    '@php block (effectiveMenuLayout)' => 'effectiveMenuLayout',
    '@php block (effectiveMenuPosition)' => 'effectiveMenuPosition',
    'Navbar usa effectiveMenuPosition' => 'pos-{{ $effectiveMenuPosition }}',
    'Navbar portal-active'          => 'portal-active',
    'Circular wrapper usa showCircularMenu' => '!$showCircularMenu',
    'portal-link class nos sat-nodes' => 'portal-link',
    'data-portal-href'              => 'data-portal-href',
    '@keyframes portalExit'         => '@keyframes portalExit',
    '@keyframes portalEntry'        => '@keyframes portalEntry',
    '@keyframes navbarPortalEntry'  => '@keyframes navbarPortalEntry',
    '@keyframes circularWrapperExit'=> '@keyframes circularWrapperExit',
    'body.portal-exit CSS'          => 'body.portal-exit {',
    'body.portal-entry CSS'         => 'body.portal-entry {',
    '.normal-navbar.portal-active CSS' => '.normal-navbar.portal-active {',
    'função portalNavigate()'       => 'function portalNavigate(',
    'portal-exit no body (JS)'      => "classList.add('portal-exit')",
    'portal-entry no body (JS)'     => "classList.add('portal-entry')",
    'intercept .portal-link (JS)'   => "querySelectorAll('.portal-link')",
];

$pass = 0;
$fail = 0;
foreach ($checks as $label => $needle) {
    if (strpos($raw, $needle) !== false) {
        echo "  ✅ $label\n";
        $pass++;
    } else {
        echo "  ❌ $label  (procurado: \"$needle\")\n";
        $fail++;
    }
}

echo "\nResultado: $pass/" . ($pass + $fail) . " verificações passaram\n";

// Contar quantos portal-link existem
$count = substr_count($raw, 'portal-link');
echo "   Total de ocorrências de 'portal-link': $count\n";
$countDataPortal = substr_count($raw, 'data-portal-href');
echo "   Total de ocorrências de 'data-portal-href': $countDataPortal\n";
