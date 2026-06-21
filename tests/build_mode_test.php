<?php

/**
 * Testes — Modo Construção (pipeline multi-agente de criação de temas)
 *
 * Cobre:
 *  1.  AIEngine::themeAgents — catálogo de agentes (single source of truth)
 *  2.  AIEngine — system prompts (buildThemePlan, themeAgentSystem, verifyTheme)
 *  3.  AIEngine::buildThemePlan — orquestrador (mock) + fallback de agentes
 *  4.  AIEngine::runThemeAgent — execução de um agente (mock) + parsing json_updates
 *  5.  AIEngine::verifyTheme — verificador (mock) + filtragem de ids inválidos
 *  6.  ThemeController::buildPlan — validação + resposta + 422 em erro de IA
 *  7.  ThemeController::buildStep — validação Rule::in + deep-merge de updates
 *  8.  ThemeController::buildVerify — sections_present + resposta
 *  9.  ThemeController::isFatalAiError — classificação de erros sistémicos (reflexão)
 *  10. Rotas /themes/{uuid}/build/{plan,step,verify} dentro de auth
 *  11. ThemeController::edit passa prop themeAgents
 *  12. Edit.vue — UI do Modo Construção e tratamento de is_fatal
 *  13. Build Vite
 *
 * Executar:
 *   php tests/build_mode_test.php
 *
 * Paridade com tests/inspire_category_test.php.
 */

declare(strict_types=1);

// ── Bootstrap Laravel ────────────────────────────────────────────
$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
require __DIR__ . '/ai_settings_guard.php'; // preserva/restaura a chave de IA real

use Illuminate\Support\Facades\Http;
use App\Models\StudioTheme;
use App\Models\StudioSetting;
use App\Services\AIEngine;
use App\Http\Controllers\ThemeController;

// ── Helpers ──────────────────────────────────────────────────────
$passed = 0;
$failed = 0;
$errors = [];

function ok(string $msg): void {
    global $passed;
    $passed++;
    echo "  \033[32m✓\033[0m {$msg}\n";
}

function fail(string $msg, string $detail = ''): void {
    global $failed, $errors;
    $failed++;
    $errors[] = $msg . ($detail ? ": {$detail}" : '');
    echo "  \033[31m✗\033[0m {$msg}" . ($detail ? " — {$detail}" : '') . "\n";
}

function section(string $title): void {
    echo "\n\033[1;34m▶ {$title}\033[0m\n";
}

function assert_contains(string $haystack, string $needle, string $msg): void {
    if (str_contains($haystack, $needle)) ok($msg);
    else fail($msg, "esperado: «{$needle}»");
}

function assert_true(bool $val, string $msg, string $detail = ''): void {
    if ($val) ok($msg);
    else fail($msg, $detail);
}

function assert_false(bool $val, string $msg): void {
    if (!$val) ok($msg);
    else fail($msg);
}

// Helper: configura uma chave AI de teste (provider claude)
function set_test_ai_key(): void {
    StudioSetting::set('ai_provider', 'claude');
    StudioSetting::set('ai_api_key', encrypt('test-key-mock'));
    StudioSetting::set('ai_api_key_claude', ''); // garante uso da legacy
    StudioSetting::set('ai_model', '');
}

// Registo ÚNICO do fake HTTP. O Laravel usa o PRIMEIRO stub que casa com um URL
// (first-wins), por isso chamar Http::fake() repetidamente não substitui o stub.
// Em vez disso, registamos um closure que lê um corpo mutável em $GLOBALS.
$GLOBALS['__ai_body'] = '';
Http::fake([
    'https://api.anthropic.com/*' => fn () => Http::response(
        json_encode(['content' => [['text' => $GLOBALS['__ai_body']]]]), 200
    ),
    'https://api.openai.com/*' => fn () => Http::response(
        json_encode(['choices' => [['message' => ['content' => $GLOBALS['__ai_body']]]]]), 200
    ),
]);

// Helper: define o corpo que a próxima chamada de IA vai devolver.
function fake_ai(string $body): void {
    $GLOBALS['__ai_body'] = $body;
}

$aiFile         = file_get_contents($root . '/app/Services/AIEngine.php');
$controllerFile = file_get_contents($root . '/app/Http/Controllers/ThemeController.php');
$routesFile     = file_get_contents($root . '/routes/web.php');

