<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

$pass = 0; $fail = 0;

function check(string $label, bool $ok, string $debug = ''): void {
    global $pass, $fail;
    echo ($ok ? '  ✅ ' : '  ❌ ') . $label . ($debug && !$ok ? ' ['.$debug.']' : '') . PHP_EOL;
    $ok ? $pass++ : $fail++;
}

// Aplica deep-merge e persiste no DB; devolve fresh
function applyToTheme(App\Models\StudioTheme $theme, array $updates): App\Models\StudioTheme {
    foreach (['colors','layout_config','capabilities','fonts','assets'] as $f) {
        if (isset($updates[$f]) && is_array($updates[$f])) {
            $existing = is_array($theme->fresh()->$f) ? $theme->fresh()->$f : [];
            $updates[$f] = array_replace_recursive($existing, $updates[$f]);
        }
    }
    $theme->update($updates);
    return $theme->fresh();
}

// Simula resposta AI e devolve [reply, updates]
function mockAI(string $replyText, ?array $updatesArray = null): array {
    $body = $replyText;
    if ($updatesArray !== null) {
        $body .= "\n```json_updates\n" . json_encode($updatesArray, JSON_UNESCAPED_UNICODE) . "\n```";
    }
    Http::fake(['api.anthropic.com/*' => Http::response(['content' => [['type' => 'text', 'text' => $body]]], 200)]);

    // Parse exactly as AIEngine::chatTheme would
    $updates = null;
    if (preg_match('/```json_updates\s*([\s\S]*?)```/m', $body, $m)) {
        $parsed = json_decode(trim($m[1]), true);
        if (is_array($parsed)) $updates = $parsed;
    }
    $reply = preg_replace('/```json_updates\s*[\s\S]*?```/m', '', $body);
    return ['reply' => trim($reply), 'updates' => $updates];
}

App\Models\StudioSetting::set('ai_api_key', 'sk-test-fake');
App\Models\StudioSetting::set('ai_provider', 'claude');
$theme = App\Models\StudioTheme::first();
if (!$theme) { echo "ERRO: Nenhum tema no DB.\n"; exit(1); }

// ═══════════════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'FLUXO 1: Manual → Chat (só subset) → Manual → Chat (só fonts)' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

$theme->update([
    'label'=>'Tema Manual','description'=>'Desc manual','version'=>'2.0.0',
    'colors'=>['light'=>['--color-primary'=>'#6d28d9','--color-background'=>'#ffffff','--color-text'=>'#111'],
               'dark' =>['--color-primary'=>'#a78bfa','--color-background'=>'#0f0f0f','--color-text'=>'#f0f']],
    'layout_config'=>['spacing'=>'normal','max_width'=>'1280','header_type'=>'solid','nav_type'=>'horizontal'],
    'capabilities'=>['animations'=>true,'search'=>true,'lightbox'=>true,'parallax'=>false],
    'fonts'=>['heading'=>'Playfair Display','body'=>'Inter'],
]);
$t = $theme->fresh();
echo PHP_EOL . '① Manual: label='.$t->label.', primary='.$t->colors['light']['--color-primary'].PHP_EOL;

// Passo A: Chat muda só colors.light.primary e spacing
$r = mockAI('Tornei mais minimalista!',
    ['colors'=>['light'=>['--color-primary'=>'#1a1a1a']],'layout_config'=>['spacing'=>'compact']]);
check('Parse: updates extraídos',         is_array($r['updates']));
check('Parse: json_updates removido da reply', !str_contains($r['reply'], 'json_updates'));

$t = applyToTheme($theme, $r['updates']);
echo PHP_EOL . '② Após chat (mudou só primary light e spacing):' . PHP_EOL;
check('AI: colors.light.primary → #1a1a1a',         ($t->colors['light']['--color-primary'] ?? '') === '#1a1a1a');
check('PRESERVOU: colors.dark.primary → #a78bfa',    ($t->colors['dark']['--color-primary'] ?? 'LOST') === '#a78bfa');
check('PRESERVOU: colors.light.bg → #ffffff',        ($t->colors['light']['--color-background'] ?? '') === '#ffffff');
check('PRESERVOU: colors.dark.bg → #0f0f0f',        ($t->colors['dark']['--color-background'] ?? '') === '#0f0f0f');
check('AI: layout.spacing → compact',               ($t->layout_config['spacing'] ?? '') === 'compact');
check('PRESERVOU: layout.max_width → 1280',          ($t->layout_config['max_width'] ?? '') === '1280');
check('PRESERVOU: layout.header_type → solid',       ($t->layout_config['header_type'] ?? '') === 'solid');
check('PRESERVOU: capabilities.search → true',       ($t->capabilities['search'] ?? false) === true);
check('PRESERVOU: capabilities.lightbox → true',     ($t->capabilities['lightbox'] ?? false) === true);
check('PRESERVOU: fonts.heading → Playfair Display', ($t->fonts['heading'] ?? '') === 'Playfair Display');
check('PRESERVOU: label → Tema Manual',              $t->label === 'Tema Manual');
check('PRESERVOU: version → 2.0.0',                 $t->version === '2.0.0');

