<?php

/**
 * Testes — AI Command Cache (Memória Local) para Temas e Plugins
 *
 * Garante:
 *  1. Primeira chamada interage com a IA e guarda em cache.
 *  2. Segunda chamada (mesmo prompt) consome do cache (devolve cached=true e hits=1).
 *  3. Diferentes tipos de contexto (theme vs plugin) não colidem.
 *  4. Ficheiros anexados ignoram/bypassam o cache para evitar respostas obsoletas.
 *
 * Executar:
 *   php tests/command_cache_test.php
 */

declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
require __DIR__ . '/ai_settings_guard.php'; // preserva/restaura a chave de IA real

use Illuminate\Support\Facades\Http;
use App\Models\StudioTheme;
use App\Models\StudioPlugin;
use App\Models\StudioSetting;
use App\Models\StudioAiCommandCache;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\PluginController;
use Illuminate\Http\UploadedFile;

// ── Helpers ──────────────────────────────────────────────────────
$passed = 0; $failed = 0; $errors = [];

function ok(string $m): void { global $passed; $passed++; echo "  \033[32m✓\033[0m {$m}\n"; }
function fail(string $m, string $d = ''): void { global $failed, $errors; $failed++; $errors[] = $m . ($d ? ": {$d}" : ''); echo "  \033[31m✗\033[0m {$m}" . ($d ? " — {$d}" : '') . "\n"; }
function section(string $t): void { echo "\n\033[1;34m▶ {$t}\033[0m\n"; }
function assert_true(bool $v, string $m, string $d = ''): void { $v ? ok($m) : fail($m, $d); }

// Fake HTTP
$GLOBALS['__ai_body'] = '';
Http::fake([
    'https://api.anthropic.com/*' => fn () => Http::response(
        json_encode(['content' => [['text' => $GLOBALS['__ai_body']]]]), 200
    ),
]);
function fake_ai(string $body): void { $GLOBALS['__ai_body'] = $body; }
function set_test_ai_key(): void {
    StudioSetting::set('ai_provider', 'claude');
    StudioSetting::set('ai_api_key', encrypt('test-key-mock'));
    StudioSetting::set('ai_api_key_claude', '');
    StudioSetting::set('ai_model', '');
}

// Limpar cache residual de testes anteriores
StudioAiCommandCache::truncate();

set_test_ai_key();

// ────────────────────────────────────────────────────────────────
//  1. Teste de Cache de Temas
// ────────────────────────────────────────────────────────────────
section('1. Testes de Caching de Temas');

$theme = StudioTheme::create([
    'name'          => 'test-theme-cache-' . uniqid(),
    'label'         => 'Tema Teste Cache',
    'version'       => '1.0.0',
    'status'        => 'draft',
    'colors'        => ['light' => ['--color-primary' => '#000000']],
    'fonts'         => ['heading' => 'Inter', 'body' => 'Inter'],
    'sections'      => [],
]);

$themeCtrl = new ThemeController();
$promptTheme = 'Cria uma paleta de cores escura e moderna';

// Limpar cache específico para garantir isolamento
$hashTheme = hash('sha256', trim(mb_strtolower($promptTheme)));
StudioAiCommandCache::where('context_type', 'theme')->where('prompt_hash', $hashTheme)->delete();

fake_ai("Mudei as cores!\n```json_updates\n" . json_encode([
    'colors' => ['light' => ['--color-primary' => '#121212']],
]) . "\n```");

try {
    // 1ª chamada (Deve chamar a IA e criar o cache)
    $req1 = \Illuminate\Http\Request::create("/themes/{$theme->uuid}/chat", 'POST', ['message' => $promptTheme]);
    $req1->setLaravelSession(app('session.store'));
    
    $resp1 = $themeCtrl->chat($req1, $theme->uuid);
    $data1 = json_decode($resp1->getContent(), true);

    assert_true($resp1->getStatusCode() === 200, 'Primeira chamada responde 200');
    assert_true(!isset($data1['cached']), 'Primeira chamada não deve vir marcada como cached');
    
    $cacheEntry = StudioAiCommandCache::getResolution('theme', $promptTheme);
    assert_true($cacheEntry !== null, 'Resolução foi devidamente guardada na BD de cache');
    assert_true($cacheEntry->hits === 0, 'Hits inicial deve ser 0');

    // Mudar o mock de IA. Se a segunda chamada chamar a IA, obteremos este texto. Se vier do cache, obteremos o antigo.
    fake_ai("Texto novo que não deve aparecer por causa do cache.");

    // 2ª chamada (Deve atingir o cache)
    $req2 = \Illuminate\Http\Request::create("/themes/{$theme->uuid}/chat", 'POST', ['message' => $promptTheme]);
    $req2->setLaravelSession(app('session.store'));
    
    $resp2 = $themeCtrl->chat($req2, $theme->uuid);
    $data2 = json_decode($resp2->getContent(), true);

    assert_true($resp2->getStatusCode() === 200, 'Segunda chamada responde 200');
    assert_true(($data2['cached'] ?? false) === true, 'Resposta retornada com cached=true');
    assert_true(str_contains($data2['reply'] ?? '', 'Mudei as cores'), 'Resposta recuperada do cache original');
    
    $cacheEntryFresh = StudioAiCommandCache::getResolution('theme', $promptTheme);
    assert_true($cacheEntryFresh->hits === 1, 'Hits incrementado para 1');

} catch (\Throwable $e) {
    fail('Erro na secção de Temas', $e->getMessage() . "\n" . $e->getTraceAsString());
}