// ────────────────────────────────────────────────────────────────
//  1. AIEngine::themeAgents — catálogo
// ────────────────────────────────────────────────────────────────
section('1. AIEngine::themeAgents — catálogo de agentes');

$agents = AIEngine::themeAgents();
assert_true(is_array($agents) && count($agents) === 4, 'themeAgents() devolve 4 agentes', 'tem ' . count($agents));

$ids = array_column($agents, 'id');
foreach (['design', 'intro', 'conversion', 'code'] as $expectedId) {
    assert_true(in_array($expectedId, $ids, true), "Agente '{$expectedId}' presente");
}

$allStructured = true;
foreach ($agents as $a) {
    if (!isset($a['id'], $a['icon'], $a['label'], $a['hint'])) $allStructured = false;
}
assert_true($allStructured, 'Cada agente tem id, icon, label e hint');

// ────────────────────────────────────────────────────────────────
//  2. AIEngine — system prompts
// ────────────────────────────────────────────────────────────────
section('2. AIEngine — métodos e system prompts');

assert_contains($aiFile, 'function buildThemePlan', 'buildThemePlan() existe');
assert_contains($aiFile, 'function runThemeAgent',  'runThemeAgent() existe');
assert_contains($aiFile, 'function verifyTheme',    'verifyTheme() existe');
assert_contains($aiFile, 'function themeAgentSystem', 'themeAgentSystem() existe');
assert_contains($aiFile, 'function chatDispatch',   'chatDispatch() existe');

assert_contains($aiFile, 'ORQUESTRADOR', 'Planner é um orquestrador');
assert_contains($aiFile, '"direction"', 'Plano define direction');
assert_contains($aiFile, '"agents"',    'Plano define agents');
assert_contains($aiFile, 'VERIFICADOR', 'Verificador identificado no prompt');
assert_contains($aiFile, '"issues"',    'Verificador devolve issues');
assert_contains($aiFile, 'json_updates', 'Agentes usam blocos json_updates');
assert_contains($aiFile, 'var(--color-primary)', 'Agentes instruídos a usar variáveis CSS');

// Os 4 agentes têm uma tarefa especializada
assert_contains($aiFile, "'design' =>",     'Tarefa do agente design definida');
assert_contains($aiFile, "'intro' =>",      'Tarefa do agente intro definida');
assert_contains($aiFile, "'conversion' =>", 'Tarefa do agente conversion definida');
assert_contains($aiFile, "'code' =>",       'Tarefa do agente code definida');

// ────────────────────────────────────────────────────────────────
//  3. AIEngine::buildThemePlan — mock + fallback
// ────────────────────────────────────────────────────────────────
section('3. AIEngine::buildThemePlan — orquestrador');

set_test_ai_key();

fake_ai("Aqui está o plano:\n```json_updates\n" . json_encode([
    'direction' => 'Estilo moderno e arrojado para uma startup SaaS.',
    'agents'    => ['design', 'intro', 'conversion'],
]) . "\n```");

try {
    $plan = AIEngine::buildThemePlan('Tema para startup SaaS de IA', '');
    assert_true(isset($plan['direction']), 'Plano tem direction');
    assert_true(isset($plan['agents']) && is_array($plan['agents']), 'Plano tem agents (array)');
    assert_true(str_contains($plan['direction'], 'SaaS'), 'Direction reflecte o brief');
    assert_true($plan['agents'] === ['design', 'intro', 'conversion'], 'Agents preservam ordem da IA');
} catch (\Throwable $e) {
    fail('buildThemePlan lançou excepção', $e->getMessage());
}

// Fallback: IA devolve lixo → usa os 4 agentes por defeito
fake_ai('Não consegui produzir json válido.');
try {
    $planFb = AIEngine::buildThemePlan('Outro brief', '');
    assert_true($planFb['agents'] === ['design', 'intro', 'conversion', 'code'], 'Fallback usa os 4 agentes por defeito');
} catch (\Throwable $e) {
    fail('buildThemePlan (fallback) lançou excepção', $e->getMessage());
}

