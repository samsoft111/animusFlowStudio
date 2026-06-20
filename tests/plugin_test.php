<?php
/**
 * Plugin Test Suite — AnimusFlowStudio
 * Cobre: buildPluginZip, exportPrompt, installInCms, publish, rotas, Chat IA, Edit.vue, integração CMS
 * Execução: php tests/plugin_test.php
 */

define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Controllers\PluginController;
use App\Models\StudioPlugin;
use App\Models\StudioSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

// ─── Contador ────────────────────────────────────────────────────────────────
$passed = 0; $failed = 0;
function check(string $label, bool $ok): void {
    global $passed, $failed;
    if ($ok) { echo "  ✅ {$label}\n"; $passed++; }
    else      { echo "  ❌ {$label}\n"; $failed++; }
}

// ─── Plugin de teste ─────────────────────────────────────────────────────────
$plugin = StudioPlugin::create([
    'name'        => 'test-export-plugin-' . uniqid(),
    'label'       => 'Test Export Plugin',
    'description' => 'Plugin de teste para export',
    'version'     => '2.1.0',
    'author'      => 'Studio Tester',
    'author_url'  => 'https://tester.example.com',
    'category'    => 'utilities',
    'tags'        => ['test', 'export'],
    'license'     => 'MIT',
    'min_animusflow_version' => '1.2.0',
    'hooks'       => ['page.render', 'content.publish'],
    'plugin_php'  => "<?php\n\ndeclare(strict_types=1);\n\nclass TestExportPlugin\n{\n    public function onPageRender(\$page): string\n    {\n        return '<div class=\"af-test\">Hello from test plugin</div>';\n    }\n\n    public function onContentPublish(\$page): void {}\n}\n",
    'widget_blade' => '<div class="af-test-widget">{{ $message }}</div>',
    'widget_js'   => "document.addEventListener('DOMContentLoaded', () => { console.log('af-test loaded'); });",
    'custom_css'  => '.af-test { background: var(--color-primary); padding: 1rem; }',
    'settings_schema' => [
        ['key' => 'test_message', 'label' => 'Message', 'type' => 'text', 'default' => 'Hello'],
        ['key' => 'test_enabled', 'label' => 'Enabled', 'type' => 'toggle', 'toggle_label' => 'Enable plugin'],
    ],
    'status'      => 'ready',
]);

// Plugin mínimo (só nome, sem código)
$minPlugin = StudioPlugin::create([
    'name'    => 'test-minimal-plugin-' . uniqid(),
    'label'   => 'Minimal Plugin',
    'version' => '1.0.0',
    'status'  => 'draft',
]);

// ─── Reflection para aceder a métodos privados ────────────────────────────────
$ctrl = new PluginController();
$ref  = new ReflectionClass($ctrl);

$buildZip = $ref->getMethod('buildPluginZip');
$buildZip->setAccessible(true);

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 1: buildPluginZip — Estrutura do ZIP' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

$zipPath = $buildZip->invoke($ctrl, $plugin);
check('ZIP criado em storage/app/', str_starts_with($zipPath, storage_path('app/')));
check('ZIP não está vazio', file_exists($zipPath) && filesize($zipPath) > 0);

$zip = new ZipArchive();
$opened = $zip->open($zipPath) === true;
check('ZIP abre sem erro', $opened);

