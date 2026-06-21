<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\StudioTheme;

/**
 * Motor de passos — o "espelho" do processo de criação de tema.
 *
 * Responsável por:
 *  1. Classificar a que passo (Detalhes/Design/Layout/…) pertence cada pedido,
 *     vindo do Chat IA ou de edição manual (híbrido: campos → palavras-chave → IA).
 *  2. Registar no schema espelho (step_journal) cada alteração com estado, origem,
 *     data e snapshot do valor anterior — permitindo reverter um passo específico.
 */
class ThemeStepEngine
{
    /** Mapa campo → passo (a "lógica do processo"). */
    public const FIELD_STEP = [
        'label'         => 'details',
        'description'   => 'details',
        'version'       => 'details',
        'status'        => 'details',
        'colors'        => 'design',
        'fonts'         => 'design',
        'variants'      => 'variants',
        'layout_config' => 'layout',
        'capabilities'  => 'capabilities',
        'assets'        => 'assets',
        'sections'      => 'sections',
        'components'    => 'components',
        'custom_css'    => 'code',
        'custom_js'     => 'code',
    ];

    /** Rótulos legíveis por passo. */
    public const STEP_LABELS = [
        'details'      => 'Detalhes',
        'design'       => 'Design',
        'variants'     => 'Variantes',
        'layout'       => 'Layout',
        'capabilities' => 'Capacidades',
        'assets'       => 'Assets',
        'sections'     => 'Secções',
        'components'   => 'Componentes',
        'code'         => 'Código',
    ];

    /** Palavras-chave por passo (fallback determinístico para texto livre, PT-PT). */
    private const KEYWORDS = [
        'design'       => ['cor', 'cores', 'paleta', 'fonte', 'tipografia', 'primária', 'primaria', 'secundária', 'accent', 'realce', 'branding', 'dark mode', 'modo escuro'],
        'layout'       => ['layout', 'header', 'cabeçalho', 'cabecalho', 'rodapé', 'rodape', 'footer', 'navegação', 'navegacao', 'menu', 'navbar', 'largura', 'max_width', 'sticky', 'espaçamento', 'espacamento', 'spacing'],
        'capabilities' => ['parallax', 'animaç', 'animac', 'lightbox', 'cookie', 'preloader', 'scroll', 'mega menu', 'pesquisa', 'capacidade', 'funcionalidade especial'],
        'sections'     => ['secção', 'seccao', 'secções', 'seccoes', 'hero', 'features', 'funcionalidades', 'preços', 'precos', 'pricing', 'testemunhos', 'galeria', 'faq', 'contacto', 'contato', 'cta', 'apelo'],
        'code'         => ['css', 'js', 'javascript', 'código', 'codigo', 'custom', 'estilo personalizado', 'script'],
        'variants'     => ['variante', 'variantes', 'paleta alternativa', 'skin', 'esquema de cor'],
        'assets'       => ['logo', 'logótipo', 'logotipo', 'favicon', 'imagem de fundo', 'og image', 'imagem og'],
        'details'      => ['nome do tema', 'título do tema', 'titulo do tema', 'descrição', 'descricao', 'versão', 'versao', 'renomear'],
    ];

    /** Passos cobertos por campos (os que entram no espelho). */
    public static function steps(): array
    {
        return array_keys(self::STEP_LABELS);
    }

    public static function label(string $step): string
    {
        return self::STEP_LABELS[$step] ?? $step;
    }

    /** Resolve os passos afectados por um conjunto de campos alterados (determinístico). */
    public static function stepsForFields(array $fields): array
    {
        $steps = [];
        foreach ($fields as $f) {
            if (isset(self::FIELD_STEP[$f])) {
                $steps[self::FIELD_STEP[$f]] = true;
            }
        }
        return array_keys($steps);
    }

    /** Classificação por palavras-chave de uma mensagem de texto livre. */
    public static function keywordStep(string $message): ?string
    {
        $m = mb_strtolower($message);
        $best = null; $bestHits = 0;
        foreach (self::KEYWORDS as $step => $words) {
            $hits = 0;
            foreach ($words as $w) {
                if (str_contains($m, $w)) {
                    $hits++;
                }
            }
            if ($hits > $bestHits) {
                $bestHits = $hits; $best = $step;
            }
        }
        return $best;
    }