// IDs inválidos são filtrados
fake_ai("```json_updates\n" . json_encode([
    'direction' => 'x',
    'agents'    => ['design', 'inexistente', 'code'],
]) . "\n```");
try {
    $planFilter = AIEngine::buildThemePlan('brief', '');
    assert_true($planFilter['agents'] === ['design', 'code'], 'IDs de agentes inválidos são removidos');
} catch (\Throwable $e) {
    fail('buildThemePlan (filtro) lançou excepção', $e->getMessage());
}

// ────────────────────────────────────────────────────────────────
//  4. AIEngine::runThemeAgent — mock + parsing
// ────────────────────────────────────────────────────────────────
section('4. AIEngine::runThemeAgent — execução de um agente');

set_test_ai_key();
fake_ai("Defini a paleta e as fontes.\n```json_updates\n" . json_encode([
    'colors' => ['light' => ['--color-primary' => '#6366f1']],
    'fonts'  => ['heading' => 'Outfit', 'body' => 'Inter'],
]) . "\n```");

try {
    $res = AIEngine::runThemeAgent('design', 'brief saas', 'moderno', '{}', [], '');
    assert_true($res['agent'] === 'design', 'Devolve o id do agente');
    assert_true(is_array($res['updates']), 'Parseia o bloco json_updates');
    assert_true(($res['updates']['colors']['light']['--color-primary'] ?? '') === '#6366f1', 'updates contém as cores geradas');
    assert_true(($res['updates']['fonts']['heading'] ?? '') === 'Outfit', 'updates contém as fontes geradas');
    assert_false(str_contains($res['reply'], 'json_updates'), 'reply visível remove o bloco json_updates');
    assert_true(str_contains($res['reply'], 'paleta'), 'reply preserva o texto humano');
} catch (\Throwable $e) {
    fail('runThemeAgent lançou excepção', $e->getMessage());
}

// Sem bloco json_updates → updates null
fake_ai('Apenas texto, sem alterações.');
try {
    $resNull = AIEngine::runThemeAgent('code', 'b', 'd', '{}', [], '');
    assert_true($resNull['updates'] === null, 'Sem json_updates → updates null');
} catch (\Throwable $e) {
    fail('runThemeAgent (null) lançou excepção', $e->getMessage());
}

// ────────────────────────────────────────────────────────────────
//  5. AIEngine::verifyTheme — mock + filtragem
// ────────────────────────────────────────────────────────────────
section('5. AIEngine::verifyTheme — verificador de qualidade');

set_test_ai_key();
fake_ai("```json_updates\n" . json_encode([
    'summary' => 'O tema está bom mas faltam testemunhos.',
    'issues'  => [
        ['agent' => 'intro', 'reason' => 'Adicionar secção de testemunhos.'],
        ['agent' => 'fantasma', 'reason' => 'id inválido deve ser ignorado.'],
        ['agent' => 'conversion', 'reason' => 'Reforçar o CTA.'],
    ],
]) . "\n```");

try {
    $vr = AIEngine::verifyTheme('brief', 'direcao', '{"sections_present":["hero"]}');
    assert_true(str_contains($vr['summary'], 'testemunhos'), 'Verificador devolve summary');
    assert_true(count($vr['issues']) === 2, 'Issues com ids inválidos são filtrados (2 válidos)');
    $issueAgents = array_column($vr['issues'], 'agent');
    assert_true(in_array('intro', $issueAgents, true) && in_array('conversion', $issueAgents, true), 'Mantém apenas agentes válidos');
    assert_false(in_array('fantasma', $issueAgents, true), 'Agente inválido removido das issues');
} catch (\Throwable $e) {
    fail('verifyTheme lançou excepção', $e->getMessage());
}

// Tema bom → sem issues
fake_ai("```json_updates\n" . json_encode(['summary' => 'Tema completo.', 'issues' => []]) . "\n```");
try {
    $vrOk = AIEngine::verifyTheme('b', 'd', '{}');
    assert_true($vrOk['issues'] === [], 'Tema bom devolve issues vazio');
} catch (\Throwable $e) {
    fail('verifyTheme (sem issues) lançou excepção', $e->getMessage());
}

// ────────────────────────────────────────────────────────────────
//  6-8. ThemeController — buildPlan / buildStep / buildVerify
// ────────────────────────────────────────────────────────────────
section('6. ThemeController — métodos do Modo Construção');

