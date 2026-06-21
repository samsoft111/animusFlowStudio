<?php

/**
 * Testes — Modo Construção de PLUGINS (pipeline multi-agente, estilo Claude)
 *
 * Cobre:
 *  1.  AIEngine::pluginAgents — catálogo (logic, widget, settings)
 *  2.  AIEngine — system prompts (buildPluginPlan, pluginAgentSystem, verifyPlugin)
 *  3.  AIEngine::buildPluginPlan — mock + fallback
 *  4.  AIEngine::runPluginAgent — mock + parsing
 *  5.  AIEngine::verifyPlugin — mock + filtragem
 *  6.  AIEngine::chatPlugin — detecção de intenção (directiva build)
 *  7.  PluginController::buildPlan/buildStep/buildVerify — validação + auto-save + 422/is_fatal
 *  8.  PluginController::isFatalAiError — classificação (reflexão)
 *  9.  Rotas /plugins/{uuid}/build/* dentro de auth
 *  10. PluginController::edit passa pluginAgents
 *  11. Plugins/Edit.vue — UI inline estilo Claude
 *
 * Executar: php tests/plugin_build_test.php
 * Paridade com tests/build_mode_test.php (temas).
 */

declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
require __DIR__ . '/ai_settings_guard.php'; // preserva/restaura a chave de IA real

use Illuminate\Support\Facades\Http;
use App\Models\StudioPlugin;
use App\Models\StudioSetting;
use App\Services\AIEngine;
use App\Http\Controllers\PluginController;

$passed = 0; $failed = 0; $errors = [];
function ok(string $m): void { global $passed; $passed++; echo "  \033[32m✓\033[0m {$m}\n"; }
function fail(string $m, string $d = ''): void { global $failed, $errors; $failed++; $errors[] = $m . ($d ? ": {$d}" : ''); echo "  \033[31m✗\033[0m {$m}" . ($d ? " — {$d}" : '') . "\n"; }
function section(string $t): void { echo "\n\033[1;34m▶ {$t}\033[0m\n"; }
function assert_contains(string $h, string $n, string $m): void { str_contains($h, $n) ? ok($m) : fail($m, "esperado: «{$n}»"); }
function assert_true(bool $v, string $m, string $d = ''): void { $v ? ok($m) : fail($m, $d); }
function assert_false(bool $v, string $m): void { !$v ? ok($m) : fail($m); }

// Fake HTTP único com corpo mutável (Http::fake é first-wins)
$GLOBALS['__ai_body'] = '';
Http::fake([
    'https://api.anthropic.com/*' => fn () => Http::response(json_encode(['content' => [['text' => $GLOBALS['__ai_body']]]]), 200),
    'https://api.openai.com/*'    => fn () => Http::response(json_encode(['choices' => [['message' => ['content' => $GLOBALS['__ai_body']]]]]), 200),
]);
function fake_ai(string $b): void { $GLOBALS['__ai_body'] = $b; }
function set_test_ai_key(): void {
    StudioSetting::set('ai_provider', 'claude');
    StudioSetting::set('ai_api_key', encrypt('test-key-mock'));
    StudioSetting::set('ai_api_key_claude', '');
    StudioSetting::set('ai_model', '');
}
function make_req(string $uuid, array $params): \Illuminate\Http\Request {
    $r = \Illuminate\Http\Request::create("/plugins/{$uuid}/x", 'POST', $params);
    $r->setLaravelSession(app('session.store'));
    return $r;
}

$aiFile   = file_get_contents($root . '/app/Services/AIEngine.php');
$ctrlFile = file_get_contents($root . '/app/Http/Controllers/PluginController.php');
$routes   = file_get_contents($root . '/routes/web.php');

// ────────────────────────────────────────────────────────────────
section('1. AIEngine::pluginAgents — catálogo');

