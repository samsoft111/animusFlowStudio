<?php

/**
 * Testes — AI Recipe Engine (Camada A & B: Receitas e Abstração)
 *
 * Garante:
 *  1. Matching correto e substituição de variáveis com as receitas semeadas.
 *  2. Auto-registo de novas receitas a partir de respostas com blocos de controlo ````recipe````.
 *  3. Hits são devidamente incrementados nas receitas locais.
 *
 * Executar:
 *   php tests/recipe_engine_test.php
 */

declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use App\Models\StudioTheme;
use App\Models\StudioSetting;
use App\Models\StudioAiRecipe;
use App\Http\Controllers\ThemeController;

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

set_test_ai_key();
\App\Models\StudioAiCommandCache::truncate();

// ────────────────────────────────────────────────────────────────
//  1. Validação de Matching e Resolução de Receitas
// ────────────────────────────────────────────────────────────────
section('1. Resolução de Receitas por Omissão (Seeded Recipes)');

try {
    // Teste 1: mudar a cor principal para #ff0000
    $resColor = StudioAiRecipe::matchAndResolve('theme', 'mudar a cor principal para #ff0000');
    assert_true($resColor !== null, 'mudar-cor-principal foi identificada e resolvida');
    assert_true(($resColor['updates']['colors']['light']['--color-primary'] ?? '') === '#ff0000', 'Cor principal resolvida corretamente para #ff0000');
    assert_true(str_contains($resColor['reply'], '#ff0000'), 'Mensagem de resposta contém a cor substituída');
    assert_true($resColor['recipe'] === 'mudar-cor-principal', 'Retornou o identificador correto da receita');

    // Teste 2: mudar a fonte do título para Outfit
    $resFont = StudioAiRecipe::matchAndResolve('theme', 'mudar a fonte do título para Outfit');
    assert_true($resFont !== null, 'mudar-fonte-titulo foi identificada e resolvida');
    assert_true(($resFont['updates']['fonts']['heading'] ?? '') === 'Outfit', 'Fonte de título resolvida para Outfit');
    assert_true(str_contains($resFont['reply'], 'Outfit'), 'Resposta de texto contém a fonte substituída');

    // Teste 3: Prompt não existente
    $resNone = StudioAiRecipe::matchAndResolve('theme', 'um prompt aleatório qualquer');
    assert_true($resNone === null, 'Retorna null para prompts que não condizem com nenhuma receita');

} catch (\Throwable $e) {
    fail('Erro no matching de receitas', $e->getMessage());
}

// ────────────────────────────────────────────────────────────────
//  2. Auto-registo de Nova Receita a partir da IA
// ────────────────────────────────────────────────────────────────
section('2. Auto-aprendizagem: IA ensina nova receita');

// Remover qualquer receita antiga de teste
StudioAiRecipe::where('name', 'mudar-cor-secundaria')->delete();

$theme = StudioTheme::create([
    'name'          => 'test-recipe-cache-' . uniqid(),
    'label'         => 'Tema Teste Receita',
    'version'       => '1.0.0',
    'status'        => 'draft',
    'colors'        => ['light' => ['--color-primary' => '#000000']],
    'fonts'         => ['heading' => 'Inter', 'body' => 'Inter'],
    'sections'      => [],
]);

$themeCtrl = new ThemeController();

// Configura o Claude para retornar um bloco ```recipe
fake_ai("Ensinei a receita ao sistema!\n```recipe\n" . json_encode([
    'name'           => 'mudar-cor-secundaria',
    'description'    => 'Mudar a cor secundária do tema',
    'prompt_pattern' => 'mudar a cor secundária para {cor}',
    'code_templates' => ['colors' => ['light' => ['--color-secondary' => '{{cor}}']]],
    'reply_template' => 'Cor secundária alterada para {{cor}}.'
]) . "\n```");

try {
    $req = \Illuminate\Http\Request::create("/themes/{$theme->uuid}/chat", 'POST', [
        'message' => 'Cria uma receita para mudar a cor secundária',
    ]);
    $req->setLaravelSession(app('session.store'));
    
    $resp = $themeCtrl->chat($req, $theme->uuid);
    $data = json_decode($resp->getContent(), true);

    assert_true($resp->getStatusCode() === 200, 'Chat respondeu 200');
    assert_true(!str_contains($data['reply'] ?? '', '```recipe'), 'O bloco recipe foi removido da resposta ao utilizador');

    // Verificar se foi inserido na BD
    $recipeEntry = StudioAiRecipe::where('name', 'mudar-cor-secundaria')->first();
    assert_true($recipeEntry !== null, 'Nova receita "mudar-cor-secundaria" foi auto-registada com sucesso');
    assert_true($recipeEntry->prompt_pattern === 'mudar a cor secundária para {cor}', 'Padrão guardado corretamente');

    // Testar se agora o sistema resolve localmente
    $resLocal = StudioAiRecipe::matchAndResolve('theme', 'mudar a cor secundária para #123456');
    assert_true($resLocal !== null, 'Nova receita resolve localmente prompts futuros');
    assert_true(($resLocal['updates']['colors']['light']['--color-secondary'] ?? '') === '#123456', 'Valor do placeholder cor substituído corretamente');
    assert_true(str_contains($resLocal['reply'], '#123456'), 'Resposta resolvida localmente contém a cor');

} catch (\Throwable $e) {
    fail('Erro no auto-registo de receitas', $e->getMessage() . "\n" . $e->getTraceAsString());
}

// Limpeza
$theme->forceDelete();
StudioAiRecipe::where('name', 'mudar-cor-secundaria')->delete();
ok('Limpeza de dados de teste concluída');

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