assert_contains($controllerFile, 'public function buildPlan',   'buildPlan() existe no controller');
assert_contains($controllerFile, 'public function buildStep',   'buildStep() existe no controller');
assert_contains($controllerFile, 'public function buildVerify', 'buildVerify() existe no controller');
assert_contains($controllerFile, 'applyThemeUpdates',           'Reusa applyThemeUpdates (deep-merge)');
assert_contains($controllerFile, 'array_replace_recursive',     'Deep-merge via array_replace_recursive');
assert_contains($controllerFile, "'is_fatal' => self::isFatalAiError", 'Erros de IA marcam is_fatal');
assert_contains($controllerFile, 'sections_present',            'buildVerify envia sections_present (sem HTML pesado)');
assert_contains($controllerFile, "Rule::in(\$validIds)",        'buildStep valida agent com Rule::in');

// Tema de teste
$theme = StudioTheme::create([
    'name'          => 'test-build-' . uniqid(),
    'label'         => 'Test Build Theme',
    'description'   => 'Tema de teste do Modo Construção',
    'version'       => '1.0.0',
    'colors'        => ['light' => ['--color-primary' => '#000000'], 'dark' => ['--color-primary' => '#ffffff']],
    'fonts'         => ['heading' => 'Inter', 'body' => 'Inter'],
    'sections'      => ['hero' => '<section>v1</section>'],
    'layout_config' => ['header_type' => 'glass'],
    'capabilities'  => ['animations' => true],
    'assets'        => [],
    'components'    => [],
    'variants'      => [],
    'custom_css'    => '',
    'custom_js'     => '',
    'status'        => 'draft',
]);

$ctrl = new ThemeController();

// ── buildPlan ──
section('6.1 buildPlan — resposta e validação');
set_test_ai_key();
fake_ai("```json_updates\n" . json_encode(['direction' => 'd', 'agents' => ['design', 'code']]) . "\n```");

$reqPlan = \Illuminate\Http\Request::create("/themes/{$theme->uuid}/build/plan", 'POST', ['brief' => 'Brief de teste']);
$reqPlan->setLaravelSession(app('session.store'));
try {
    $respPlan = $ctrl->buildPlan($reqPlan, $theme->uuid);
    $dPlan = json_decode($respPlan->getContent(), true);
    assert_true($respPlan->getStatusCode() === 200, 'buildPlan responde 200');
    assert_true(($dPlan['agents'] ?? []) === ['design', 'code'], 'buildPlan devolve agents do plano');
} catch (\Throwable $e) {
    fail('buildPlan lançou excepção', $e->getMessage());
}

// brief obrigatório
$reqPlanBad = \Illuminate\Http\Request::create("/themes/{$theme->uuid}/build/plan", 'POST', []);
$reqPlanBad->setLaravelSession(app('session.store'));
try {
    $ctrl->buildPlan($reqPlanBad, $theme->uuid);
    fail('buildPlan deveria exigir brief');
} catch (\Illuminate\Validation\ValidationException $e) {
    ok('buildPlan exige brief (ValidationException)');
} catch (\Throwable $e) {
    fail('Tipo de excepção inesperado em buildPlan', get_class($e));
}

// 422 em erro de IA (sem chave)
StudioSetting::set('ai_api_key', '');
StudioSetting::set('ai_api_key_claude', '');
$reqPlanErr = \Illuminate\Http\Request::create("/themes/{$theme->uuid}/build/plan", 'POST', ['brief' => 'x']);
$reqPlanErr->setLaravelSession(app('session.store'));
try {
    $respErr = $ctrl->buildPlan($reqPlanErr, $theme->uuid);
    $dErr = json_decode($respErr->getContent(), true);
    assert_true($respErr->getStatusCode() === 422, 'buildPlan devolve 422 sem chave AI');
    assert_true(isset($dErr['error']) && isset($dErr['is_fatal']), 'Erro inclui error + is_fatal');
    assert_true($dErr['is_fatal'] === true, 'Falta de chave é classificada como fatal');
} catch (\Throwable $e) {
    fail('buildPlan (erro) lançou excepção', $e->getMessage());
}