$agents = AIEngine::pluginAgents();
assert_true(is_array($agents) && count($agents) === 3, 'pluginAgents() devolve 3 agentes', 'tem ' . count($agents));
$ids = array_column($agents, 'id');
foreach (['logic', 'widget', 'settings'] as $id) {
    assert_true(in_array($id, $ids, true), "Agente '{$id}' presente");
}
$structured = true;
foreach ($agents as $a) { if (!isset($a['id'], $a['icon'], $a['label'], $a['hint'])) $structured = false; }
assert_true($structured, 'Cada agente tem id, icon, label e hint');

// ────────────────────────────────────────────────────────────────
section('2. AIEngine — métodos e system prompts');

assert_contains($aiFile, 'function buildPluginPlan',  'buildPluginPlan() existe');
assert_contains($aiFile, 'function runPluginAgent',   'runPluginAgent() existe');
assert_contains($aiFile, 'function verifyPlugin',     'verifyPlugin() existe');
assert_contains($aiFile, 'function pluginAgentSystem','pluginAgentSystem() existe');
assert_contains($aiFile, 'ORQUESTRADOR de construção de plugins', 'Planner de plugins identificado');
assert_contains($aiFile, 'VERIFICADOR de qualidade de plugins',   'Verificador de plugins identificado');
assert_contains($aiFile, 'declare(strict_types=1)', 'Agente logic instruído a gerar PHP completo');

// ────────────────────────────────────────────────────────────────
section('3. AIEngine::buildPluginPlan — orquestrador');

set_test_ai_key();
fake_ai("```json_updates\n" . json_encode(['direction' => 'Plugin de barra de anúncio', 'agents' => ['logic', 'widget']]) . "\n```");
try {
    $plan = AIEngine::buildPluginPlan('Plugin de barra de anúncio', '');
    assert_true(isset($plan['direction']) && isset($plan['agents']), 'Plano tem direction e agents');
    assert_true($plan['agents'] === ['logic', 'widget'], 'Agents preservam ordem da IA');
} catch (\Throwable $e) { fail('buildPluginPlan lançou excepção', $e->getMessage()); }

fake_ai('sem json');
try {
    $fb = AIEngine::buildPluginPlan('x', '');
    assert_true($fb['agents'] === ['logic', 'widget', 'settings'], 'Fallback usa os 3 agentes por defeito');
} catch (\Throwable $e) { fail('buildPluginPlan (fallback) excepção', $e->getMessage()); }

fake_ai("```json_updates\n" . json_encode(['direction' => 'x', 'agents' => ['logic', 'inexistente', 'settings']]) . "\n```");
try {
    $f = AIEngine::buildPluginPlan('x', '');
    assert_true($f['agents'] === ['logic', 'settings'], 'IDs inválidos são removidos');
} catch (\Throwable $e) { fail('buildPluginPlan (filtro) excepção', $e->getMessage()); }

// ────────────────────────────────────────────────────────────────
section('4. AIEngine::runPluginAgent — execução');

set_test_ai_key();
fake_ai("Gerei a classe PHP.\n```json_updates\n" . json_encode([
    'plugin_php' => '<?php declare(strict_types=1); class X {}',
    'hooks'      => ['page.render'],
]) . "\n```");
try {
    $r = AIEngine::runPluginAgent('logic', 'brief', 'dir', '{}', [], '');
    assert_true($r['agent'] === 'logic', 'Devolve o id do agente');
    assert_true(is_array($r['updates']), 'Parseia json_updates');
    assert_true(($r['updates']['hooks'][0] ?? '') === 'page.render', 'updates contém os hooks');
    assert_false(str_contains($r['reply'], 'json_updates'), 'reply remove o bloco');
} catch (\Throwable $e) { fail('runPluginAgent excepção', $e->getMessage()); }

// ────────────────────────────────────────────────────────────────
section('5. AIEngine::verifyPlugin — verificador');