if ($opened) {
    $entries = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $entries[] = $zip->getNameIndex($i);
    }

    // Manifest
    $manifestEntry = current(array_filter($entries, fn($e) => basename($e) === 'animusflow-plugin.json'));
    check('animusflow-plugin.json presente no ZIP', (bool)$manifestEntry);

    if ($manifestEntry) {
        $manifest = json_decode($zip->getFromName($manifestEntry), true);
        check('manifest: name correcto',    $manifest['name']    === $plugin->name);
        check('manifest: label correcto',   $manifest['label']   === $plugin->label);
        check('manifest: version correcta', $manifest['version'] === '2.1.0');
        check('manifest: author correcto',  $manifest['author']  === 'Studio Tester');
        check('manifest: hooks declarados', in_array('page.render', $manifest['hooks'] ?? []));
        check('manifest: content.publish declarado', in_array('content.publish', $manifest['hooks'] ?? []));
        check('manifest: settings_schema presente', count($manifest['settings'] ?? []) === 2);
        check('manifest: license MIT', $manifest['license'] === 'MIT');
        check('manifest: requires 1.2.0', $manifest['requires'] === '1.2.0');
    }

    // Plugin.php
    $phpEntry = current(array_filter($entries, fn($e) => basename($e) === 'Plugin.php'));
    check('Plugin.php presente no ZIP', (bool)$phpEntry);
    if ($phpEntry) {
        $phpContent = $zip->getFromName($phpEntry);
        check('Plugin.php contém declare(strict_types=1)', str_contains($phpContent, 'declare(strict_types=1)'));
        check('Plugin.php contém onPageRender', str_contains($phpContent, 'onPageRender'));
        check('Plugin.php contém HTML inline', str_contains($phpContent, 'af-test'));
    }

    // widget.blade.php
    $bladeEntry = current(array_filter($entries, fn($e) => basename($e) === 'widget.blade.php'));
    check('views/widget.blade.php presente no ZIP', (bool)$bladeEntry);

    // widget.js
    $jsEntry = current(array_filter($entries, fn($e) => basename($e) === 'widget.js'));
    check('assets/widget.js presente no ZIP', (bool)$jsEntry);
    if ($jsEntry) {
        check('widget.js contém código JS', str_contains($zip->getFromName($jsEntry), 'DOMContentLoaded'));
    }

    // plugin.css
    $cssEntry = current(array_filter($entries, fn($e) => basename($e) === 'plugin.css'));
    check('assets/plugin.css presente no ZIP', (bool)$cssEntry);
    if ($cssEntry) {
        check('plugin.css contém regra CSS', str_contains($zip->getFromName($cssEntry), 'af-test'));
    }

    // README.md
    $readmeEntry = current(array_filter($entries, fn($e) => basename($e) === 'README.md'));
    check('README.md presente no ZIP', (bool)$readmeEntry);
    if ($readmeEntry) {
        $readme = $zip->getFromName($readmeEntry);
        check('README.md: label do plugin', str_contains($readme, 'Test Export Plugin'));
        check('README.md: hooks listados', str_contains($readme, 'page.render'));
        check('README.md: instruções instalação', str_contains($readme, 'Upload'));
    }

    // Caminhos com forward slashes (fix Windows)
    $hasBackslash = false;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        if (str_contains($zip->getNameIndex($i), '\\')) { $hasBackslash = true; break; }
    }
    check('ZIP paths usam forward slashes (sem backslash)', !$hasBackslash);

    // Estrutura de prefixo: ficheiros dentro de uma pasta com o nome do plugin
    $hasPrefix = false;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (str_starts_with($name, $plugin->name . '/')) { $hasPrefix = true; break; }
    }
    check("Ficheiros dentro de pasta {$plugin->name}/ no ZIP", $hasPrefix);

    $zip->close();
}

// Limpar ZIP
@unlink($zipPath);

// ─── Plugin mínimo (sem código) ───────────────────────────────────────────────
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 2: buildPluginZip — Plugin mínimo (sem código)' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

$minZipPath = $buildZip->invoke($ctrl, $minPlugin);
$minZip = new ZipArchive();
if ($minZip->open($minZipPath) === true) {
    $minEntries = [];
    for ($i = 0; $i < $minZip->numFiles; $i++) $minEntries[] = $minZip->getNameIndex($i);

    $minManifestEntry = current(array_filter($minEntries, fn($e) => basename($e) === 'animusflow-plugin.json'));
    $minPhpEntry      = current(array_filter($minEntries, fn($e) => basename($e) === 'Plugin.php'));

    check('Plugin mínimo: manifest criado automaticamente', (bool)$minManifestEntry);
    check('Plugin mínimo: Plugin.php gerado com scaffold',  (bool)$minPhpEntry);

    if ($minPhpEntry) {
        $minPhpContent = $minZip->getFromName($minPhpEntry);
        check('Scaffold tem declare(strict_types=1)',    str_contains($minPhpContent, 'declare(strict_types=1)'));
        check('Scaffold contém classe PHP',              str_contains($minPhpContent, 'class '));
        check('Scaffold NÃO usa view() (bug conhecido)', !str_contains($minPhpContent, '->render()'));
    }

    if ($minManifestEntry) {
        $minManifest = json_decode($minZip->getFromName($minManifestEntry), true);
        check('Manifest mínimo: name correcto',    $minManifest['name']  === $minPlugin->name);
        check('Manifest mínimo: settings vazio',  empty($minManifest['settings']));
    }

    $minZip->close();
}
@unlink($minZipPath);

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 3: exportPrompt — Estrutura e conteúdo (.afprompt)' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

StudioSetting::set('studio_author', 'Test Author');
StudioSetting::set('studio_author_url', 'https://author.test');
StudioSetting::set('export_animusflow_min_ver', '1.2.0');

// Capturar output do método exportPrompt
ob_start();
$response = $ctrl->exportPrompt($plugin->uuid);
ob_end_clean();

$promptContent = $response->getContent();
$promptHeaders = $response->headers->all();