// ────────────────────────────────────────────────────────────────
//  2. Teste de Cache de Plugins
// ────────────────────────────────────────────────────────────────
section('2. Testes de Caching de Plugins');

$plugin = StudioPlugin::create([
    'name'    => 'test-plugin-cache-' . uniqid(),
    'label'   => 'Plugin Teste Cache',
    'version' => '1.0.0',
    'status'  => 'draft',
    'hooks'   => ['page.render'],
]);

$pluginCtrl = new PluginController();
$promptPlugin = 'Cria um widget de contador simples';

// Limpar cache específico
$hashPlugin = hash('sha256', trim(mb_strtolower($promptPlugin)));
StudioAiCommandCache::where('context_type', 'plugin')->where('prompt_hash', $hashPlugin)->delete();

fake_ai("Código de contador gerado!\n```json_updates\n" . json_encode([
    'widget_blade' => '<div>Contador</div>',
]) . "\n```");

try {
    // 1ª chamada
    $reqP1 = \Illuminate\Http\Request::create("/plugins/{$plugin->uuid}/chat", 'POST', ['message' => $promptPlugin]);
    $reqP1->setLaravelSession(app('session.store'));
    
    $respP1 = $pluginCtrl->chat($reqP1, $plugin->uuid);
    $dataP1 = json_decode($respP1->getContent(), true);

    assert_true($respP1->getStatusCode() === 200, 'Primeira chamada do plugin responde 200');
    assert_true(!isset($dataP1['cached']), 'Primeira chamada do plugin não vem do cache');

    $cacheEntryP = StudioAiCommandCache::getResolution('plugin', $promptPlugin);
    assert_true($cacheEntryP !== null, 'Plugin cache guardado com sucesso');

    // Mudar mock
    fake_ai("Novo código alternativo de IA");

    // 2ª chamada
    $reqP2 = \Illuminate\Http\Request::create("/plugins/{$plugin->uuid}/chat", 'POST', ['message' => $promptPlugin]);
    $reqP2->setLaravelSession(app('session.store'));
    
    $respP2 = $pluginCtrl->chat($reqP2, $plugin->uuid);
    $dataP2 = json_decode($respP2->getContent(), true);

    assert_true($respP2->getStatusCode() === 200, 'Segunda chamada responde 200');
    assert_true(($dataP2['cached'] ?? false) === true, 'Plugin respondido do cache (cached=true)');
    assert_true(str_contains($dataP2['reply'] ?? '', 'Código de contador gerado'), 'Retornou a resposta cacheada');

    $cacheEntryPFresh = StudioAiCommandCache::getResolution('plugin', $promptPlugin);
    assert_true($cacheEntryPFresh->hits === 1, 'Hits do plugin incrementado para 1');

} catch (\Throwable $e) {
    fail('Erro na secção de Plugins', $e->getMessage() . "\n" . $e->getTraceAsString());
}

// ────────────────────────────────────────────────────────────────
//  3. Teste de Bypass de Cache com Ficheiros Anexados
// ────────────────────────────────────────────────────────────────
section('3. Testes de Bypass com Ficheiros (Attachments)');

try {
    fake_ai("Fiz a modificação com base na imagem!");
    
    // Criar um ficheiro mockado para upload
    $file = UploadedFile::fake()->image('logo.png');
    
    // Envia o mesmo prompt anterior mas com ficheiro anexo
    $reqBypass = \Illuminate\Http\Request::create("/themes/{$theme->uuid}/chat", 'POST', [
        'message' => $promptTheme,
    ], [], [
        'files' => [$file]
    ]);
    $reqBypass->setLaravelSession(app('session.store'));

    $respBypass = $themeCtrl->chat($reqBypass, $theme->uuid);
    $dataBypass = json_decode($respBypass->getContent(), true);

    assert_true($respBypass->getStatusCode() === 200, 'Chamada com anexo responde 200');
    assert_true(!isset($dataBypass['cached']), 'Ignorou o cache devido ao anexo');
    assert_true(str_contains($dataBypass['reply'] ?? '', 'imagem'), 'Chamou a IA e obteve a resposta baseada na imagem');
    
    // Hits não deve ter alterado
    $cacheEntryBypassCheck = StudioAiCommandCache::getResolution('theme', $promptTheme);
    assert_true($cacheEntryBypassCheck->hits === 1, 'Hits mantêm-se em 1 (cache não foi incrementado nem lido)');

} catch (\Throwable $e) {
    fail('Erro na secção de Bypass com ficheiros', $e->getMessage() . "\n" . $e->getTraceAsString());
}

// Limpeza dos registos criados nos testes
$theme->forceDelete();
$plugin->forceDelete();
StudioAiCommandCache::truncate();
ok('Limpeza de dados de teste efetuada');

// ── Sumário ──
echo "\n" . str_repeat('─', 55) . "\n";
$total = $passed + $failed;
echo "\033[1m  Total: {$total}  Passed: \033[32m{$passed}\033[0m\033[1m  Failed: \033[" . ($failed > 0 ? '31' : '32') . "m{$failed}\033[0m\n";
if ($failed > 0) {
    echo "\n\033[31mTestes falhados:\033[0m\n";
    foreach ($errors as $e) echo "  • {$e}\n";
}
echo str_repeat('─', 55) . "\n\n";
exit($failed > 0 ? 1 : 0);