set_test_ai_key();
fake_ai("```json_updates\n" . json_encode([
    'summary' => 'Falta widget.',
    'issues'  => [['agent' => 'widget', 'reason' => 'Criar o widget.'], ['agent' => 'fantasma', 'reason' => 'ignorar']],
]) . "\n```");
try {
    $v = AIEngine::verifyPlugin('b', 'd', '{}');
    assert_true(str_contains($v['summary'], 'widget'), 'Devolve summary');
    assert_true(count($v['issues']) === 1, 'Filtra ids inválidos (1 válido)');
    assert_true(($v['issues'][0]['agent'] ?? '') === 'widget', 'Mantém agente válido');
} catch (\Throwable $e) { fail('verifyPlugin excepção', $e->getMessage()); }

// ────────────────────────────────────────────────────────────────
section('6. AIEngine::chatPlugin — detecção de intenção (build)');

assert_contains($aiFile, '```build', 'chatPlugin deteta o bloco build');
set_test_ai_key();
fake_ai("Vou construir o teu plugin.\n```build\n" . json_encode(['brief' => 'Plugin de newsletter']) . "\n```");
try {
    $c = AIEngine::chatPlugin([['role' => 'user', 'content' => 'cria um plugin de newsletter']], '{}', []);
    assert_true(isset($c['build']) && is_array($c['build']), 'chatPlugin devolve directiva build');
    assert_true(str_contains($c['build']['brief'] ?? '', 'newsletter'), 'build.brief extraído');
    assert_true($c['updates'] === null, 'Sem json_updates numa directiva build');
    assert_false(str_contains($c['reply'], '```build'), 'Bloco build removido da reply');
} catch (\Throwable $e) { fail('chatPlugin build excepção', $e->getMessage()); }

// ────────────────────────────────────────────────────────────────
section('7. PluginController — buildPlan / buildStep / buildVerify');

assert_contains($ctrlFile, 'public function buildPlan',   'buildPlan() existe');
assert_contains($ctrlFile, 'public function buildStep',   'buildStep() existe');
assert_contains($ctrlFile, 'public function buildVerify', 'buildVerify() existe');
assert_contains($ctrlFile, 'applyPluginUpdates',          'applyPluginUpdates (auto-save)');
assert_contains($ctrlFile, "'is_fatal' => self::isFatalAiError", 'Erros marcam is_fatal');
assert_contains($ctrlFile, "Rule::in(\$validIds)",        'buildStep valida agent com Rule::in');

$plugin = StudioPlugin::create([
    'name'            => 'test-build-plugin-' . uniqid(),
    'label'           => 'Test Build Plugin',
    'description'     => 'Plugin de teste',
    'version'         => '1.0.0',
    'hooks'           => ['page.render'],
    'plugin_php'      => '<?php // v1',
    'widget_blade'    => '',
    'widget_js'       => '',
    'custom_css'      => '',
    'settings_schema' => [],
    'status'          => 'draft',
]);
$ctrl = new PluginController();

// buildPlan
set_test_ai_key();
fake_ai("```json_updates\n" . json_encode(['direction' => 'd', 'agents' => ['logic', 'widget']]) . "\n```");
try {
    $resp = $ctrl->buildPlan(make_req($plugin->uuid, ['brief' => 'Brief']), $plugin->uuid);
    $d = json_decode($resp->getContent(), true);
    assert_true($resp->getStatusCode() === 200, 'buildPlan responde 200');
    assert_true(($d['agents'] ?? []) === ['logic', 'widget'], 'buildPlan devolve agents');
} catch (\Throwable $e) { fail('buildPlan excepção', $e->getMessage()); }

// buildStep aplica + auto-save
set_test_ai_key();
fake_ai("Feito.\n```json_updates\n" . json_encode(['plugin_php' => '<?php // v2', 'hooks' => ['page.render', 'content.publish']]) . "\n```");
try {
    $resp = $ctrl->buildStep(make_req($plugin->uuid, ['agent' => 'logic', 'brief' => 'b', 'direction' => 'd']), $plugin->uuid);
    $d = json_decode($resp->getContent(), true);
    assert_true($resp->getStatusCode() === 200, 'buildStep responde 200');
    assert_true(($d['applied'] ?? false) === true, 'buildStep aplica (auto-save)');
    $fresh = StudioPlugin::where('uuid', $plugin->uuid)->first();
    assert_true($fresh->plugin_php === '<?php // v2', 'BD: plugin_php persistido');
    assert_true(in_array('content.publish', $fresh->hooks ?? [], true), 'BD: hooks persistidos');
} catch (\Throwable $e) { fail('buildStep excepção', $e->getMessage()); }