// ── buildStep ──
section('7. buildStep — execução de agente + deep-merge');
set_test_ai_key();
fake_ai("Fiz o design.\n```json_updates\n" . json_encode([
    'colors' => ['light' => ['--color-accent' => '#8b5cf6']], // só accent: primary deve persistir
]) . "\n```");

$reqStep = \Illuminate\Http\Request::create("/themes/{$theme->uuid}/build/step", 'POST', [
    'agent'     => 'design',
    'brief'     => 'Brief',
    'direction' => 'Moderno',
]);
$reqStep->setLaravelSession(app('session.store'));
try {
    $respStep = $ctrl->buildStep($reqStep, $theme->uuid);
    $dStep = json_decode($respStep->getContent(), true);
    assert_true($respStep->getStatusCode() === 200, 'buildStep responde 200');
    assert_true(($dStep['agent'] ?? '') === 'design', 'buildStep devolve o agente');
    assert_true(($dStep['applied'] ?? false) === true, 'buildStep aplica as alterações');

    $fresh = StudioTheme::where('uuid', $theme->uuid)->first();
    assert_true(($fresh->colors['light']['--color-accent'] ?? '') === '#8b5cf6', 'Nova cor accent gravada');
    assert_true(($fresh->colors['light']['--color-primary'] ?? '') === '#000000', 'Deep-merge: --color-primary preservado');
    assert_true(($fresh->colors['dark']['--color-primary'] ?? '') === '#ffffff', 'Deep-merge: cores dark preservadas');
} catch (\Throwable $e) {
    fail('buildStep lançou excepção', $e->getMessage());
}

// agent inválido → ValidationException
$reqStepBad = \Illuminate\Http\Request::create("/themes/{$theme->uuid}/build/step", 'POST', ['agent' => 'inexistente']);
$reqStepBad->setLaravelSession(app('session.store'));
try {
    $ctrl->buildStep($reqStepBad, $theme->uuid);
    fail('buildStep deveria rejeitar agente inválido');
} catch (\Illuminate\Validation\ValidationException $e) {
    ok('buildStep rejeita agente inválido (Rule::in)');
} catch (\Throwable $e) {
    fail('Tipo de excepção inesperado em buildStep', get_class($e));
}

// ── buildVerify ──
section('8. buildVerify — verificação');
set_test_ai_key();
fake_ai("```json_updates\n" . json_encode([
    'summary' => 'Falta CTA.',
    'issues'  => [['agent' => 'conversion', 'reason' => 'Adicionar CTA.']],
]) . "\n```");

$reqVer = \Illuminate\Http\Request::create("/themes/{$theme->uuid}/build/verify", 'POST', [
    'brief'     => 'Brief',
    'direction' => 'Moderno',
]);
$reqVer->setLaravelSession(app('session.store'));
try {
    $respVer = $ctrl->buildVerify($reqVer, $theme->uuid);
    $dVer = json_decode($respVer->getContent(), true);
    assert_true($respVer->getStatusCode() === 200, 'buildVerify responde 200');
    assert_true(str_contains($dVer['summary'] ?? '', 'CTA'), 'buildVerify devolve summary');
    assert_true(count($dVer['issues'] ?? []) === 1, 'buildVerify devolve issues');
    assert_true(($dVer['issues'][0]['agent'] ?? '') === 'conversion', 'Issue aponta para agente correcto');
} catch (\Throwable $e) {
    fail('buildVerify lançou excepção', $e->getMessage());
}

// Limpar tema de teste
$theme->forceDelete();
ok('Tema de teste removido da BD');

// ────────────────────────────────────────────────────────────────
//  9. isFatalAiError — classificação (reflexão)
// ────────────────────────────────────────────────────────────────
section('9. ThemeController::isFatalAiError — erros sistémicos');

$ref = new \ReflectionMethod(ThemeController::class, 'isFatalAiError');
$ref->setAccessible(true);
$isFatal = fn (string $m): bool => $ref->invoke(null, new \RuntimeException($m));