check('exportPrompt retorna resposta HTTP', $response->getStatusCode() === 200);
check('Content-Type é text/plain', str_contains($promptHeaders['content-type'][0] ?? '', 'text/plain'));
check('Content-Disposition com .afprompt', str_contains($promptHeaders['content-disposition'][0] ?? '', '.afprompt'));
check('Ficheiro .afprompt tem conteúdo', strlen($promptContent) > 200);

// Marcações obrigatórias
check('Contém [AF:PLUGIN:BEGIN]', str_contains($promptContent, '[AF:PLUGIN:BEGIN]'));
check('Contém [AF:PLUGIN:END]',   str_contains($promptContent, '[AF:PLUGIN:END]'));
check('Contém CHECKSUM sha256',   str_contains($promptContent, 'CHECKSUM: sha256:'));
check('Cabeçalho ANIMUSFLOW PLUGIN PROMPT', str_contains($promptContent, 'ANIMUSFLOW PLUGIN PROMPT'));
check('Nome do plugin no header', str_contains($promptContent, $plugin->name));

// Extrair JSON do bloco
preg_match('/\[AF:PLUGIN:BEGIN\]\s*([\s\S]*?)\s*\[AF:PLUGIN:END\]/', $promptContent, $m);
$json    = $m[1] ?? '';
$payload = json_decode($json, true);

check('JSON entre marcações é válido', is_array($payload));

if (is_array($payload)) {
    check('payload.type = plugin',              $payload['type'] === 'plugin');
    check('payload.af_prompt_version = 1.0',   $payload['af_prompt_version'] === '1.0');
    check('payload.generator = AnimusFlowStudio', $payload['generator'] === 'AnimusFlowStudio');
    check('payload.meta.name correcto',         $payload['meta']['name'] === $plugin->name);
    check('payload.meta.label correcto',        $payload['meta']['label'] === $plugin->label);
    check('payload.meta.version correcta',      $payload['meta']['version'] === '2.1.0');
    check('payload.meta.author correcto',       $payload['meta']['author'] === 'Studio Tester');
    check('payload.meta.hooks declarados',      in_array('page.render', $payload['meta']['hooks'] ?? []));
    check('payload.code.plugin_php presente',   !empty($payload['code']['plugin_php']));
    check('payload.code.widget_blade presente', !empty($payload['code']['widget_blade']));
    check('payload.code.widget_js presente',    !empty($payload['code']['widget_js']));
    check('payload.code.custom_css presente',   !empty($payload['code']['custom_css']));
    check('payload.settings_schema tem 2 campos', count($payload['settings_schema'] ?? []) === 2);
    check('payload.af_install.manifest presente', !empty($payload['af_install']['manifest']));
    check('payload.af_install.manifest.hooks',    in_array('page.render', $payload['af_install']['manifest']['hooks'] ?? []));
    check('payload.af_install.manifest.settings', count($payload['af_install']['manifest']['settings'] ?? []) === 2);
    check('payload.af_install.plugin_php',        !empty($payload['af_install']['plugin_php']));
    check('payload.af_install.widget_blade',      !empty($payload['af_install']['widget_blade']));
    check('payload.af_install.widget_js',         !empty($payload['af_install']['widget_js']));
    check('payload.af_install.custom_css',        !empty($payload['af_install']['custom_css']));

    // Verificar checksum
    $expectedChecksum = hash('sha256', $json);
    preg_match('/CHECKSUM: sha256:([a-f0-9]+)/', $promptContent, $csMatch);
    $actualChecksum = $csMatch[1] ?? '';
    check('Checksum sha256 tem 64 chars',       strlen($actualChecksum) === 64);
    check('Checksum sha256 é válido (bate com JSON)', $actualChecksum === $expectedChecksum);
}

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 4: Integração CMS — ZIP compatível com StudioController::installPlugin' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

// Simula o que o CMS faz ao receber o ZIP
$zipForCms = $buildZip->invoke($ctrl, $plugin);
$zipCms = new ZipArchive();
$zipCms->open($zipForCms);

// Encontrar manifest (como faz o CMS)
$foundManifest = null;
$foundPrefix   = '';
for ($i = 0; $i < $zipCms->numFiles; $i++) {
    $name = $zipCms->getNameIndex($i);
    if (basename($name) === 'animusflow-plugin.json') {
        $foundManifest = json_decode($zipCms->getFromName($name), true);
        $foundPrefix   = dirname($name) === '.' ? '' : dirname($name) . '/';
        break;
    }
}

check('CMS encontra animusflow-plugin.json no ZIP', is_array($foundManifest));
check('CMS extrai prefix correcto (nome-plugin/)', $foundPrefix === $plugin->name . '/');

if (is_array($foundManifest)) {
    $slug = $foundManifest['name'] ?? '';
    check('Slug validação regex do CMS passa',
        (bool) preg_match('/^[a-z0-9][a-z0-9\-_]{0,49}$/', $slug));
}

