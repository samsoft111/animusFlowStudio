<?php
/**
 * Theme Step Engine Test Suite — AnimusFlowStudio
 * Cobre: ThemeStepEngine (mapeamento, classificação híbrida, record, pruneHistory, revertStep, publicJournal)
 * Execução: php tests/theme_step_engine_test.php
 *
 * Paridade com tests/plugin_step_engine_test.php (motor de passos dos plugins).
 */

define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\StudioTheme;
use App\Services\ThemeStepEngine;

// ─── Contador ────────────────────────────────────────────────────────────────
$passed = 0; $failed = 0;
function check(string $label, bool $ok): void {
    global $passed, $failed;
    if ($ok) { echo "  ✅ {$label}\n"; $passed++; }
    else      { echo "  ❌ {$label}\n"; $failed++; }
}

// Criar tema temporário para testes
$theme = StudioTheme::create([
    'name'        => 'test-step-theme-' . uniqid(),
    'label'       => 'Test Step Theme',
    'description' => 'Descrição do tema de teste',
    'version'     => '1.0.0',
    'status'      => 'draft',
    'colors'      => ['light' => ['--color-primary' => '#000000']],
    'fonts'       => ['heading' => 'Inter', 'body' => 'Inter'],
    'sections'    => ['hero' => '<section>Hero</section>'],
    'custom_css'  => '/* base */',
]);

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 1: Mapeamentos e Rótulos' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

check('Mapping para details funciona', ThemeStepEngine::FIELD_STEP['label'] === 'details');
check('Mapping para design (colors) funciona', ThemeStepEngine::FIELD_STEP['colors'] === 'design');
check('Mapping para design (fonts) funciona', ThemeStepEngine::FIELD_STEP['fonts'] === 'design');
check('Mapping para layout funciona', ThemeStepEngine::FIELD_STEP['layout_config'] === 'layout');
check('Mapping para sections funciona', ThemeStepEngine::FIELD_STEP['sections'] === 'sections');
check('Mapping para code (css) funciona', ThemeStepEngine::FIELD_STEP['custom_css'] === 'code');
check('Mapping para code (js) funciona', ThemeStepEngine::FIELD_STEP['custom_js'] === 'code');
check('Mapping para variants funciona', ThemeStepEngine::FIELD_STEP['variants'] === 'variants');

check('Label de details está correto', ThemeStepEngine::label('details') === 'Detalhes');
check('Label de design está correto', ThemeStepEngine::label('design') === 'Design');
check('Label de code está correto', ThemeStepEngine::label('code') === 'Código');
check('Label de sections está correto', ThemeStepEngine::label('sections') === 'Secções');

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 2: Classificação Híbrida (Camada A & B)' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

// 1. Baseado em campos alterados (colors→design, custom_css→code)
$res1 = ThemeStepEngine::classify('ambíguo', ['colors', 'custom_css'], false);
check('Classificação por campos encontra design e code', count($res1['steps']) === 2);
check('Classificação por campos escolhe o primeiro (design)', $res1['step'] === 'design');
check('Classificação por campos usa método "fields"', $res1['method'] === 'fields');

// 2. Baseado em palavras-chave (sem campos alterados)
$res2 = ThemeStepEngine::classify('muda a cor primária e a fonte dos títulos', [], false);
check('Classificação por keyword identifica "design"', $res2['step'] === 'design');
check('Classificação por keyword usa método "keyword"', $res2['method'] === 'keyword');

$res3 = ThemeStepEngine::classify('adiciona código css personalizado no custom', [], false);
check('Classificação por keyword identifica "code"', $res3['step'] === 'code');

$res4 = ThemeStepEngine::classify('muda o rodapé e o menu de navegação', [], false);
check('Classificação por keyword identifica "layout"', $res4['step'] === 'layout');

$res5 = ThemeStepEngine::classify('adiciona uma secção de preços (pricing)', [], false);
check('Classificação por keyword identifica "sections"', $res5['step'] === 'sections');

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 3: Registo de Diário de Alterações (Mirror Schema)' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

$beforeCss = $theme->custom_css;

$theme->update(['custom_css' => '/* modificado */']);