assert_true($isFatal('Chave AI não configurada. Vai a Definições.'), 'Chave em falta → fatal');
assert_true($isFatal('cURL error 6: Could not resolve host'),        'Erro de rede cURL → fatal');
assert_true($isFatal('API error: 429 rate_limit_exceeded'),          'Rate limit (429) → fatal');
assert_true($isFatal('API error: 401 unauthorized'),                 'Não autorizado (401) → fatal');
assert_true($isFatal('API error: 503 overloaded'),                   'Servidor sobrecarregado (503) → fatal');
assert_true($isFatal('API error: quota exhausted'),                  'Quota esgotada → fatal');
assert_false($isFatal('API error: 400 invalid json in request'),     'Erro pontual (400) → NÃO fatal');
assert_false($isFatal('Resposta sem bloco json_updates'),            'Erro de parsing → NÃO fatal');

// ────────────────────────────────────────────────────────────────
//  10. Rotas /themes/{uuid}/build/*
// ────────────────────────────────────────────────────────────────
section('10. Rotas do Modo Construção');

assert_contains($routesFile, "build/plan",   'Rota build/plan existe');
assert_contains($routesFile, "build/step",   'Rota build/step existe');
assert_contains($routesFile, "build/verify", 'Rota build/verify existe');
assert_contains($routesFile, "'buildPlan'",   'build/plan aponta para buildPlan');
assert_contains($routesFile, "'buildStep'",   'build/step aponta para buildStep');
assert_contains($routesFile, "'buildVerify'", 'build/verify aponta para buildVerify');
assert_contains($routesFile, "->name('themes.build.plan')",   'Nome themes.build.plan');
assert_contains($routesFile, "->name('themes.build.step')",   'Nome themes.build.step');
assert_contains($routesFile, "->name('themes.build.verify')", 'Nome themes.build.verify');

// Dentro do grupo auth
$authStart  = strpos($routesFile, "Route::middleware('auth')->group");
$buildPos   = strpos($routesFile, "build/plan");
$authEnd    = strrpos($routesFile, '});');
assert_true(
    $authStart !== false && $buildPos !== false && $authStart < $buildPos && $buildPos < $authEnd,
    'Rotas build/* estão dentro do grupo middleware auth'
);

// ────────────────────────────────────────────────────────────────
//  11. ThemeController::edit passa themeAgents
// ────────────────────────────────────────────────────────────────
section('11. edit() expõe themeAgents ao Vue');

assert_contains($controllerFile, "'themeAgents' => AIEngine::themeAgents()", 'edit() passa themeAgents como prop');

// ────────────────────────────────────────────────────────────────
//  12. Edit.vue — UI do Modo Construção
// ────────────────────────────────────────────────────────────────
section('12. Edit.vue — Modo Construção inline na conversa (estilo Claude)');

$vueFile = file_get_contents($root . '/resources/js/Pages/Themes/Edit.vue');

assert_contains($vueFile, 'themeAgents:',     'Prop themeAgents declarada');
assert_contains($vueFile, 'runBuildFlow',     'Orquestrador inline runBuildFlow');
assert_contains($vueFile, 'runBuildAgent',    'Executor de agente runBuildAgent');
assert_contains($vueFile, 'PHASE_META',       'Mapa de fases legíveis PHASE_META');
assert_contains($vueFile, 'phaseLabel',       'Tradutor agente→fase phaseLabel');

// Fases legíveis para o utilizador final (sem jargão técnico)
assert_contains($vueFile, 'A planear a construção',  'Fase legível: planear');
assert_contains($vueFile, 'A rever a qualidade',     'Fase legível: rever');
assert_contains($vueFile, 'A construir o teu tema',  'Estado legível: a construir');
assert_contains($vueFile, 'A definir estilo, cores', 'Fase legível: design');

// Cartão de construção inline + detalhes técnicos escondidos
assert_contains($vueFile, "type === 'build'",        'Mensagem tipo build no stream');
assert_contains($vueFile, 'Ver detalhes técnicos',   'Detalhe técnico recolhido (opcional)');

// Pipeline reaproveita os endpoints existentes
assert_contains($vueFile, '/build/plan',   'Chama endpoint build/plan');
assert_contains($vueFile, '/build/step',   'Chama endpoint build/step');
assert_contains($vueFile, '/build/verify', 'Chama endpoint build/verify');