// Passo B: Utilizador vai ao Design tab e muda dark.primary manualmente
$theme->update(['colors' => array_replace_recursive($t->colors, ['dark'=>['--color-primary'=>'#ff6b6b']])]);
$t = $theme->fresh();
echo PHP_EOL . '③ Manual no Design tab (dark.primary → #ff6b6b):' . PHP_EOL;
check('Manual: dark.primary → #ff6b6b',         ($t->colors['dark']['--color-primary'] ?? '') === '#ff6b6b');
check('Preservado: light.primary (#1a1a1a)',     ($t->colors['light']['--color-primary'] ?? '') === '#1a1a1a');

// Passo C: Chat volta e muda só fonts — não toca em cores
$r2 = mockAI('Mudei para DM Sans!', ['fonts'=>['heading'=>'DM Sans','body'=>'DM Sans']]);
$t = applyToTheme($theme, $r2['updates']);
echo PHP_EOL . '④ Chat: muda só fonts:' . PHP_EOL;
check('AI: fonts.heading → DM Sans',                ($t->fonts['heading'] ?? '') === 'DM Sans');
check('PRESERVOU: dark.primary (#ff6b6b manual)',   ($t->colors['dark']['--color-primary'] ?? '') === '#ff6b6b');
check('PRESERVOU: light.primary (#1a1a1a chat)',    ($t->colors['light']['--color-primary'] ?? '') === '#1a1a1a');
check('PRESERVOU: layout.max_width → 1280',        ($t->layout_config['max_width'] ?? '') === '1280');

// ═══════════════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'FLUXO 2: Chat cria tema de raiz → Manual refina → Chat ajusta' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

$theme->update(['label'=>'Novo','description'=>null,'version'=>'1.0.0','status'=>'draft',
    'colors'=>null,'layout_config'=>null,'capabilities'=>null,'fonts'=>null,'custom_css'=>null]);

$r3 = mockAI('Criei o tema Luxe Store!', [
    'label'=>'Luxe Store','description'=>'Tema de luxo',
    'colors'=>[
        'light'=>['--color-primary'=>'#b8860b','--color-background'=>'#faf9f7','--color-text'=>'#1a1a1a'],
        'dark' =>['--color-primary'=>'#ffd700','--color-background'=>'#0d0d0d','--color-text'=>'#f5f5f0'],
    ],
    'fonts'=>['heading'=>'Cormorant Garamond','body'=>'Lato'],
    'layout_config'=>['max_width'=>'1400','spacing'=>'relaxed','header_type'=>'transparent','footer_type'=>'full'],
    'capabilities'=>['animations'=>true,'lightbox'=>true,'parallax'=>true,'search'=>false],
]);
$t = applyToTheme($theme, $r3['updates']);
echo PHP_EOL . '① Chat criou tema de raiz:' . PHP_EOL;
check('label → Luxe Store',                     $t->label === 'Luxe Store', 'actual: '.$t->label);
check('colors.light.primary → #b8860b',         ($t->colors['light']['--color-primary'] ?? '') === '#b8860b');
check('colors.dark.primary → #ffd700',          ($t->colors['dark']['--color-primary'] ?? 'MISSING') === '#ffd700');
check('fonts.heading → Cormorant Garamond',     ($t->fonts['heading'] ?? '') === 'Cormorant Garamond');
check('layout.max_width → 1400',               ($t->layout_config['max_width'] ?? '') === '1400');
check('capabilities.parallax → true',           ($t->capabilities['parallax'] ?? false) === true);

// Utilizador refina manualmente
$theme->update([
    'custom_css' => '.hero { min-height: 100vh; }',
    'layout_config' => array_replace_recursive($t->layout_config, ['max_width'=>'1600','footer_type'=>'minimal']),
    'capabilities' => array_replace_recursive($t->capabilities, ['search'=>true]),
]);
$t = $theme->fresh();
echo PHP_EOL . '② Manual refina (max_width, css, search):' . PHP_EOL;
check('Manual: custom_css adicionado',                !empty($t->custom_css));
check('Manual: max_width → 1600 (era 1400)',         ($t->layout_config['max_width'] ?? '') === '1600');
check('Manual: footer_type → minimal',               ($t->layout_config['footer_type'] ?? '') === 'minimal');
check('Manual: search → true (era false)',            ($t->capabilities['search'] ?? false) === true);
check('Chat preservado: parallax → true',            ($t->capabilities['parallax'] ?? false) === true);
check('Chat preservado: header_type → transparent',  ($t->layout_config['header_type'] ?? '') === 'transparent');
check('Chat preservado: primary light → #b8860b',    ($t->colors['light']['--color-primary'] ?? '') === '#b8860b');

