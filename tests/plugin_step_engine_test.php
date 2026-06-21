<?php
/**
 * Plugin Step Engine Test Suite — AnimusFlowStudio
 * Cobre: PluginStepEngine (mapeamento, classificação híbrida, record, pruneHistory, revertStep, publicJournal)
 * Execução: php tests/plugin_step_engine_test.php
 */

define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\StudioPlugin;
use App\Services\PluginStepEngine;
use Illuminate\Support\Facades\Schema;

// ─── Contador ────────────────────────────────────────────────────────────────
$passed = 0; $failed = 0;
function check(string $label, bool $ok): void {
    global $passed, $failed;
    if ($ok) { echo "  ✅ {$label}\n"; $passed++; }
    else      { echo "  ❌ {$label}\n"; $failed++; }
}

// Criar plugin temporário para testes
$plugin = StudioPlugin::create([
    'name'        => 'test-step-plugin-' . uniqid(),
    'label'       => 'Test Step Plugin',
    'description' => 'Descrição do plugin de teste',
    'version'     => '1.0.0',
    'status'      => 'draft',
    'hooks'       => ['page.render'],
    'plugin_php'  => '<?php class TestPlugin {}',
    'widget_blade'=> '<div>Widget</div>',
    'widget_js'   => 'console.log("widget");',
    'custom_css'  => '.widget { color: red; }',
    'settings_schema' => [],
    'readme'      => '# Docs',
]);

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 1: Mapeamentos e Rótulos' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

check('Mapping para details funciona', PluginStepEngine::FIELD_STEP['label'] === 'details');
check('Mapping para hooks funciona', PluginStepEngine::FIELD_STEP['hooks'] === 'hooks');
check('Mapping para php funciona', PluginStepEngine::FIELD_STEP['plugin_php'] === 'php');
check('Mapping para widget funciona', PluginStepEngine::FIELD_STEP['widget_blade'] === 'widget');
check('Mapping para css funciona', PluginStepEngine::FIELD_STEP['custom_css'] === 'css');
check('Mapping para schema funciona', PluginStepEngine::FIELD_STEP['settings_schema'] === 'schema');
check('Mapping para docs funciona', PluginStepEngine::FIELD_STEP['readme'] === 'docs');

check('Label de details está correto', PluginStepEngine::label('details') === 'Detalhes');
check('Label de php está correto', PluginStepEngine::label('php') === 'PHP');
check('Label de schema está correto', PluginStepEngine::label('schema') === 'Configurações');

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 2: Classificação Híbrida (Camada A & B)' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

// 1. Baseado em campos alterados
$res1 = PluginStepEngine::classify('ambíguo', ['plugin_php', 'widget_blade'], false);
check('Classificação por campos encontra php e widget', count($res1['steps']) === 2);
check('Classificação por campos escolhe o primeiro', $res1['step'] === 'php');
check('Classificação por campos usa método "fields"', $res1['method'] === 'fields');

// 2. Baseado em palavras-chave (sem campos alterados)
$res2 = PluginStepEngine::classify('Quero adicionar um hook para interceptar renderização', [], false);
check('Classificação por keyword identifica "hooks"', $res2['step'] === 'hooks');
check('Classificação por keyword usa método "keyword"', $res2['method'] === 'keyword');

$res3 = PluginStepEngine::classify('Altera o estilo css para cor azul', [], false);
check('Classificação por keyword identifica "css"', $res3['step'] === 'css');

$res4 = PluginStepEngine::classify('modifica os campos do schema de configurações', [], false);
check('Classificação por keyword identifica "schema"', $res4['step'] === 'schema');

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 3: Registo de Diário de Alterações (Mirror Schema)' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

$beforePhp = $plugin->plugin_php;

// Efetua alteração simulada
$plugin->update(['plugin_php' => '<?php class TestPlugin { // modificado }']);

$affected = PluginStepEngine::record($plugin, ['plugin_php'], 'chat', 'Adicionado método ao PHP', ['plugin_php' => $beforePhp]);
check('record() deteta passo afetado', count($affected) === 1 && $affected[0] === 'php');