$affected = ThemeStepEngine::record($theme, ['custom_css'], 'chat', 'Adicionada regra CSS', ['custom_css' => $beforeCss]);
check('record() deteta passo afetado', count($affected) === 1 && $affected[0] === 'code');

$journal = $theme->fresh()->step_journal;
check('Journal possui nó do passo "code"', isset($journal['code']));
check('Status do passo code é "done"', $journal['code']['status'] === 'done');
check('Origem registada com sucesso', $journal['code']['source'] === 'chat');
check('Histórico possui 1 entrada', count($journal['code']['history']) === 1);

$entry = $journal['code']['history'][0];
check('Resumo da alteração está correto', $entry['summary'] === 'Adicionada regra CSS');
check('Campos alterados estão registados', $entry['fields'] === ['custom_css']);
check('Snapshot "before" foi gravado', $entry['before']['custom_css'] === $beforeCss);
check('Metadados (tamanho/hash) gerados', isset($entry['meta']['custom_css']['size']) && isset($entry['meta']['custom_css']['hash']));

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 4: Poda de Histórico (Sliding Window & Metadata Pruning)' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

// 5 modificações adicionais no mesmo passo para ultrapassar a janela de snapshot (3)
for ($i = 1; $i <= 5; $i++) {
    $currentVal = $theme->custom_css;
    $theme->update(['custom_css' => "/* edit {$i} */"]);
    ThemeStepEngine::record($theme, ['custom_css'], 'manual', "Edição {$i}", ['custom_css' => $currentVal]);
}

$journal = $theme->fresh()->step_journal;
$history = $journal['code']['history'];
check('Histórico contem todas as modificações recentes', count($history) >= 4);

// Os 3 mais recentes devem ter o payload completo 'before'
$recent1 = $history[count($history) - 1];
$recent2 = $history[count($history) - 2];
$recent3 = $history[count($history) - 3];
check('Mais recente contem before', isset($recent1['before']));
check('Segundo mais recente contem before', isset($recent2['before']));
check('Terceiro mais recente contem before', isset($recent3['before']));

// Entradas anteriores devem ter sido podadas (sem before, com flag pruned)
$prunedEntry = $history[0];
check('Entradas anteriores foram podadas (sem before)', !isset($prunedEntry['before']));
check('Entrada podada tem flag pruned = true', ($prunedEntry['pruned'] ?? false) === true);
check('Entrada podada retém metadados', isset($prunedEntry['meta']['custom_css']['size']));

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 5: Reversão de Passo (RevertStep)' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

$theme->refresh();
$revertible = ThemeStepEngine::canRevert($theme->step_journal['code']);
check('Última entrada é passível de reversão', $revertible === true);

$reverted = ThemeStepEngine::revertStep($theme, 'code');
check('revertStep() retornou sucesso', $reverted === true);

$theme->refresh();
check('Valor anterior do CSS foi restaurado', $theme->custom_css === '/* edit 4 */');
check('Passo atualizado indica origem da reversão', ($theme->step_journal['code']['source'] ?? '') === 'revert');

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 6: Filtro Público de Resposta (publicJournal)' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

$public = ThemeStepEngine::publicJournal($theme->step_journal);
check('publicJournal() exportou os passos corretos', isset($public['code']));
check('publicJournal() adicionou flag revertible', isset($public['code']['revertible']));

$publicHistory = $public['code']['history'] ?? [];
$hasBefore = false;
foreach ($publicHistory as $e) {
    if (isset($e['before'])) { $hasBefore = true; }
}
check('publicJournal() limpou todos os payloads "before"', $hasBefore === false);

// ─── Limpeza ────────────────────────────────────────────────────────────────
$theme->forceDelete();

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo "RESULTADO FINAL: {$passed} passou, {$failed} falhou" . PHP_EOL;
if ($failed === 0) {
    echo '✅ TODOS OS TESTES DE UNIDADE DO MOTOR DE PASSOS (TEMA) PASSARAM' . PHP_EOL;
} else {
    echo "❌ {$failed} TESTE(S) FALHARAM" . PHP_EOL;
}
exit($failed > 0 ? 1 : 0);
