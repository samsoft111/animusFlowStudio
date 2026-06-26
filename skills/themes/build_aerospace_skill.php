<?php

declare(strict_types=1);

/**
 * Regenerador / verificador do skill do tema AeroSpace — AnimusFlowStudio
 *
 * O skill `aerospace_theme_skill.md` é um SNAPSHOT do tema guardado na BD
 * (StudioTheme label = "AeroSpace"). Este script mantém os dois em sincronia
 * SEM nunca usar preg_replace sobre o conteúdo JSON (ver memória
 * "skill-json-preg-replace-gotcha": \ e $ escapam mal). Em vez disso:
 *
 *   - lê o tema da BD,
 *   - reconstrói o bloco ```json_updates``` por CONCATENAÇÃO + json_encode,
 *   - preserva o cabeçalho de prosa do .md tal como está.
 *
 * Modos:
 *   php skills/themes/build_aerospace_skill.php            → --check (default)
 *       Compara BD vs .md campo a campo e reporta drift. Não escreve nada.
 *       Sai com código 1 se houver drift (útil em CI).
 *
 *   php skills/themes/build_aerospace_skill.php --write    → regenera o .md
 *       Reescreve o bloco json_updates a partir da BD (forma canónica),
 *       mantendo o cabeçalho de prosa.
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\StudioTheme;

const SKILL_PATH  = __DIR__ . '/aerospace_theme_skill.md';
const THEME_LABEL = 'AeroSpace';
const FENCE_OPEN  = '```json_updates';

/** Ordem canónica dos campos no bloco json_updates. */
const FIELD_ORDER = [
    'label', 'description', 'version', 'status',
    'colors', 'fonts', 'layout_config', 'capabilities',
    'sections', 'custom_css', 'custom_js',
];

$write = in_array('--write', $argv, true);

// ─── Carregar o tema da BD ───────────────────────────────────────────────────
$theme = StudioTheme::where('label', THEME_LABEL)->first();
if (!$theme) {
    fwrite(STDERR, "❌ Tema '" . THEME_LABEL . "' não existe na BD.\n");
    exit(2);
}

$db = [
    'label'         => $theme->label,
    'description'   => $theme->description,
    'version'       => $theme->version,
    'status'        => $theme->status,
    'colors'        => $theme->colors,
    'fonts'         => $theme->fonts,
    'layout_config' => $theme->layout_config,
    'capabilities'  => $theme->capabilities,
    'sections'      => $theme->sections,
    'custom_css'    => $theme->custom_css,
    'custom_js'     => $theme->custom_js,
];

// ─── Ler o skill atual ───────────────────────────────────────────────────────
if (!is_file(SKILL_PATH)) {
    fwrite(STDERR, '❌ Skill não encontrado: ' . SKILL_PATH . "\n");
    exit(2);
}
$raw = file_get_contents(SKILL_PATH);

// Extrair o cabeçalho de prosa (tudo até e incluindo a abertura da fence).
$fencePos = strpos($raw, FENCE_OPEN);
if ($fencePos === false) {
    fwrite(STDERR, "❌ Bloco '" . FENCE_OPEN . "' não encontrado no skill.\n");
    exit(2);
}
$header = substr($raw, 0, $fencePos); // termina antes de "```json_updates"

// Extrair o JSON atual do skill (entre a fence aberta e a próxima ```).
$afterFence = substr($raw, $fencePos + strlen(FENCE_OPEN));
$closePos   = strpos($afterFence, '```');
$currentJson = $closePos === false ? $afterFence : substr($afterFence, 0, $closePos);
$fileData    = json_decode(trim($currentJson), true);

// ─── Normalização recursiva (ksort) para comparação independente da ordem ─────
function canon(mixed $v): mixed
{
    if (is_array($v)) {
        $isList = array_is_list($v);
        $v = array_map('canon', $v);
        if (!$isList) {
            ksort($v);
        }
        return $v;
    }
    return $v;
}

// ─── MODO WRITE ──────────────────────────────────────────────────────────────
if ($write) {
    $ordered = [];
    foreach (FIELD_ORDER as $k) {
        $ordered[$k] = $db[$k];
    }
    $json = json_encode(
        $ordered,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );

    // Concatenação — nunca preg_replace sobre o conteúdo.
    $out = $header . FENCE_OPEN . "\n" . $json . "\n```\n";
    file_put_contents(SKILL_PATH, $out);

    echo "✅ Skill regenerado a partir da BD (" . THEME_LABEL . " v{$theme->version}).\n";
    echo '   ' . strlen($json) . " caracteres no bloco json_updates.\n";
    exit(0);
}

// ─── MODO CHECK (default) ────────────────────────────────────────────────────
if (!is_array($fileData)) {
    fwrite(STDERR, "❌ O bloco json_updates do skill não é JSON válido.\n");
    exit(1);
}

echo "🔎 Comparar BD (" . THEME_LABEL . " v{$theme->version}) vs skill .md\n";
echo str_repeat('─', 60) . "\n";

$drift = [];
foreach (FIELD_ORDER as $k) {
    $a = canon($db[$k] ?? null);
    $b = canon($fileData[$k] ?? null);
    $same = $a === $b;
    printf("  %s %-14s %s\n", $same ? '✅' : '❌', $k, $same ? 'em sincronia' : 'DIVERGENTE');
    if (!$same) {
        $drift[] = $k;
    }
}

// Campos no .md que já não existem na lista canónica (lixo acumulado).
$extra = array_diff(array_keys($fileData), FIELD_ORDER);
if ($extra) {
    echo '  ⚠️  campos extra no .md: ' . implode(', ', $extra) . "\n";
}

echo str_repeat('─', 60) . "\n";
if ($drift) {
    echo '❌ Drift em: ' . implode(', ', $drift) . "\n";
    echo "   Corre com --write para regenerar o skill a partir da BD.\n";
    exit(1);
}

echo "✅ Skill em sincronia com a BD.\n";
exit(0);
