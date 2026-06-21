<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

/**
 * Base partilhada dos motores de passos ("espelho" do processo).
 *
 * Contém toda a lógica comum — classificação híbrida, registo no espelho com
 * snapshot, poda da janela de snapshots, reversão e versão pública. As subclasses
 * (ThemeStepEngine, PluginStepEngine) fornecem apenas os DADOS específicos do seu
 * domínio através das constantes FIELD_STEP / STEP_LABELS / KEYWORDS e do método
 * aiClassify(). Late static binding (static::) garante que cada subclasse usa os
 * seus próprios dados sem reimplementar a lógica.
 *
 * Subclasses DEVEM definir:
 *   public const FIELD_STEP   — mapa campo → passo
 *   public const STEP_LABELS  — passo → rótulo legível
 *   protected const KEYWORDS  — passo → palavras-chave (fallback de texto livre)
 *   protected static function aiClassify(string $message): ?string
 */
abstract class AbstractStepEngine
{
    /** Máximo de entradas de histórico por passo (metadados). */
    protected const HISTORY_LIMIT = 20;

    /** Nº de entradas recentes que retêm o snapshot completo (revertível). */
    protected const SNAPSHOT_WINDOW = 3;

    /**
     * Classificação por IA (só quando determinístico/keyword falham).
     * Default: sem IA. As subclasses sobrepõem para invocar o classificador certo.
     */
    protected static function aiClassify(string $message): ?string
    {
        return null;
    }

    /** Passos cobertos por campos. */
    public static function steps(): array
    {
        return array_keys(static::STEP_LABELS);
    }

    public static function label(string $step): string
    {
        return static::STEP_LABELS[$step] ?? $step;
    }

    /** Resolve os passos afectados por um conjunto de campos alterados. */
    public static function stepsForFields(array $fields): array
    {
        $steps = [];
        foreach ($fields as $f) {
            if (isset(static::FIELD_STEP[$f])) {
                $steps[static::FIELD_STEP[$f]] = true;
            }
        }
        return array_keys($steps);
    }

    /** Classificação por palavras-chave de uma mensagem de texto livre. */
    public static function keywordStep(string $message): ?string
    {
        $m = mb_strtolower($message);
        $best = null; $bestHits = 0;
        foreach (static::KEYWORDS as $step => $words) {
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
     * 3) IA (só quando ambíguo) — controlado por $allowAi.
     *
     * Devolve ['step' => string|null, 'steps' => array, 'method' => 'fields|keyword|ai|none'].
     */
    public static function classify(string $message, array $changedFields = [], bool $allowAi = true): array
    {
        $steps = static::stepsForFields($changedFields);
        if (!empty($steps)) {
            return ['step' => $steps[0], 'steps' => $steps, 'method' => 'fields'];
        }

        $kw = static::keywordStep($message);
        if ($kw !== null) {
            return ['step' => $kw, 'steps' => [$kw], 'method' => 'keyword'];
        }

        if ($allowAi && trim($message) !== '') {
            $ai = static::aiClassify($message);
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
    public static function record(Model $model, array $changedFields, string $source, string $summary, array $before): array
    {
        $changedFields = array_values(array_filter($changedFields, fn($f) => isset(static::FIELD_STEP[$f])));
        if (empty($changedFields)) {
            return [];
        }

        $journal = is_array($model->step_journal) ? $model->step_journal : [];
        $now      = now()->toIso8601String();
        $affected = [];

        // Agrupa os campos pelo respectivo passo
        $byStep = [];
        foreach ($changedFields as $f) {
            $byStep[static::FIELD_STEP[$f]][] = $f;
        }

        foreach ($byStep as $step => $fields) {
            $beforeVals = array_intersect_key($before, array_flip($fields));

            $entry = [
                'at'      => $now,
                'source'  => $source, // chat | manual | build
                'summary' => mb_substr($summary, 0, 240),
                'fields'  => $fields,
                'before'  => $beforeVals,
                'meta'    => self::fieldsMeta($beforeVals),
            ];

            $node = $journal[$step] ?? ['status' => 'pending', 'source' => $source, 'updated_at' => $now, 'history' => []];
            $history   = $node['history'] ?? [];
            $history[] = $entry;

            $node['history']    = self::pruneHistory($history);
            $node['status']     = 'done';
            $node['source']     = $source;
            $node['updated_at'] = $now;
            $journal[$step]     = $node;

            $affected[] = $step;
        }

        $model->update(['step_journal' => $journal]);
        return $affected;
    }

    /**
     * Reverte um passo: restaura o snapshot anterior da última entrada do passo
     * e remove essa entrada do histórico (undo passo-a-passo). Devolve true se
     * reverteu; false se a última entrada já não tem snapshot (foi podada).
     */
    public static function revertStep(Model $model, string $step): bool
    {
        $journal = is_array($model->step_journal) ? $model->step_journal : [];
        $node    = $journal[$step] ?? null;
        if (!$node || empty($node['history'])) {
            return false;
        }

        $history = $node['history'];
        $lastIdx = array_key_last($history);
        $before  = $history[$lastIdx]['before'] ?? null;

        if (!is_array($before) || empty($before)) {
            return false;
        }

        array_pop($history);

        $restore = [];
        foreach ($before as $field => $value) {
            if ((static::FIELD_STEP[$field] ?? null) === $step) {
                $restore[$field] = $value;
            }
        }
        if (!empty($restore)) {
            $model->update($restore);
        }

        $node['history'] = $history;
        if (empty($history)) {
            $node['status'] = 'pending';
        }
        $node['updated_at'] = now()->toIso8601String();
        $node['source']     = 'revert';
        $journal[$step]     = $node;
        $model->update(['step_journal' => $journal]);

        return true;
    }

    /**
     * Versão "pública" do espelho para enviar ao frontend: remove os snapshots
     * completos ('before') e mantém apenas metadados, marcando se cada passo é
     * revertível.
     */
    public static function publicJournal(?array $journal): array
    {
        if (!is_array($journal)) {
            return [];
        }
        $out = [];
        foreach ($journal as $step => $node) {
            $node['revertible'] = static::canRevert($node);
            if (!empty($node['history']) && is_array($node['history'])) {
                $node['history'] = array_map(function ($e) {
                    unset($e['before']);
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

    /** Metadados leves (hash + tamanho em bytes) por campo. */
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
        $history = array_slice($history, -static::HISTORY_LIMIT);
        $n = count($history);
        foreach ($history as $i => &$entry) {
            if ($i < $n - static::SNAPSHOT_WINDOW) {
                unset($entry['before']);
                $entry['pruned'] = true;
            }
        }
        unset($entry);
        return array_values($history);
    }
}
