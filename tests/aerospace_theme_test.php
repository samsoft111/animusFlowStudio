<?php

declare(strict_types=1);

/**
 * Teste de integridade do tema AeroSpace — AnimusFlowStudio
 *
 * Protege o tema demo "AeroSpace" (StudioTheme) contra regressões nos próximos
 * fixes: garante que continua publicado, com as 13 secções, CSS/JS substanciais,
 * design system completo, media referenciada existente em disco, e que o skill
 * `skills/themes/aerospace_theme_skill.md` se mantém em sincronia com a BD.
 *
 * Execução: php tests/aerospace_theme_test.php
 *
 * NOTA: corre sobre a BD real — requer ai_settings_guard.php (preserva as chaves
 * de IA reais). Só LÊ o tema; nunca o altera.
 */

define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
require __DIR__ . '/ai_settings_guard.php';

use App\Models\StudioTheme;

$passed = 0;
$failed = 0;
function check(string $label, bool $ok): void
{
    global $passed, $failed;
    if ($ok) { echo "  ✅ {$label}\n"; $passed++; }
    else      { echo "  ❌ {$label}\n"; $failed++; }
}

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 1: Tema na BD' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

$theme = StudioTheme::where('label', 'AeroSpace')->first();
check('Tema "AeroSpace" existe na BD', $theme !== null);

if (!$theme) {
    echo "\n❌ Sem tema — não há mais nada a testar.\n";
    exit(1);
}

check('Está publicado (status=published)', $theme->status === 'published');
check('Tem versão definida', !empty($theme->version));

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 2: Secções' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

$expectedSections = [
    'cta', 'map', 'hero', 'team', 'text', 'about', 'stats',
    'steps', 'footer', 'contact', 'gallery', 'features', 'testimonials',
];
$sections = is_array($theme->sections) ? $theme->sections : [];
check('sections é um array', is_array($theme->sections));
check('Tem as 13 secções esperadas', count(array_intersect($expectedSections, array_keys($sections))) === count($expectedSections));

foreach ($expectedSections as $key) {
    $html = $sections[$key] ?? '';
    check("Secção '{$key}' não-vazia e é HTML", is_string($html) && strlen(trim($html)) > 0 && str_contains($html, '<'));
}

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 3: CSS / JS / Design system' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

check('custom_css substancial (>1KB)', strlen($theme->custom_css ?? '') > 1000);
check('custom_js substancial (>1KB)', strlen($theme->custom_js ?? '') > 1000);

$colors = is_array($theme->colors) ? $theme->colors : [];
check('colors tem light e dark', isset($colors['light']) && isset($colors['dark']));
foreach (['light', 'dark'] as $mode) {
    $c = $colors[$mode] ?? [];
    check("colors.{$mode} tem tokens base (primary/background/accent)",
        isset($c['--color-primary'], $c['--color-background'], $c['--color-accent']));
}

$fonts = is_array($theme->fonts) ? $theme->fonts : [];
check('fonts tem heading e body', !empty($fonts['heading']) && !empty($fonts['body']));

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 4: layout_config' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

$lc = is_array($theme->layout_config) ? $theme->layout_config : [];
check('menu_layout = circular', ($lc['menu_layout'] ?? null) === 'circular');
check('nav_links é array com >=3 entradas', is_array($lc['nav_links'] ?? null) && count($lc['nav_links']) >= 3);
check('gallery_layout definido', !empty($lc['gallery_layout']));
check('hud_bg_video definido', !empty($lc['hud_bg_video']));

// nav_links devem usar caminhos relativos (não âncoras de single-page)
$anchorLinks = array_filter($lc['nav_links'] ?? [], fn($l) => str_starts_with($l['url'] ?? '', '#'));
check('nav_links não usam âncoras locais (#...)', count($anchorLinks) === 0);

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 5: Media referenciada existe em disco' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