// Verificar que Plugin.php fica em prefixo/Plugin.php (para extracção correcta)
$phpOk = false;
for ($i = 0; $i < $zipCms->numFiles; $i++) {
    $name = $zipCms->getNameIndex($i);
    if ($name === $foundPrefix . 'Plugin.php') { $phpOk = true; break; }
}
check('Plugin.php em prefixo correcto no ZIP (para extracção CMS)', $phpOk);

// Simula extracção e verifica que Plugin.php seria encontrado
$destSimulado = sys_get_temp_dir() . '/cms-test-plugin-' . uniqid();
File::ensureDirectoryExists($destSimulado);
for ($i = 0; $i < $zipCms->numFiles; $i++) {
    $name = $zipCms->getNameIndex($i);
    if (!empty($foundPrefix) && !str_starts_with($name, $foundPrefix)) continue;
    $relative = empty($foundPrefix) ? $name : substr($name, strlen($foundPrefix));
    if ($relative === '' || str_ends_with($relative, '/')) continue;
    $target = $destSimulado . '/' . $relative;
    File::ensureDirectoryExists(dirname($target));
    file_put_contents($target, $zipCms->getFromName($name));
}
$zipCms->close();

check('Plugin.php extraído para destino simulado', file_exists($destSimulado . '/Plugin.php'));
check('animusflow-plugin.json extraído para destino simulado', file_exists($destSimulado . '/animusflow-plugin.json'));
check('views/widget.blade.php extraído', file_exists($destSimulado . '/views/widget.blade.php'));
check('assets/widget.js extraído', file_exists($destSimulado . '/assets/widget.js'));
check('assets/plugin.css extraído', file_exists($destSimulado . '/assets/plugin.css'));

// Simula PluginManager::resolveClassName()
$phpSrc  = file_get_contents($destSimulado . '/Plugin.php');
$tokens  = token_get_all($phpSrc);
$class   = '';
foreach ($tokens as $i => $tok) {
    if (is_array($tok) && $tok[0] === T_CLASS) {
        // próximo token não-whitespace é o nome
        for ($j = $i+1; $j < count($tokens); $j++) {
            if (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) continue;
            if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) { $class = $tokens[$j][1]; break 2; }
        }
    }
}
check("PluginManager::resolveClassName() encontra classe '$class'", !empty($class));
check('Classe termina em Plugin', str_ends_with($class, 'Plugin'));

// Simula dispatch: instanciar e chamar onPageRender
$resolved = false;
try {
    require_once $destSimulado . '/Plugin.php';
    if (class_exists($class)) {
        $instance = new $class();
        $result   = $instance->onPageRender((object)['id' => 1, 'title' => 'Test']);
        check('onPageRender() retorna string HTML', is_string($result) && strlen($result) > 0);
        check('HTML contém classe CSS esperada', str_contains($result, 'af-test'));
        $resolved = true;
    }
} catch (\Throwable $e) {
    check('onPageRender() executado sem erro: ' . $e->getMessage(), false);
}
if (!$resolved) check('Classe instanciada com sucesso', false);

File::deleteDirectory($destSimulado);
@unlink($zipForCms);

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 5: installInCms — Fluxo HTTP' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

StudioSetting::set('cms_url', '');
StudioSetting::set('cms_api_key', '');

// Teste sem configuração
$request = \Illuminate\Http\Request::create('/plugins/' . $plugin->uuid . '/install-in-cms', 'POST');
$result  = $ctrl->installInCms($plugin->uuid);
check('Sem CMS URL: retorna 422', $result->getStatusCode() === 422);
check('Sem CMS URL: mensagem de erro correcta', str_contains($result->getContent(), 'CMS URL'));

// Teste com CMS configurado e resposta simulada
Http::fake(['*/api/v1/studio/install-plugin' => Http::response(['success' => true, 'message' => 'Plugin instalado.'], 200)]);
StudioSetting::set('cms_url', 'http://localhost:8000');
StudioSetting::set('cms_api_key', encrypt('test-token-123'));

$result2 = $ctrl->installInCms($plugin->uuid);
$data2   = json_decode($result2->getContent(), true);
check('CMS configurado: retorna 200', $result2->getStatusCode() === 200);
check('CMS configurado: success=true', $data2['success'] ?? false);
check('CMS configurado: mensagem presente', !empty($data2['message']));

// Limpar
StudioSetting::set('cms_url', '');
StudioSetting::set('cms_api_key', '');

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 6: publish — Marketplace' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

StudioSetting::set('animusflow_api_key', '');
$rPub1 = $ctrl->publish($plugin->uuid);
check('Sem API key: retorna 422', $rPub1->getStatusCode() === 422);
check('Sem API key: mensagem correcta', str_contains($rPub1->getContent(), 'API key'));

