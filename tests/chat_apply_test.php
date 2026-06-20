<?php

/**
 * Testes — Criar tema via Chat IA (contrato do ThemeController::chat)
 *
 * Garante o modelo coerente de aplicação/persistência:
 *  1. chat() com json_updates → aplica por deep-merge E persiste na BD (auto-save),
 *     devolvendo applied=true + tema fresco (a UI mostra "guardadas automaticamente").
 *  2. chat() conversacional (sem json_updates) → applied=false, theme=null, BD intacta.
 *  3. chat() sem chave AI → 422 com {error, is_fatal:true} (coerente com Modo Construção).
 *  4. Deep-merge preserva campos não tocados (cores dark, layout, etc.).
 *  5. UI: badge honesto, sem o botão "Guardar agora" contraditório.
 *
 * Executar:
 *   php tests/chat_apply_test.php
 *
 * Paridade com tests/inspire_category_test.php e tests/build_mode_test.php.
 */

declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use App\Models\StudioTheme;
use App\Models\StudioSetting;
use App\Http\Controllers\ThemeController;

// ── Helpers ──────────────────────────────────────────────────────
$passed = 0; $failed = 0; $errors = [];

function ok(string $m): void { global $passed; $passed++; echo "  \033[32m✓\033[0m {$m}\n"; }
function fail(string $m, string $d = ''): void { global $failed, $errors; $failed++; $errors[] = $m . ($d ? ": {$d}" : ''); echo "  \033[31m✗\033[0m {$m}" . ($d ? " — {$d}" : '') . "\n"; }
function section(string $t): void { echo "\n\033[1;34m▶ {$t}\033[0m\n"; }
function assert_true(bool $v, string $m, string $d = ''): void { $v ? ok($m) : fail($m, $d); }
function assert_contains(string $h, string $n, string $m): void { str_contains($h, $n) ? ok($m) : fail($m, "esperado: «{$n}»"); }

// Fake HTTP único com corpo mutável (Laravel Http::fake é first-wins).
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
function make_chat_request(string $uuid, string $message): \Illuminate\Http\Request {
    $r = \Illuminate\Http\Request::create("/themes/{$uuid}/chat", 'POST', ['message' => $message]);
    $r->setLaravelSession(app('session.store'));
    return $r;
}

$ctrl = new ThemeController();

// Tema de teste com estado inicial conhecido
$theme = StudioTheme::create([
    'name'          => 'test-chat-' . uniqid(),
    'label'         => 'Tema Chat',
    'description'   => 'Tema de teste do chat',
    'version'       => '1.0.0',
    'colors'        => ['light' => ['--color-primary' => '#111111', '--color-background' => '#ffffff'], 'dark' => ['--color-primary' => '#eeeeee']],
    'fonts'         => ['heading' => 'Inter', 'body' => 'Inter'],
    'sections'      => ['hero' => '<section>v1</section>'],
    'layout_config' => ['max_width' => '1280', 'spacing' => 'normal'],
    'capabilities'  => ['animations' => true, 'search' => true],
    'assets'        => [], 'components' => [], 'variants' => [],
    'custom_css'    => '', 'custom_js' => '', 'status' => 'draft',
]);

// ────────────────────────────────────────────────────────────────
//  1. chat() com json_updates → aplica + persiste (auto-save)
// ────────────────────────────────────────────────────────────────
section('1. chat() aplica e persiste alterações (auto-save)');

set_test_ai_key();
fake_ai("Tornei o tema mais escuro!\n```json_updates\n" . json_encode([
    'label'  => 'Tema Escuro',
    'colors' => ['light' => ['--color-primary' => '#1a1a1a']], // só primary light muda
    'fonts'  => ['heading' => 'Outfit'],
]) . "\n```");

try {
    $resp = $ctrl->chat(make_chat_request($theme->uuid, 'Torna mais escuro'), $theme->uuid);
    $data = json_decode($resp->getContent(), true);

    assert_true($resp->getStatusCode() === 200, 'chat() responde 200');
    assert_true(($data['applied'] ?? false) === true, 'applied=true (auto-save)');
    assert_true(isset($data['theme']) && $data['theme'] !== null, 'Devolve tema fresco');
    assert_true(!str_contains($data['reply'] ?? '', 'json_updates'), 'reply remove o bloco json_updates');
    assert_true(str_contains($data['reply'] ?? '', 'escuro'), 'reply preserva texto humano');

    // Persistido mesmo na BD?
    $fresh = StudioTheme::where('uuid', $theme->uuid)->first();
    assert_true($fresh->label === 'Tema Escuro', 'BD: label persistido');
    assert_true(($fresh->colors['light']['--color-primary'] ?? '') === '#1a1a1a', 'BD: nova cor primary persistida');
    assert_true(($fresh->fonts['heading'] ?? '') === 'Outfit', 'BD: nova fonte persistida');
} catch (\Throwable $e) {
    fail('chat() lançou excepção', $e->getMessage());
}