$mediaRefs = array_filter(array_merge(
    [$lc['hud_bg_single_photo'] ?? null, $lc['hud_bg_video'] ?? null],
    is_array($lc['hud_bg_gallery'] ?? null) ? $lc['hud_bg_gallery'] : []
));
foreach ($mediaRefs as $ref) {
    $rel  = ltrim((string) $ref, '/');
    $path = public_path($rel);
    check("media existe: /{$rel}", is_file($path));
}

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 6: Skill .md em sincronia com a BD' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

$skillPath = __DIR__ . '/../skills/themes/aerospace_theme_skill.md';
check('Skill aerospace_theme_skill.md existe', is_file($skillPath));

if (is_file($skillPath)) {
    $raw = file_get_contents($skillPath);
    check('Skill tem bloco ```json_updates', str_contains($raw, '```json_updates'));

    $start = strpos($raw, '```json_updates');
    $body  = $start !== false ? substr($raw, $start + strlen('```json_updates')) : '';
    $end   = strpos($body, '```');
    $json  = $end !== false ? substr($body, 0, $end) : $body;
    $data  = json_decode(trim($json), true);

    check('Bloco json_updates é JSON válido', is_array($data));
    if (is_array($data)) {
        check('Skill label == BD label', ($data['label'] ?? null) === $theme->label);
        check('Skill version == BD version', ($data['version'] ?? null) === $theme->version);
        check('Skill status == BD status', ($data['status'] ?? null) === $theme->status);
        $skillSecs = array_keys($data['sections'] ?? []);
        sort($skillSecs);
        $dbSecs = array_keys($sections);
        sort($dbSecs);
        check('Skill e BD têm as mesmas secções', $skillSecs === $dbSecs);
    }
}

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 7: Editar o vídeo/fundo HUD no editor' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

$ctrlSrc = file_get_contents(__DIR__ . '/../app/Http/Controllers/ThemeController.php');
foreach (['hud_bg_video', 'hud_bg_photo', 'hud_gallery_1'] as $slot) {
    check("uploadAsset permite slot '{$slot}'", str_contains($ctrlSrc, "'{$slot}'"));
}

$editSrc = file_get_contents(__DIR__ . '/../resources/js/Pages/Themes/Edit.vue');
check('Assets tem o cartão "Fundo HUD / Screensaver"', str_contains($editSrc, 'Fundo HUD / Screensaver'));
check('Layout tem o cartão "Fundo do HUD / Screensaver"', str_contains($editSrc, 'Fundo do HUD / Screensaver'));
check('Selector de modo tem as 3 opções (video/photo/gallery)',
    str_contains($editSrc, 'value="video"') && str_contains($editSrc, 'value="photo"') && str_contains($editSrc, 'value="gallery"'));
check('Editor liga o selector hud_bg_type', str_contains($editSrc, 'form.layout_config.hud_bg_type'));
check('Editor liga o vídeo hud_bg_video', str_contains($editSrc, 'form.layout_config.hud_bg_video'));
check('Editor tem handler handleHudUpload', str_contains($editSrc, 'handleHudUpload'));
check('Editor tem handler handleHudDelete', str_contains($editSrc, 'handleHudDelete'));

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 8: Definições do site (theme_settings)' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

check('Coluna theme_settings existe', \Illuminate\Support\Facades\Schema::hasColumn('studio_themes', 'theme_settings'));

$ts = is_array($theme->theme_settings) ? $theme->theme_settings : [];
check('theme_settings é array não-vazio', is_array($theme->theme_settings) && count($ts) > 0);

$expectedGroups = ['geral', 'cabecalho', 'menus', 'cores', 'tipografia', 'fundo', 'layout', 'rodape', 'funcionalidades'];
$presentGroups  = array_unique(array_column($ts, 'group'));
check('Todos os 9 grupos estão cobertos', count(array_intersect($expectedGroups, $presentGroups)) === count($expectedGroups));