    /**
     * Classificação HÍBRIDA do pedido → passo(s).
     * 1) Determinístico pelos campos já alterados (fonte de verdade).
     * 2) Palavras-chave do texto.
     * 3) IA (só quando ambíguo) — opcional, controlado por $allowAi.
     *
     * Devolve ['step' => string|null, 'steps' => array, 'method' => 'fields|keyword|ai|none'].
     */
    public static function classify(string $message, array $changedFields = [], bool $allowAi = true): array
    {
        $steps = self::stepsForFields($changedFields);
        if (!empty($steps)) {
            return ['step' => $steps[0], 'steps' => $steps, 'method' => 'fields'];
        }

        $kw = self::keywordStep($message);
        if ($kw !== null) {
            return ['step' => $kw, 'steps' => [$kw], 'method' => 'keyword'];
        }

        if ($allowAi && trim($message) !== '') {
            $ai = AIEngine::classifyThemeStep($message, self::steps());
            if ($ai !== null) {
                return ['step' => $ai, 'steps' => [$ai], 'method' => 'ai'];
            }
        }

        return ['step' => null, 'steps' => [], 'method' => 'none'];
    }

    /**
     * Regista no espelho as alterações de um conjunto de campos.
     * $before = mapa campo → valor anterior (snapshot para revert).
     */
    public static function record(StudioTheme $theme, array $changedFields, string $source, string $summary, array $before): array
    {
        $changedFields = array_values(array_filter($changedFields, fn($f) => isset(self::FIELD_STEP[$f])));
        if (empty($changedFields)) {
            return [];
        }

        $journal = is_array($theme->step_journal) ? $theme->step_journal : [];
        $now      = now()->toIso8601String();
        $affected = [];

        // Agrupa os campos pelo respectivo passo
        $byStep = [];
        foreach ($changedFields as $f) {
            $byStep[self::FIELD_STEP[$f]][] = $f;
        }

        foreach ($byStep as $step => $fields) {
            $entry = [
                'at'      => $now,
                'source'  => $source, // chat | manual | build
                'summary' => mb_substr($summary, 0, 240),
                'fields'  => $fields,
                'before'  => array_intersect_key($before, array_flip($fields)),
            ];

            $node = $journal[$step] ?? ['status' => 'pending', 'source' => $source, 'updated_at' => $now, 'history' => []];
            $history   = $node['history'] ?? [];
            $history[] = $entry;
            // Limite defensivo de tamanho — guarda as últimas 20 entradas por passo
            $node['history']    = array_slice($history, -20);
            $node['status']     = 'done';
            $node['source']     = $source;
            $node['updated_at'] = $now;
            $journal[$step]     = $node;

            $affected[] = $step;
        }

        $theme->update(['step_journal' => $journal]);
        return $affected;
    }

    /**
     * Reverte um passo: restaura o snapshot anterior da última entrada do passo
     * e remove essa entrada do histórico (undo passo-a-passo). Devolve true se reverteu.
     */
    public static function revertStep(StudioTheme $theme, string $step): bool
    {
        $journal = is_array($theme->step_journal) ? $theme->step_journal : [];
        $node    = $journal[$step] ?? null;
        if (!$node || empty($node['history'])) {
            return false;
        }

        $history = $node['history'];
        $last    = array_pop($history);
        $before  = $last['before'] ?? [];

        // Restaura os campos do snapshot (apenas campos válidos do passo)
        $restore = [];
        foreach ($before as $field => $value) {
            if ((self::FIELD_STEP[$field] ?? null) === $step) {
                $restore[$field] = $value;
            }
        }
        if (!empty($restore)) {
            $theme->update($restore);
        }

        // Actualiza o nó do passo
        $node['history'] = $history;
        if (empty($history)) {
            $node['status'] = 'pending';
        }
        $node['updated_at'] = now()->toIso8601String();
        $node['source']     = 'revert';
        $journal[$step]     = $node;
        $theme->update(['step_journal' => $journal]);

        return true;
    }
}