// ────────────────────────────────────────────────────────────────
//  2. Deep-merge preserva campos não tocados
// ────────────────────────────────────────────────────────────────
section('2. Deep-merge — preserva o que a IA não tocou');

$fresh = StudioTheme::where('uuid', $theme->uuid)->first();
assert_true(($fresh->colors['dark']['--color-primary'] ?? '') === '#eeeeee', 'Preservado: colors.dark.primary');
assert_true(($fresh->colors['light']['--color-background'] ?? '') === '#ffffff', 'Preservado: colors.light.background');
assert_true(($fresh->layout_config['max_width'] ?? '') === '1280', 'Preservado: layout_config.max_width');
assert_true(($fresh->capabilities['search'] ?? false) === true, 'Preservado: capabilities.search');
assert_true(($fresh->fonts['body'] ?? '') === 'Inter', 'Preservado: fonts.body');

// ────────────────────────────────────────────────────────────────
//  3. chat() conversacional (sem json_updates) → não altera a BD
// ────────────────────────────────────────────────────────────────
section('3. chat() conversacional — sem alterações');

set_test_ai_key();
fake_ai('O minimalismo usa espaço em branco e paleta reduzida. Queres aplicar?');

$before = StudioTheme::where('uuid', $theme->uuid)->first()->label;
try {
    $resp = $ctrl->chat(make_chat_request($theme->uuid, 'O que é minimalismo?'), $theme->uuid);
    $data = json_decode($resp->getContent(), true);

    assert_true($resp->getStatusCode() === 200, 'chat() responde 200');
    assert_true(array_key_exists('updates', $data) && $data['updates'] === null, 'updates é null (sem json_updates)');
    assert_true(($data['applied'] ?? true) === false, 'applied=false');
    assert_true(array_key_exists('theme', $data) && $data['theme'] === null, 'theme=null quando nada muda');
    assert_true(!empty($data['reply']), 'reply não está vazio');

    $after = StudioTheme::where('uuid', $theme->uuid)->first()->label;
    assert_true($before === $after, 'BD intacta (label inalterado)');
} catch (\Throwable $e) {
    fail('chat() conversacional lançou excepção', $e->getMessage());
}

// ────────────────────────────────────────────────────────────────
//  4. chat() sem chave AI → 422 + is_fatal
// ────────────────────────────────────────────────────────────────
section('4. chat() sem chave AI → 422 + is_fatal (coerência com Modo Construção)');

StudioSetting::set('ai_api_key', '');
StudioSetting::set('ai_api_key_claude', '');
try {
    $resp = $ctrl->chat(make_chat_request($theme->uuid, 'Olá'), $theme->uuid);
    $data = json_decode($resp->getContent(), true);
    assert_true($resp->getStatusCode() === 422, 'Responde 422 sem chave');
    assert_true(isset($data['error']), 'Resposta tem error');
    assert_true(isset($data['is_fatal']) && $data['is_fatal'] === true, 'is_fatal=true (chave em falta)');
} catch (\Throwable $e) {
    fail('chat() sem chave lançou excepção', $e->getMessage());
}

// ────────────────────────────────────────────────────────────────
//  5. Validação — message obrigatória
// ────────────────────────────────────────────────────────────────
section('5. chat() — message obrigatória');

$badReq = \Illuminate\Http\Request::create("/themes/{$theme->uuid}/chat", 'POST', []);
$badReq->setLaravelSession(app('session.store'));
try {
    $ctrl->chat($badReq, $theme->uuid);
    fail('chat() deveria exigir message');
} catch (\Illuminate\Validation\ValidationException $e) {
    ok('chat() exige message (ValidationException)');
} catch (\Throwable $e) {
    fail('Tipo de excepção inesperado', get_class($e));
}

$theme->forceDelete();
ok('Tema de teste removido da BD');

// ────────────────────────────────────────────────────────────────
//  6. UI Edit.vue — badge honesto, sem contradição
// ────────────────────────────────────────────────────────────────
section('6. Edit.vue — UI coerente do chat');

$vue = file_get_contents($root . '/resources/js/Pages/Themes/Edit.vue');
assert_contains($vue, 'Aplicadas e guardadas automaticamente', 'Badge honesto presente');
assert_true(!str_contains($vue, '>Guardar agora<'), 'Botão contraditório "Guardar agora" removido do chat');
assert_contains($vue, 'sendChatMessage', 'Função sendChatMessage presente');
assert_contains($vue, 'applyServerTheme(data.theme)', 'Form sincronizado com o tema do servidor');

// ────────────────────────────────────────────────────────────────
//  7. Controller — contrato de resposta
// ────────────────────────────────────────────────────────────────
section('7. ThemeController::chat — contrato');

$ctrlFile = file_get_contents($root . '/app/Http/Controllers/ThemeController.php');
assert_contains($ctrlFile, "'is_fatal' => self::isFatalAiError(\$e)", 'chat() devolve is_fatal em erro');
assert_contains($ctrlFile, 'applyThemeUpdates($theme, $result', 'chat() aplica via applyThemeUpdates (deep-merge)');

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