$validTypes = ['text', 'textarea', 'number', 'range', 'toggle', 'color', 'select', 'media_image', 'media_video', 'media_gallery'];
$allWellFormed = true;
foreach ($ts as $field) {
    if (!isset($field['key'], $field['label'], $field['type'], $field['group']) || !in_array($field['type'], $validTypes, true)) {
        $allWellFormed = false;
        break;
    }
}
check('Todos os campos têm key/label/type/group válidos', $allWellFormed);

// Campos-chave do fundo HUD presentes no schema
$keys = array_column($ts, 'key');
check('Schema inclui hud_bg_type', in_array('hud_bg_type', $keys, true));
check('Schema inclui hud_bg_video (tipo media_video)',
    (bool) array_filter($ts, fn($f) => ($f['key'] ?? '') === 'hud_bg_video' && ($f['type'] ?? '') === 'media_video'));
check('Schema inclui galeria (tipo media_gallery)',
    (bool) array_filter($ts, fn($f) => ($f['type'] ?? '') === 'media_gallery'));
check('Schema inclui cores (source color_light/color_dark)',
    (bool) array_filter($ts, fn($f) => in_array($f['source'] ?? '', ['color_light', 'color_dark'], true)));
check('Schema inclui tipografia (source font)',
    (bool) array_filter($ts, fn($f) => ($f['source'] ?? '') === 'font'));

// Selects têm options
$selectsOk = true;
foreach ($ts as $field) {
    if (($field['type'] ?? '') === 'select' && empty($field['options'])) { $selectsOk = false; break; }
}
check('Todos os campos select têm options', $selectsOk);

// Controlador: update valida + export inclui settings
$ctrlSrc2 = $ctrlSrc ?? file_get_contents(__DIR__ . '/../app/Http/Controllers/ThemeController.php');
check('update() valida theme_settings', str_contains($ctrlSrc2, "'theme_settings' => 'nullable|array'"));
check('buildThemeZip exporta "settings" no theme.json', str_contains($ctrlSrc2, "'settings'      => \$theme->theme_settings"));

// Editor: tab + helpers
$editSrc2 = $editSrc ?? file_get_contents(__DIR__ . '/../resources/js/Pages/Themes/Edit.vue');
check('Editor tem a tab "Definições do site"', str_contains($editSrc2, "label: 'Definições do site'"));
check('Editor inicializa form.theme_settings', str_contains($editSrc2, 'theme_settings: JSON.parse'));
check('Editor tem addSetting/removeSetting', str_contains($editSrc2, 'function addSetting') && str_contains($editSrc2, 'function removeSetting'));
check('Editor tem parser de opções', str_contains($editSrc2, 'function textToOptions'));

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 9: Repor definições recomendadas' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

check('Serviço ThemeSettingsRecommender existe', class_exists(\App\Support\ThemeSettingsRecommender::class));
$reco = \App\Support\ThemeSettingsRecommender::recommend($theme);
check('recommend() devolve array não-vazio', is_array($reco) && count($reco) > 0);
check('recommend() cobre os 9 grupos',
    count(array_intersect($expectedGroups, array_unique(array_column($reco, 'group')))) === count($expectedGroups));
check('Seed e recommend() produzem o mesmo nº de campos', count($reco) === count($ts));

check('Rota themes.settings.recommend existe', \Illuminate\Support\Facades\Route::has('themes.settings.recommend'));
check('Controlador tem recommendSettings()', method_exists(\App\Http\Controllers\ThemeController::class, 'recommendSettings'));

$editSrc3 = $editSrc ?? file_get_contents(__DIR__ . '/../resources/js/Pages/Themes/Edit.vue');
check('Editor tem o botão "Repor definições recomendadas"', str_contains($editSrc3, 'Repor definições recomendadas'));
check('Editor tem o handler resetRecommendedSettings', str_contains($editSrc3, 'function resetRecommendedSettings'));
check('Botão chama a rota settings/recommend', str_contains($editSrc3, '/settings/recommend'));

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo "RESULTADO: {$passed} passou · {$failed} falhou" . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

exit($failed === 0 ? 0 : 1);