Http::fake(['*/api/marketplace/publish' => Http::response(['uuid' => 'pkg-test-uuid-789'], 200)]);
StudioSetting::set('animusflow_api_key', encrypt('market-key'));

$rPub2   = $ctrl->publish($plugin->uuid);
$dataPub = json_decode($rPub2->getContent(), true);
check('Marketplace OK: retorna 200', $rPub2->getStatusCode() === 200);
check('Marketplace OK: success=true', $dataPub['success'] ?? false);
check('Marketplace OK: package_uuid retornado', ($dataPub['package_uuid'] ?? '') === 'pkg-test-uuid-789');

$freshPlugin = StudioPlugin::where('uuid', $plugin->uuid)->first();
check('Após publish: is_published=true', (bool)$freshPlugin->is_published);
check('Após publish: status=published',  $freshPlugin->status === 'published');
check('Após publish: animus_package_uuid guardado', $freshPlugin->animus_package_uuid === 'pkg-test-uuid-789');

StudioSetting::set('animusflow_api_key', '');

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 7: Auto-criação de plugin' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

$countBefore = StudioPlugin::withTrashed()->count();
$createResult = $ctrl->create();
check('create() retorna RedirectResponse', $createResult instanceof \Illuminate\Http\RedirectResponse);
check('create() redireciona para edit', str_contains($createResult->getTargetUrl(), '/plugins/'));
check('create() cria novo plugin na DB', StudioPlugin::withTrashed()->count() === $countBefore + 1);

$newPlugin = StudioPlugin::orderBy('id', 'desc')->first();
check('Plugin auto-criado tem nome válido (regex)',
    (bool) preg_match('/^[a-z0-9][a-z0-9\-_]{0,49}$/', $newPlugin->name));
check('Plugin auto-criado tem status=draft',  $newPlugin->status === 'draft');
check('Plugin auto-criado tem hooks=[page.render]', ($newPlugin->hooks ?? []) === ['page.render']);
check('Plugin auto-criado tem versão 1.0.0', $newPlugin->version === '1.0.0');
$newPlugin->forceDelete();

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 8: Rotas HTTP — Verificação completa' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

$routes = Route::getRoutes()->getRoutesByName();

$routeTests = [
    'plugins.index'        => ['GET',  '/plugins'],
    'plugins.create'       => ['GET',  '/plugins/create'],
    'plugins.edit'         => ['GET',  '/plugins/{uuid}/edit'],
    'plugins.update'       => ['PUT',  '/plugins/{uuid}'],
    'plugins.destroy'      => ['DELETE', '/plugins/{uuid}'],
    'plugins.export'       => ['GET',  '/plugins/{uuid}/export'],
    'plugins.export-prompt'=> ['GET',  '/plugins/{uuid}/export-prompt'],
    'plugins.generate-ai'  => ['POST', '/plugins/{uuid}/generate-ai'],
    'plugins.publish'      => ['POST', '/plugins/{uuid}/publish'],
    'plugins.install-cms'  => ['POST', '/plugins/{uuid}/install-in-cms'],
    'plugins.chat'         => ['POST', '/plugins/{uuid}/chat'],
];

foreach ($routeTests as $name => [$method, $path]) {
    $route = $routes[$name] ?? null;
    check("Rota {$name} existe ({$method} {$path})", $route !== null && in_array($method, $route->methods()));
}

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 9: Scaffold PHP — Verificar que NÃO usa view()->render()' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

// O CMS NÃO regista namespaces de view para plugins — view('plugin::widget') falha
// O scaffold correcto deve usar HTML inline ou file_get_contents(), não view()
$scaffoldFromZip = $buildZip->invoke($ctrl, $plugin);
$scaffoldZip = new ZipArchive();
$scaffoldZip->open($scaffoldFromZip);

for ($i = 0; $i < $scaffoldZip->numFiles; $i++) {
    if (basename($scaffoldZip->getNameIndex($i)) === 'Plugin.php') {
        $phpSrc = $scaffoldZip->getFromIndex($i);
        // Quando plugin_php está preenchido, usa directamente — não usa view()
        check('Plugin.php preenchido: NÃO usa view()->render() (incompatível com CMS)', !str_contains($phpSrc, '->render()'));
        check('Plugin.php preenchido: contém HTML inline ou file_get_contents', str_contains($phpSrc, 'HTML') || str_contains($phpSrc, 'file_get_contents') || str_contains($phpSrc, 'return \'') || str_contains($phpSrc, 'return "') || str_contains($phpSrc, '<<<'));
        break;
    }
}
$scaffoldZip->close();
@unlink($scaffoldFromZip);