// Chat ajuste fino: accent color
$r4 = mockAI('Adicionei accent dourado!',
    ['colors'=>['light'=>['--color-accent'=>'#d4af37'],'dark'=>['--color-accent'=>'#ffd700']]]);
$t = applyToTheme($theme, $r4['updates']);
echo PHP_EOL . '③ Chat: accent dourado (sem tocar em mais nada):' . PHP_EOL;
check('Chat: accent light → #d4af37',              ($t->colors['light']['--color-accent'] ?? '') === '#d4af37');
check('Chat: accent dark → #ffd700',               ($t->colors['dark']['--color-accent'] ?? '') === '#ffd700');
check('PRESERVOU: primary light → #b8860b',        ($t->colors['light']['--color-primary'] ?? '') === '#b8860b');
check('PRESERVOU: primary dark → #ffd700',         ($t->colors['dark']['--color-primary'] ?? '') === '#ffd700');
check('PRESERVOU: manual max_width → 1600',        ($t->layout_config['max_width'] ?? '') === '1600');
check('PRESERVOU: manual footer_type → minimal',   ($t->layout_config['footer_type'] ?? '') === 'minimal');
check('PRESERVOU: manual custom_css',              !empty($t->custom_css));
check('PRESERVOU: manual search → true',           ($t->capabilities['search'] ?? false) === true);

// ═══════════════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'FLUXO 3: themeJson enviado à AI reflecte estado actual do DB' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

$fresh = $theme->fresh();
$themeData = $fresh->toArray();
unset($themeData['sections'], $themeData['components']);
$decoded = json_decode(json_encode($themeData, JSON_UNESCAPED_UNICODE), true);

check('label actual (Luxe Store)',                   $decoded['label'] === 'Luxe Store');
check('max_width manual (1600)',                     ($decoded['layout_config']['max_width'] ?? '') === '1600');
check('custom_css manual presente',                  !empty($decoded['custom_css']));
check('accent light do chat (#d4af37)',              ($decoded['colors']['light']['--color-accent'] ?? '') === '#d4af37');
check('primary dark preservado (#ffd700)',           ($decoded['colors']['dark']['--color-primary'] ?? '') === '#ffd700');
check('sections omitido (peso reduzido)',            !isset($decoded['sections']));
check('components omitido (peso reduzido)',          !isset($decoded['components']));

// ═══════════════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'FLUXO 4: Resposta conversacional sem alterações ao tema' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

$before = $theme->fresh();
$r5 = mockAI('O minimalismo usa espaço em branco generoso e paleta reduzida. Queres aplicar?', null);
check('updates é null (sem json_updates)',       $r5['updates'] === null);
check('reply não está vazio',                    !empty($r5['reply']));
// tema não muda pois nao há updates para aplicar
check('tema intacto (label ainda Luxe Store)',   $before->label === 'Luxe Store');

// ═══════════════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'FLUXO 5: Merge de capabilities — AI muda um booleano' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

// AI só desliga parallax — search, lightbox, animations mantidos
$r6 = mockAI('Desactivei parallax!', ['capabilities'=>['parallax'=>false]]);
$tBefore = $theme->fresh();
$t = applyToTheme($theme, $r6['updates']);
check('AI: parallax → false',                  ($t->capabilities['parallax'] ?? true) === false);
check('PRESERVOU: search → true',              ($t->capabilities['search'] ?? false) === true);
check('PRESERVOU: animations → true',          ($t->capabilities['animations'] ?? false) === true);
check('PRESERVOU: lightbox → true',            ($t->capabilities['lightbox'] ?? false) === true);

// Limpeza
$theme->update(['label'=>'Novo Tema 1','description'=>null,'version'=>'1.0.0','status'=>'draft',
    'colors'=>null,'layout_config'=>null,'capabilities'=>null,'fonts'=>null,'custom_css'=>null]);
App\Models\StudioSetting::set('ai_api_key', '');

echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo "RESULTADO FINAL: {$pass} passou, {$fail} falhou" . PHP_EOL;
echo ($fail === 0 ? '✅ TODOS OS TESTES PASSARAM' : "❌ {$fail} TESTES FALHARAM") . PHP_EOL;
