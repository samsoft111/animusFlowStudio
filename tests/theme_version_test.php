<?php
/**
 * Theme Versioning Test Suite — AnimusFlowStudio
 * Cobre: StudioThemeVersion::snapshot, ThemeController (listVersions, createVersion,
 *        restoreVersion, deleteVersion), auto-snapshot antes de restaurar,
 *        snapshot de publicação, rotas e UI do Themes/Edit.vue.
 * Execução: php tests/theme_version_test.php
 *
 * Paridade com tests/plugin_test.php (versionamento de plugins).
 */

define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
require __DIR__ . '/ai_settings_guard.php'; // preserva/restaura as chaves reais (cms_api_key, animusflow_api_key)

use App\Http\Controllers\ThemeController;
use App\Models\StudioTheme;
use App\Models\StudioThemeVersion;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

// ─── Contador ────────────────────────────────────────────────────────────────
$passed = 0; $failed = 0;
function check(string $label, bool $ok): void {
    global $passed, $failed;
    if ($ok) { echo "  ✅ {$label}\n"; $passed++; }
    else      { echo "  ❌ {$label}\n"; $failed++; }
}

// ─── Tema de teste ─────────────────────────────────────────────────────────────
$theme = StudioTheme::create([
    'name'          => 'test-version-theme-' . uniqid(),
    'label'         => 'Test Version Theme',
    'description'   => 'Tema de teste para versionamento',
    'version'       => '1.0.0',
    'colors'        => ['light' => ['--color-primary' => '#111111'], 'dark' => ['--color-primary' => '#eeeeee']],
    'fonts'         => ['heading' => 'Inter', 'body' => 'Inter'],
    'sections'      => ['hero' => '<section class="hero">v1</section>'],
    'layout_config' => ['header_type' => 'glass', 'max_width' => 1120],
    'capabilities'  => ['dark_mode' => true],
    'assets'        => [],
    'components'    => [],
    'variants'      => [],
    'custom_css'    => '.hero { color: #111; }',
    'custom_js'     => "console.log('v1');",
    'status'        => 'draft',
]);

$ctrl = new ThemeController();

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 1: StudioThemeVersion::snapshot — Modelo' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

check('Migration studio_theme_versions existe', Schema::hasTable('studio_theme_versions'));

$v1 = StudioThemeVersion::snapshot($theme, 'Versão inicial', 'manual');
check('snapshot() retorna instância',          $v1 instanceof StudioThemeVersion);
check('snapshot() gera uuid',                  !empty($v1->uuid));
check('snapshot() captura version 1.0.0',      $v1->version === '1.0.0');
check('snapshot() captura label',              $v1->label === 'Test Version Theme');
check('snapshot() guarda changelog',           $v1->changelog === 'Versão inicial');
check('snapshot() tipo = manual',              $v1->snapshot_type === 'manual');
check('snapshot() captura colors (array)',     ($v1->colors['light']['--color-primary'] ?? null) === '#111111');
check('snapshot() captura sections',           str_contains($v1->sections['hero'] ?? '', 'v1'));
check('snapshot() captura custom_css',         str_contains($v1->custom_css ?? '', '#111'));
check('snapshot() relação theme() funciona',   $v1->theme->id === $theme->id);

$vAuto = StudioThemeVersion::snapshot($theme, 'snapshot auto', 'auto');
check('snapshot() suporta tipo auto',          $vAuto->snapshot_type === 'auto');

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 2: ThemeController::createVersion' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

$createReq  = \Illuminate\Http\Request::create('/themes/x/versions', 'POST', ['changelog' => 'Ajustes de cor']);
$createRes  = $ctrl->createVersion($createReq, $theme->uuid);
$createData = json_decode($createRes->getContent(), true);
check('createVersion() success = true',        ($createData['success'] ?? false) === true);
check('createVersion() retorna version.uuid',  !empty($createData['version']['uuid']));
check('createVersion() tipo = manual',         ($createData['version']['snapshot_type'] ?? '') === 'manual');
check('createVersion() devolve changelog',     ($createData['version']['changelog'] ?? '') === 'Ajustes de cor');

// changelog vazio é aceite (nullable)
$emptyReq  = \Illuminate\Http\Request::create('/themes/x/versions', 'POST', []);
$emptyRes  = $ctrl->createVersion($emptyReq, $theme->uuid);
check('createVersion() aceita changelog vazio', $emptyRes->getStatusCode() === 200);

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 3: ThemeController::listVersions' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

$listRes  = $ctrl->listVersions($theme->uuid);
$listData = json_decode($listRes->getContent(), true);
check('listVersions() retorna array',          is_array($listData['versions'] ?? null));
// Bloco1: v1 + vAuto (2) ; Bloco2: createVersion + emptyVersion (2) = 4
check('listVersions() conta 4 versões',         count($listData['versions'] ?? []) === 4);
check('listVersions() inclui snapshot_type',    isset($listData['versions'][0]['snapshot_type']));
check('listVersions() inclui version',          isset($listData['versions'][0]['version']));

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 4: ThemeController::restoreVersion + auto-snapshot' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