// Verificar também o scaffold gerado pelo buildPluginZip para plugin SEM plugin_php
$noPhpPlugin = StudioPlugin::create([
    'name'    => 'test-nophp-' . uniqid(),
    'label'   => 'No PHP Plugin',
    'version' => '1.0.0',
    'status'  => 'draft',
    'hooks'   => ['page.render'],
]);
$noPhpZipPath = $buildZip->invoke($ctrl, $noPhpPlugin);
$noPhpZip = new ZipArchive();
$noPhpZip->open($noPhpZipPath);
for ($i = 0; $i < $noPhpZip->numFiles; $i++) {
    if (basename($noPhpZip->getNameIndex($i)) === 'Plugin.php') {
        $stubSrc = $noPhpZip->getFromIndex($i);
        check('Scaffold automático (sem plugin_php): NÃO usa view()->render()', !str_contains($stubSrc, '->render()'));
        check('Scaffold automático: tem declare(strict_types=1)', str_contains($stubSrc, 'declare(strict_types=1)'));
        break;
    }
}
$noPhpZip->close();
@unlink($noPhpZipPath);
$noPhpPlugin->forceDelete();

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 10: Edit.vue — Verificação de interface' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

$vue = file_get_contents(resource_path('js/Pages/Plugins/Edit.vue'));

check('Tab Chat IA presente',                str_contains($vue, "id: 'chat'"));
check('Tab Exportar presente',               str_contains($vue, "id: 'export'"));
check('Botão .afprompt no topbar',           str_contains($vue, 'showPromptModal = true') && str_contains($vue, '.afprompt'));
check('Modal exportar prompt presente',      str_contains($vue, 'showPromptModal'));
check('[AF:PLUGIN:BEGIN] na pré-visualização', str_contains($vue, 'AF:PLUGIN:BEGIN'));
check('Botão Descarregar .afprompt no modal',str_contains($vue, 'export-prompt'));
check('copyPromptToClipboard() definida',    str_contains($vue, 'function copyPromptToClipboard'));
check('promptSummary computed presente',     str_contains($vue, 'promptSummary'));
check('installSteps presente',               str_contains($vue, 'installSteps'));
check('Sem formulário de criação separado',  !str_contains($vue, "v-if=\"!plugin\""));
check('Chat sendChatMessage() definida',     str_contains($vue, 'async function sendChatMessage'));
check('Chat applyChatUpdates() definida',    str_contains($vue, 'function applyChatUpdates'));
check('Chat usa endpoint /plugins/.../chat', str_contains($vue, "/plugins/\${props.plugin.uuid}/chat"));
check('Chat sincroniza plugin_php',          str_contains($vue, 'form.plugin_php'));
check('Chat sincroniza widget_blade',        str_contains($vue, 'form.widget_blade'));
check('injectPhpScaffold() definida',        str_contains($vue, 'function injectPhpScaffold'));
check('Scaffold NÃO usa view()->render()',   !str_contains($vue, "view('\${props.plugin.name}::widget')->render()") &&
                                              !str_contains($vue, 'view(`${props.plugin.name}::widget`)->render()'));

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 11: AIEngine::chatPlugin() — Verificação estática' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

$aiSrc = file_get_contents(app_path('Services/AIEngine.php'));

check('chatPlugin() método existe', str_contains($aiSrc, 'public static function chatPlugin'));
check('chatPlugin() usa system prompt em PT-PT', str_contains($aiSrc, 'PT-PT') && str_contains($aiSrc, 'chatPlugin'));
check('chatPlugin() extrai json_updates', str_contains($aiSrc, 'json_updates') && str_contains($aiSrc, 'chatPlugin'));
check('chatPlugin() suporta Claude e OpenAI', str_contains($aiSrc, 'chatOpenAI') && str_contains($aiSrc, 'chatClaude'));
check('chatPlugin() menciona hooks AnimusFlow no prompt', str_contains($aiSrc, 'page.render') && str_contains($aiSrc, 'chatPlugin'));
check('chatPlugin() retorna [reply, updates, build]', str_contains($aiSrc, "return ['reply' => \$reply, 'updates' => \$updates, 'build' => \$build]"));

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 12: PluginController::chat() — Verificação estática' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

$ctrlSrc = file_get_contents(app_path('Http/Controllers/PluginController.php'));