$journal = $plugin->fresh()->step_journal;
check('Journal possui nó do passo "php"', isset($journal['php']));
check('Status do passo php é "done"', $journal['php']['status'] === 'done');
check('Origem registada com sucesso', $journal['php']['source'] === 'chat');
check('Histórico possui 1 entrada', count($journal['php']['history']) === 1);

$entry = $journal['php']['history'][0];
check('Resumo da alteração está correto', $entry['summary'] === 'Adicionado método ao PHP');
check('Campos alterados estão registados', $entry['fields'] === ['plugin_php']);
check('Snapshot "before" foi gravado', $entry['before']['plugin_php'] === $beforePhp);
check('Metadados (tamanho/hash) gerados', isset($entry['meta']['plugin_php']['size']) && isset($entry['meta']['plugin_php']['hash']));

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 4: Poda de Histórico (Sliding Window & Metadata Pruning)' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

// Record 5 modificações adicionais no mesmo passo para ultrapassar o limite da janela de snapshot (3)
for ($i = 1; $i <= 5; $i++) {
    $currentVal = $plugin->plugin_php;
    $plugin->update(['plugin_php' => "<?php // edit {$i}"]);
    PluginStepEngine::record($plugin, ['plugin_php'], 'manual', "Edição {$i}", ['plugin_php' => $currentVal]);
}

$journal = $plugin->fresh()->step_journal;
$history = $journal['php']['history'];
check('Histórico contem todas as modificações recentes', count($history) >= 4);

// Os 3 mais recentes devem ter o payload completo 'before'
$recent1 = $history[count($history) - 1];
$recent2 = $history[count($history) - 2];
$recent3 = $history[count($history) - 3];
check('Mais recente contem before', isset($recent1['before']));
check('Segundo mais recente contem before', isset($recent2['before']));
check('Terceiro mais recente contem before', isset($recent3['before']));

// Entradas anteriores devem ter sido podadas (sem payload before, com flag pruned)
$prunedEntry = $history[0];
check('Entradas anteriores foram podadas (sem before)', !isset($prunedEntry['before']));
check('Entrada podada tem flag pruned = true', ($prunedEntry['pruned'] ?? false) === true);
check('Entrada podada retém metadados', isset($prunedEntry['meta']['plugin_php']['size']));

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 5: Reversão de Passo (RevertStep)' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

$plugin->refresh();
$preRevertVal = $plugin->plugin_php;
$revertible = PluginStepEngine::canRevert($plugin->step_journal['php']);
check('Última entrada é passível de reversão', $revertible === true);

// Faz a reversão
$reverted = PluginStepEngine::revertStep($plugin, 'php');
check('revertStep() retornou sucesso', $reverted === true);

$plugin->refresh();
check('Valor anterior do PHP foi restaurado', $plugin->plugin_php === "<?php // edit 4");
check('Passo atualizado indica origem da reversão', ($plugin->step_journal['php']['source'] ?? '') === 'revert');

// ═══════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 6: Filtro Público de Resposta (publicJournal)' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

$public = PluginStepEngine::publicJournal($plugin->step_journal);
check('publicJournal() exportou os passos corretos', isset($public['php']));
check('publicJournal() adicionou flag revertible', isset($public['php']['revertible']));

// O histórico no publicJournal não deve conter snapshots de regressão 'before'
$publicHistory = $public['php']['history'] ?? [];
$hasBefore = false;
foreach ($publicHistory as $e) {
    if (isset($e['before'])) {
        $hasBefore = true;
    }
}
check('publicJournal() limpou todos os payloads "before"', $hasBefore === false);

// ─── Limpeza ────────────────────────────────────────────────────────────────
$plugin->forceDelete();

// ═══════════════════════════════════════════════════
// RESULTADO FINAL
// ═══════════════════════════════════════════════════
$total = $passed + $failed;
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo "RESULTADO FINAL: {$passed} passou, {$failed} falhou" . PHP_EOL;
if ($failed === 0) {
    echo '✅ TODOS OS TESTES DE UNIDADE DO MOTOR DE PASSOS PASSARAM' . PHP_EOL;
} else {
    echo "❌ {$failed} TESTE(S) FALHARAM" . PHP_EOL;
}
exit($failed > 0 ? 1 : 0);