// agent inválido
try {
    $ctrl->buildStep(make_req($plugin->uuid, ['agent' => 'inexistente']), $plugin->uuid);
    fail('buildStep deveria rejeitar agente inválido');
} catch (\Illuminate\Validation\ValidationException $e) {
    ok('buildStep rejeita agente inválido (Rule::in)');
} catch (\Throwable $e) { fail('Excepção inesperada em buildStep', get_class($e)); }

// buildVerify
set_test_ai_key();
fake_ai("```json_updates\n" . json_encode(['summary' => 'Falta CSS.', 'issues' => [['agent' => 'widget', 'reason' => 'Adicionar CSS.']]]) . "\n```");
try {
    $resp = $ctrl->buildVerify(make_req($plugin->uuid, ['brief' => 'b']), $plugin->uuid);
    $d = json_decode($resp->getContent(), true);
    assert_true($resp->getStatusCode() === 200, 'buildVerify responde 200');
    assert_true(count($d['issues'] ?? []) === 1, 'buildVerify devolve issues');
} catch (\Throwable $e) { fail('buildVerify excepção', $e->getMessage()); }

// 422 + is_fatal sem chave
StudioSetting::set('ai_api_key', '');
StudioSetting::set('ai_api_key_claude', '');
try {
    $resp = $ctrl->buildPlan(make_req($plugin->uuid, ['brief' => 'x']), $plugin->uuid);
    $d = json_decode($resp->getContent(), true);
    assert_true($resp->getStatusCode() === 422, 'buildPlan 422 sem chave');
    assert_true(($d['is_fatal'] ?? false) === true, 'is_fatal=true sem chave');
} catch (\Throwable $e) { fail('buildPlan (erro) excepção', $e->getMessage()); }

$plugin->forceDelete();
ok('Plugin de teste removido da BD');

// ────────────────────────────────────────────────────────────────
section('8. PluginController::isFatalAiError — reflexão');

$ref = new \ReflectionMethod(PluginController::class, 'isFatalAiError');
$ref->setAccessible(true);
$isFatal = fn (string $m): bool => $ref->invoke(null, new \RuntimeException($m));
assert_true($isFatal('Chave AI não configurada.'),    'Chave em falta → fatal');
assert_true($isFatal('cURL error 6'),                  'Erro de rede → fatal');
assert_true($isFatal('API error: 429 rate_limit'),     'Rate limit → fatal');
assert_false($isFatal('API error: 400 bad json'),      'Erro pontual (400) → NÃO fatal');

// ────────────────────────────────────────────────────────────────
section('9. Rotas /plugins/{uuid}/build/*');

assert_contains($routes, "plugins/{uuid}/build/plan",   'Rota build/plan existe');
assert_contains($routes, "plugins/{uuid}/build/step",   'Rota build/step existe');
assert_contains($routes, "plugins/{uuid}/build/verify", 'Rota build/verify existe');
assert_contains($routes, "->name('plugins.build.plan')",   'Nome plugins.build.plan');
assert_contains($routes, "->name('plugins.build.step')",   'Nome plugins.build.step');
assert_contains($routes, "->name('plugins.build.verify')", 'Nome plugins.build.verify');
$authStart = strpos($routes, "Route::middleware('auth')->group");
$pos       = strpos($routes, "plugins/{uuid}/build/plan");
$authEnd   = strrpos($routes, '});');
assert_true($authStart !== false && $pos !== false && $authStart < $pos && $pos < $authEnd, 'Rotas build/* dentro de auth');

// ────────────────────────────────────────────────────────────────
section('10. edit() expõe pluginAgents');

assert_contains($ctrlFile, "'pluginAgents' => AIEngine::pluginAgents()", 'edit() passa pluginAgents');