// Abortamento inteligente em erro fatal
assert_contains($vueFile, 'isFatal',     'Trata is_fatal do servidor');
assert_contains($vueFile, 'is_fatal',    'Lê campo is_fatal da resposta');

// O painel técnico antigo foi removido
assert_true(!str_contains($vueFile, 'Modo Construção — agentes especializados'), 'Painel técnico antigo removido');
assert_true(!str_contains($vueFile, 'Continuar apesar de erros'), 'Checkbox técnico removido');

// ────────────────────────────────────────────────────────────────
//  12b. Skill — carregado por ficheiro e propagado ao pipeline
// ────────────────────────────────────────────────────────────────
section('12b. Skill — upload + propagação ao planeador e agentes');

// UI: upload de skill no chat
assert_contains($vueFile, 'loadSkillFile',  'Função loadSkillFile (lê ficheiro como texto)');
assert_contains($vueFile, 'buildSkill',     'Estado buildSkill');
assert_contains($vueFile, 'skillFileInput', 'Input de ficheiro do skill');
assert_contains($vueFile, "fd.append('skill'", 'Envia skill ao pipeline');

// Backend: assinaturas aceitam skill (system agora devolvido em [estável, variável])
assert_contains($aiFile, 'string $skill = \'\'): array', 'themeAgentSystem aceita skill');
assert_contains($controllerFile, "\$data['skill'] ?? ''", 'buildStep passa skill ao agente');

// Runtime: o skill chega ao system prompt do agente (via Http::recorded)
set_test_ai_key();
fake_ai("ok\n```json_updates\n{}\n```");
$skillText = 'REGRA-SKILL-UNICA-9X7: cantos sempre arredondados';
try {
    AIEngine::runThemeAgent('design', 'brief', 'dir', '{}', [], '', $skillText);
    $rec  = \Illuminate\Support\Facades\Http::recorded();
    $last = $rec[count($rec) - 1] ?? null;
    $body = $last ? json_decode($last[0]->body(), true) : [];
    $sys  = $body['system'] ?? '';
    // O system do Claude é agora um array de blocos: [estável (cache_control), variável]
    $sysText = is_array($sys) ? implode("\n", array_map(fn($b) => $b['text'] ?? '', $sys)) : $sys;
    assert_true(str_contains($sysText, $skillText), 'Skill aparece no system prompt do agente');
    assert_true(is_array($sys) && isset($sys[0]['cache_control']['type']) && $sys[0]['cache_control']['type'] === 'ephemeral', 'Prompt caching: bloco estável marcado com cache_control ephemeral');
    assert_true(is_array($sys) && str_contains($sys[0]['text'] ?? '', $skillText) && !str_contains($sys[1]['text'] ?? '', $skillText), 'Skill fica no bloco estável (cacheável), não no variável');
} catch (\Throwable $e) {
    fail('runThemeAgent com skill lançou excepção', $e->getMessage());
}

// ────────────────────────────────────────────────────────────────
//  13. Build Vite
// ────────────────────────────────────────────────────────────────
section('13. Build Vite');

$manifestPath = $root . '/public/build/manifest.json';
if (file_exists($manifestPath)) {
    $manifest = json_decode(file_get_contents($manifestPath), true);
    $hasEdit = false;
    foreach ($manifest as $key => $entry) {
        if (str_contains($key, 'Themes/Edit')) { $hasEdit = true; break; }
    }
    assert_true($hasEdit, 'Themes/Edit.vue compilado no manifest Vite');
} else {
    echo "  \033[33m⚠\033[0m  Manifest Vite não encontrado — skipped (corre 'npm run build' primeiro)\n";
}

// ────────────────────────────────────────────────────────────────
//  Sumário
// ────────────────────────────────────────────────────────────────
echo "\n" . str_repeat('─', 55) . "\n";
$total = $passed + $failed;
echo "\033[1m  Total: {$total}  Passed: \033[32m{$passed}\033[0m\033[1m  Failed: \033[" . ($failed > 0 ? '31' : '32') . "m{$failed}\033[0m\n";

if ($failed > 0) {
    echo "\n\033[31mTestes falhados:\033[0m\n";
    foreach ($errors as $err) {
        echo "  • {$err}\n";
    }
}

echo str_repeat('─', 55) . "\n\n";
exit($failed > 0 ? 1 : 0);
