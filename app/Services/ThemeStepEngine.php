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
 *
 * ÂMBITO (decisão deliberada): este motor é EXCLUSIVO de temas. Os plugins têm
 * um fluxo chat/build/manual semelhante mas NÃO têm o stepper de progresso por
 * passos (Detalhes/Design/…), pelo que o conceito de "espelho por passo" não
 * mapeia para eles. Replicar isto para plugins seria duplicar o motor para um
 * conjunto de campos diferente sem uma UI de progresso a espelhar. Fica como
 * possível trabalho futuro, não como dívida — é uma escolha de âmbito.
 */
class ThemeStepEngine
{
    /** Máximo de entradas de histórico por passo (metadados). */
    private const HISTORY_LIMIT = 20;

    /** Nº de entradas recentes que retêm o snapshot completo (revertível). */
    private const SNAPSHOT_WINDOW = 3;

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
            $beforeVals = array_intersect_key($before, array_flip($fields));

            $entry = [
                'at'      => $now,
                'source'  => $source, // chat | manual | build
                'summary' => mb_substr($summary, 0, 240),
                'fields'  => $fields,
                'before'  => $beforeVals,                 // valor completo (podado fora da janela)
                'meta'    => self::fieldsMeta($beforeVals), // hash+tamanho — sempre leve
            ];

            $node = $journal[$step] ?? ['status' => 'pending', 'source' => $source, 'updated_at' => $now, 'history' => []];
            $history   = $node['history'] ?? [];
            $history[] = $entry;

            // Limite de entradas + poda dos snapshots pesados (mantém o valor
            // completo só nas últimas SNAPSHOT_WINDOW entradas; as restantes
            // ficam só com metadados, evitando inchar a linha com HTML repetido).
            $node['history']    = self::pruneHistory($history);
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
     * e remove essa entrada do histórico (undo passo-a-passo). Devolve true se
     * reverteu; false se a última entrada já não tem snapshot (foi podada).
     */
    public static function revertStep(StudioTheme $theme, string $step): bool
    {
        $journal = is_array($theme->step_journal) ? $theme->step_journal : [];
        $node    = $journal[$step] ?? null;
        if (!$node || empty($node['history'])) {
            return false;
        }

        $history = $node['history'];
        $lastIdx = array_key_last($history);
        $before  = $history[$lastIdx]['before'] ?? null;

        // Snapshot podado (fora da janela) → não há valor para restaurar.
        if (!is_array($before) || empty($before)) {
            return false;
        }

        array_pop($history);

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

    /**
     * Versão "pública" do espelho para enviar ao frontend: remove os snapshots
     * completos ('before') — que podem ser HTML pesado — e mantém apenas os
     * metadados leves, marcando em cada passo se a última entrada é revertível.
     */
    public static function publicJournal(?array $journal): array
    {
        if (!is_array($journal)) {
            return [];
        }
        $out = [];
        foreach ($journal as $step => $node) {
            $node['revertible'] = self::canRevert($node);
            if (!empty($node['history']) && is_array($node['history'])) {
                $node['history'] = array_map(function ($e) {
                    unset($e['before']);            // não enviar o valor completo ao browser
                    return $e;
                }, $node['history']);
            }
            $out[$step] = $node;
        }
        return $out;
    }

    /** Indica se a última entrada de um passo ainda é revertível (tem snapshot). */
    public static function canRevert(?array $node): bool
    {
        if (!is_array($node) || empty($node['history'])) {
            return false;
        }
        $last = $node['history'][array_key_last($node['history'])];
        return is_array($last['before'] ?? null) && !empty($last['before']);
    }

    /** Metadados leves (hash + tamanho em bytes) por campo — para exibir e comparar. */
    private static function fieldsMeta(array $values): array
    {
        $meta = [];
        foreach ($values as $field => $value) {
            $json = json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
            $meta[$field] = ['size' => strlen($json), 'hash' => substr(sha1($json), 0, 12)];
        }
        return $meta;
    }

    /**
     * Mantém no máximo HISTORY_LIMIT entradas e retém o snapshot completo
     * ('before') apenas nas últimas SNAPSHOT_WINDOW; as restantes ficam só com
     * metadados ('meta'), evitando guardar HTML pesado repetido.
     */
    private static function pruneHistory(array $history): array
    {
        $history = array_slice($history, -self::HISTORY_LIMIT);
        $n = count($history);
        foreach ($history as $i => &$entry) {
            if ($i < $n - self::SNAPSHOT_WINDOW) {
                unset($entry['before']);
                $entry['pruned'] = true;
            }
        }
        unset($entry);
        return array_values($history);
    }
}