check('chat() método existe', str_contains($ctrlSrc, 'public function chat('));
check('chat() valida message e history', str_contains($ctrlSrc, "'message'") && str_contains($ctrlSrc, "'history'"));
check('chat() processa ficheiros multimédia', str_contains($ctrlSrc, "'files'") && str_contains($ctrlSrc, 'image/jpeg'));
check('chat() chama AIEngine::chatPlugin', str_contains($ctrlSrc, 'AIEngine::chatPlugin'));
check('chat() aplica updates com array_intersect_key', str_contains($ctrlSrc, 'array_intersect_key'));
check('chat() retorna reply + applied + plugin', str_contains($ctrlSrc, "'reply'") && str_contains($ctrlSrc, "'applied'") && str_contains($ctrlSrc, "'plugin'"));
check('chat() campos permitidos incluem plugin_php', str_contains($ctrlSrc, "'plugin_php'"));
check('chat() campos permitidos incluem widget_blade', str_contains($ctrlSrc, "'widget_blade'"));
check('chat() campos permitidos incluem settings_schema', str_contains($ctrlSrc, "'settings_schema'"));

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 13: Versionamento de plugins' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

use App\Models\StudioPluginVersion;

// Model e snapshot
$verPlugin = \App\Models\StudioPlugin::create([
    'name'        => 'ver-test-' . uniqid(),
    'label'       => 'Version Test Plugin',
    'version'     => '1.0.0',
    'status'      => 'draft',
    'hooks'       => ['page.render'],
    'plugin_php'  => "<?php\ndeclare(strict_types=1);\nclass VerTestPlugin { public function onPageRender(): string { return '<div>v1</div>'; } }",
    'widget_blade'=> '<div class="v1">Hello v1</div>',
    'custom_css'  => '.v1 { color: red; }',
    'settings_schema' => [['key'=>'msg','label'=>'Msg','type'=>'text','default'=>'']],
]);

// Check snapshotFrom
$snap = StudioPluginVersion::snapshotFrom($verPlugin);
check('snapshotFrom() retorna array', is_array($snap));
check('snapshotFrom() inclui plugin_php', isset($snap['plugin_php']) && str_contains($snap['plugin_php'], 'VerTestPlugin'));
check('snapshotFrom() inclui hooks', isset($snap['hooks']) && in_array('page.render', $snap['hooks']));
check('snapshotFrom() inclui settings_schema', isset($snap['settings_schema']) && count($snap['settings_schema']) === 1);
check('snapshotFrom() não inclui uuid', !isset($snap['uuid']));

// Create version via controller
$vCtrl = new PluginController();
$vRef  = new ReflectionClass($vCtrl);

// saveVersion via HTTP-like request
$saveReq = \Illuminate\Http\Request::create('/plugins/x/versions', 'POST', [
    'version'   => '1.0.0',
    'changelog' => 'Versão inicial',
]);
$saveRes = $vCtrl->saveVersion($saveReq, $verPlugin->uuid);
$saveData = json_decode($saveRes->getContent(), true);

check('saveVersion() retorna success=true', ($saveData['success'] ?? false) === true);
check('saveVersion() retorna version.id',   isset($saveData['version']['id']));
check('saveVersion() version é 1.0.0',      ($saveData['version']['version'] ?? '') === '1.0.0');
check('saveVersion() summary tem has_php',  ($saveData['version']['summary']['has_php'] ?? false) === true);

$savedVersionId = $saveData['version']['id'] ?? null;

// Duplicate version should fail
$dupReq = \Illuminate\Http\Request::create('/plugins/x/versions', 'POST', ['version' => '1.0.0', 'changelog' => '']);
$dupRes = $vCtrl->saveVersion($dupReq, $verPlugin->uuid);
check('saveVersion() duplicado retorna 422', $dupRes->getStatusCode() === 422);

// Create a second version
$verPlugin->update(['plugin_php' => "<?php\ndeclare(strict_types=1);\nclass VerTestPlugin { public function onPageRender(): string { return '<div>v2</div>'; } }", 'version' => '1.1.0']);
$v2Req = \Illuminate\Http\Request::create('/plugins/x/versions', 'POST', ['version' => '1.1.0', 'changelog' => 'Nova funcionalidade']);
$v2Res = $vCtrl->saveVersion($v2Req, $verPlugin->uuid);
$v2Data = json_decode($v2Res->getContent(), true);
$v2Id   = $v2Data['version']['id'] ?? null;

check('Segunda versão criada com sucesso', isset($v2Id));

// versions() endpoint
$listRes  = $vCtrl->versions($verPlugin->uuid);
$listData = json_decode($listRes->getContent(), true);
check('versions() retorna array de versões', is_array($listData['versions'] ?? null));
check('versions() tem 2 versões', count($listData['versions'] ?? []) === 2);
check('versions() inclui summary.has_php', isset($listData['versions'][0]['summary']['has_php']));
check('versions() ordenado por created_at DESC', ($listData['versions'][0]['version'] ?? '') === '1.1.0');