// Alterar o tema para um estado novo (v2.0.0, cor diferente)
$theme->update([
    'version'    => '2.0.0',
    'colors'     => ['light' => ['--color-primary' => '#999999'], 'dark' => ['--color-primary' => '#222222']],
    'sections'   => ['hero' => '<section class="hero">v2</section>'],
    'custom_css' => '.hero { color: #999; }',
]);
$theme->refresh();
check('Estado actual alterado para v2.0.0',     $theme->version === '2.0.0');

$versionsBefore = StudioThemeVersion::where('studio_theme_id', $theme->id)->count();

$restoreReq  = \Illuminate\Http\Request::create('/x', 'POST');
$restoreRes  = $ctrl->restoreVersion($restoreReq, $theme->uuid, $v1->uuid);
$restoreData = json_decode($restoreRes->getContent(), true);
check('restoreVersion() success = true',        ($restoreData['success'] ?? false) === true);

$theme->refresh();
check('restoreVersion() repõe version 1.0.0',   $theme->version === '1.0.0');
check('restoreVersion() repõe colors (#111)',   ($theme->colors['light']['--color-primary'] ?? null) === '#111111');
check('restoreVersion() repõe sections (v1)',   str_contains($theme->sections['hero'] ?? '', 'v1'));
check('restoreVersion() repõe custom_css',      str_contains($theme->custom_css ?? '', '#111'));

$versionsAfter = StudioThemeVersion::where('studio_theme_id', $theme->id)->count();
check('restoreVersion() cria snapshot auto antes', $versionsAfter === $versionsBefore + 1);
$autoSnap = StudioThemeVersion::where('studio_theme_id', $theme->id)
    ->where('snapshot_type', 'auto')
    ->where('changelog', 'like', 'Antes de restaurar%')
    ->latest()->first();
check('Auto-snapshot "Antes de restaurar" existe', $autoSnap !== null);
check('Auto-snapshot capturou estado v2.0.0',   ($autoSnap?->colors['light']['--color-primary'] ?? null) === '#999999');

// Restaurar de versão inexistente → 404
$missingRes = null;
try {
    $ctrl->restoreVersion($restoreReq, $theme->uuid, 'uuid-inexistente-0000');
} catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
    $missingRes = 404;
}
check('restoreVersion() versão inexistente → 404', $missingRes === 404);

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 5: ThemeController::deleteVersion' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

$countBeforeDelete = StudioThemeVersion::where('studio_theme_id', $theme->id)->count();
$delRes  = $ctrl->deleteVersion($theme->uuid, $v1->uuid);
$delData = json_decode($delRes->getContent(), true);
check('deleteVersion() success = true',         ($delData['success'] ?? false) === true);
check('deleteVersion() remove o registo',       StudioThemeVersion::where('uuid', $v1->uuid)->doesntExist());
check('deleteVersion() decrementa contagem',    StudioThemeVersion::where('studio_theme_id', $theme->id)->count() === $countBeforeDelete - 1);

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 6: Rotas de versionamento' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

$routeNames = collect(Route::getRoutes())->map(fn ($r) => $r->getName())->filter()->values()->all();
check('Rota themes.versions.list existe',       in_array('themes.versions.list', $routeNames));
check('Rota themes.versions.create existe',     in_array('themes.versions.create', $routeNames));
check('Rota themes.versions.restore existe',    in_array('themes.versions.restore', $routeNames));
check('Rota themes.versions.delete existe',     in_array('themes.versions.delete', $routeNames));

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 7: UI — Themes/Edit.vue' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

$vue = file_get_contents(resource_path('js/Pages/Themes/Edit.vue'));
check("Tab 'versions' presente",                str_contains($vue, "id: 'versions'"));
check('ref themeVersions definido',             str_contains($vue, 'themeVersions'));
check('loadVersions() definida',                str_contains($vue, 'async function loadVersions'));
check('saveVersion() definida',                 str_contains($vue, 'async function saveVersion'));
check('restoreVersion() definida',              str_contains($vue, 'async function restoreVersion'));
check('deleteVersion() definida',               str_contains($vue, 'async function deleteVersion'));
check('Timeline com tipos de snapshot',         str_contains($vue, "snapshot_type === 'publish'") && str_contains($vue, "snapshot_type === 'auto'"));
check('Botão ↩️ Restaurar no timeline',         str_contains($vue, '↩️') || str_contains($vue, 'restoreVersion(ver)'));
check('Info sobre auto-snapshot presente',      str_contains($vue, 'automático'));

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 8: Snapshot de publicação (wiring)' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

$controllerSrc = file_get_contents(app_path('Http/Controllers/ThemeController.php'));
check('publish() cria snapshot tipo publish',   str_contains($controllerSrc, "'publish'") && str_contains($controllerSrc, 'StudioThemeVersion::snapshot'));
check('StudioTheme tem relação versions()',     method_exists(StudioTheme::class, 'versions'));

// ─── Limpeza ────────────────────────────────────────────────────────────────
StudioThemeVersion::where('studio_theme_id', $theme->id)->delete();
$theme->forceDelete();

// ═══════════════════════════════════════════════════
// RESULTADO FINAL
// ═══════════════════════════════════════════════════
$total = $passed + $failed;
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo "RESULTADO FINAL: {$passed} passou, {$failed} falhou" . PHP_EOL;
if ($failed === 0) echo '✅ TODOS OS TESTES PASSARAM' . PHP_EOL;
else               echo "❌ {$failed} TESTE(S) FALHARAM" . PHP_EOL;