// ────────────────────────────────────────────────────────────────
section('11. Plugins/Edit.vue — UI inline estilo Claude');

$vue = file_get_contents($root . '/resources/js/Pages/Plugins/Edit.vue');
assert_contains($vue, 'pluginAgents:',   'Prop pluginAgents declarada');
assert_contains($vue, 'runBuildFlow',    'Orquestrador inline runBuildFlow');
assert_contains($vue, 'runBuildAgent',   'Executor runBuildAgent');
assert_contains($vue, 'PHASE_META',      'Mapa de fases PHASE_META');
assert_contains($vue, 'phaseLabel',      'Tradutor phaseLabel');
assert_contains($vue, 'A gerar a lógica do plugin', 'Fase legível: lógica');
assert_contains($vue, 'A criar a interface (widget)', 'Fase legível: widget');
assert_contains($vue, 'A construir o teu plugin',  'Estado legível: a construir');
assert_contains($vue, "type === 'build'",          'Mensagem tipo build no stream');
assert_contains($vue, 'Ver detalhes técnicos',     'Detalhe técnico recolhido');
assert_contains($vue, 'Aplicadas e guardadas automaticamente', 'Badge honesto');
assert_contains($vue, '/build/plan',   'Chama endpoint build/plan');
assert_contains($vue, '/build/step',   'Chama endpoint build/step');
assert_contains($vue, '/build/verify', 'Chama endpoint build/verify');
assert_true(!str_contains($vue, '>Guardar agora<'), 'Botão "Guardar agora" contraditório removido');

// ────────────────────────────────────────────────────────────────
section('12. Skill — upload + propagação ao pipeline de plugin');

// UI
assert_contains($vue, 'loadSkillFile',  'Função loadSkillFile');
assert_contains($vue, 'buildSkill',     'Estado buildSkill');
assert_contains($vue, 'skillFileInput', 'Input de ficheiro do skill');
assert_contains($vue, "fd.append('skill'", 'Envia skill ao pipeline');
// Backend aceita skill (system agora devolvido em [estável, variável])
assert_contains($aiFile, 'string $skill = \'\'): array', 'pluginAgentSystem aceita skill');
assert_contains($ctrlFile, "\$data['skill'] ?? ''", 'buildStep passa skill ao agente');

// Runtime: o skill chega ao system prompt do agente de plugin
set_test_ai_key();
fake_ai("ok\n```json_updates\n{}\n```");
$skillText = 'REGRA-PLUGIN-SKILL-7K2: usar sempre nonce nos forms';
try {
    AIEngine::runPluginAgent('logic', 'brief', 'dir', '{}', [], '', $skillText);
    $rec  = Http::recorded();
    $last = $rec[count($rec) - 1] ?? null;
    $body = $last ? json_decode($last[0]->body(), true) : [];
    $sys  = $body['system'] ?? '';
    // O system do Claude é agora um array de blocos: [estável (cache_control), variável]
    $sysText = is_array($sys) ? implode("\n", array_map(fn($b) => $b['text'] ?? '', $sys)) : $sys;
    assert_true(str_contains($sysText, $skillText), 'Skill aparece no system prompt do agente de plugin');
    assert_true(is_array($sys) && ($sys[0]['cache_control']['type'] ?? '') === 'ephemeral', 'Prompt caching: bloco estável marcado com cache_control ephemeral');
} catch (\Throwable $e) {
    fail('runPluginAgent com skill lançou excepção', $e->getMessage());
}

// ── Sumário ──
echo "\n" . str_repeat('─', 55) . "\n";
$total = $passed + $failed;
echo "\033[1m  Total: {$total}  Passed: \033[32m{$passed}\033[0m\033[1m  Failed: \033[" . ($failed > 0 ? '31' : '32') . "m{$failed}\033[0m\n";
if ($failed > 0) { echo "\n\033[31mFalhados:\033[0m\n"; foreach ($errors as $e) echo "  • {$e}\n"; }
echo str_repeat('─', 55) . "\n\n";
exit($failed > 0 ? 1 : 0);