// versionSnapshot()
if ($savedVersionId) {
    $snapRes  = $vCtrl->versionSnapshot($verPlugin->uuid, $savedVersionId);
    $snapData = json_decode($snapRes->getContent(), true);
    check('versionSnapshot() retorna snapshot', isset($snapData['snapshot']));
    check('versionSnapshot() snapshot tem plugin_php', !empty($snapData['snapshot']['plugin_php']));
    check('versionSnapshot() snapshot é v1.0.0', ($snapData['version'] ?? '') === '1.0.0');
}

// compareVersions()
if ($savedVersionId && $v2Id) {
    $cmpReq = \Illuminate\Http\Request::create('/compare', 'POST', ['version_a' => $savedVersionId, 'version_b' => $v2Id]);
    $cmpRes  = $vCtrl->compareVersions($cmpReq, $verPlugin->uuid);
    $cmpData = json_decode($cmpRes->getContent(), true);
    check('compareVersions() retorna diff', isset($cmpData['diff']));
    check('compareVersions() detecta mudança em plugin_php', count(array_filter($cmpData['diff'] ?? [], fn($d) => $d['field'] === 'plugin_php')) > 0);
    check('compareVersions() conta campos alterados', ($cmpData['changed'] ?? 0) > 0);
}

// restoreVersion()
if ($savedVersionId) {
    $rstRes  = $vCtrl->restoreVersion($verPlugin->uuid, $savedVersionId);
    $rstData = json_decode($rstRes->getContent(), true);
    check('restoreVersion() retorna success=true', ($rstData['success'] ?? false) === true);
    $verPlugin->refresh();
    check('restoreVersion() restaura plugin_php para v1.0.0', str_contains($verPlugin->plugin_php ?? '', 'v1</div>'));
}

// Rotas de versioning
$routeNames = collect(\Illuminate\Support\Facades\Route::getRoutes())->map(fn($r) => $r->getName())->filter()->values()->all();
check('Rota plugins.versions.list existe',     in_array('plugins.versions.list', $routeNames));
check('Rota plugins.versions.save existe',     in_array('plugins.versions.save', $routeNames));
check('Rota plugins.versions.snapshot existe', in_array('plugins.versions.snapshot', $routeNames));
check('Rota plugins.versions.restore existe',  in_array('plugins.versions.restore', $routeNames));
check('Rota plugins.versions.compare existe',  in_array('plugins.versions.compare', $routeNames));

// Edit.vue — tab e funções
$vue = file_get_contents(resource_path('js/Pages/Plugins/Edit.vue'));
check("Tab '📦 Versões' presente",       str_contains($vue, "id: 'versions'"));
check('bumpVersion() definida',          str_contains($vue, 'function bumpVersion'));
check('createVersion() definida',        str_contains($vue, 'async function createVersion'));
check('restoreToVersion() definida',     str_contains($vue, 'async function restoreToVersion'));
check('selectForCompare() definida',     str_contains($vue, 'function selectForCompare'));
check('runCompare() definida',           str_contains($vue, 'async function runCompare'));
check('viewSnapshot() definida',         str_contains($vue, 'async function viewSnapshot'));
check('Timeline dot activo no topo',     str_contains($vue, "idx === 0"));
check('Diff viewer com grid cols 2',     str_contains($vue, 'grid-cols-2') && str_contains($vue, 'diffResult'));
check('snapshotModal template presente', str_contains($vue, 'snapshotModal'));
check('Botão ↩️ Restaurar no timeline',  str_contains($vue, '↩️ Restaurar'));

// StudioPluginVersion model
check('snapshotFields inclui plugin_php',  in_array('plugin_php', StudioPluginVersion::$snapshotFields));
check('snapshotFields inclui hooks',       in_array('hooks', StudioPluginVersion::$snapshotFields));
check('snapshotFields NÃO inclui uuid',    !in_array('uuid', StudioPluginVersion::$snapshotFields));
check('auto-snapshot em publish wired',    str_contains(file_get_contents(app_path('Http/Controllers/PluginController.php')), 'saveVersionSnapshot'));

// Limpeza
StudioPluginVersion::where('studio_plugin_id', $verPlugin->id)->delete();
$verPlugin->forceDelete();

// ═══════════════════════════════════════════════════
// Limpeza
// ═══════════════════════════════════════════════════
$plugin->forceDelete();
$minPlugin->forceDelete();

// ═══════════════════════════════════════════════════
// RESULTADO FINAL
// ═══════════════════════════════════════════════════
$total = $passed + $failed;
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo "RESULTADO FINAL: {$passed} passou, {$failed} falhou" . PHP_EOL;
if ($failed === 0) echo '✅ TODOS OS TESTES PASSARAM' . PHP_EOL;
else               echo "❌ {$failed} TESTE(S) FALHARAM" . PHP_EOL;
