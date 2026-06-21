<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\StudioPlugin;

/**
 * Motor de passos para plugins — o "espelho" do processo de criação de plugins.
 */
class PluginStepEngine
{
    /** Máximo de histórico por passo (metadados). */
    private const HISTORY_LIMIT = 20;

    /** Nº de entradas recentes que retêm o snapshot completo (revertível). */
    private const SNAPSHOT_WINDOW = 3;

    /** Mapa campo → passo (a "lógica do processo" para plugins). */
    public const FIELD_STEP = [
        'label'                  => 'details',
        'description'            => 'details',
        'version'                => 'details',
        'author'                 => 'details',
        'author_url'             => 'details',
        'category'               => 'details',
        'tags'                   => 'details',
        'license'                => 'details',
        'min_animusflow_version' => 'details',
        'homepage_url'           => 'details',
        'status'                 => 'details',
        'hooks'                  => 'hooks',
        'plugin_php'             => 'php',
        'widget_blade'           => 'widget',
        'widget_js'              => 'widget',
        'custom_css'             => 'css',
        'settings_schema'        => 'schema',
        'readme'                 => 'docs',
    ];

    /** Rótulos legíveis por passo para plugins. */
    public const STEP_LABELS = [
        'details' => 'Detalhes',
        'hooks'   => 'Hooks',
        'php'     => 'PHP',
        'widget'  => 'Widget',
        'css'     => 'CSS',
        'schema'  => 'Configurações',
        'docs'    => 'Docs',
    ];

    /** Palavras-chave por passo (fallback determinístico para texto livre, PT-PT). */
    private const KEYWORDS = [
        'details' => ['nome', 'título', 'titulo', 'descrição', 'descricao', 'versão', 'versao', 'autor', 'categoria', 'tag', 'licença', 'licenca', 'homepage', 'status'],
        'hooks'   => ['hook', 'hooks', 'evento', 'eventos', 'page.render', 'content.publish', 'admin.sidebar'],
        'php'     => ['php', 'classe', 'class', 'scaffold', 'método', 'metodo', 'função', 'funcao', 'plugin.php', 'plugin_php'],
        'widget'  => ['widget', 'blade', 'html', 'js', 'javascript', 'widget_blade', 'widget_js', 'widget.blade.php', 'widget.js'],
        'css'     => ['css', 'estilo', 'custom_css', 'plugin.css', 'classe css', 'design css', 'layout css'],
        'schema'  => ['config', 'configurac', 'configuraç', 'settings', 'schema', 'campo', 'campos', 'settings_schema'],
        'docs'    => ['readme', 'docs', 'documentação', 'documentacao', 'readme.md'],
    ];

    /** Passos cobertos por campos. */
    public static function steps(): array
    {
        return array_keys(self::STEP_LABELS);
    }

    public static function label(string $step): string
    {
        return self::STEP_LABELS[$step] ?? $step;
    }

    /** Resolve os passos afectados por um conjunto de campos alterados. */
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

    /** Classificação HÍBRIDA do pedido → passo(s). */
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
            $ai = AIEngine::classifyPluginStep($message, self::steps());
            if ($ai !== null) {
                return ['step' => $ai, 'steps' => [$ai], 'method' => 'ai'];
            }
        }

        return ['step' => null, 'steps' => [], 'method' => 'none'];
    }

    /** Regista no espelho as alterações de um conjunto de campos do plugin. */
    public static function record(StudioPlugin $plugin, array $changedFields, string $source, string $summary, array $before): array
    {
        $changedFields = array_values(array_filter($changedFields, fn($f) => isset(self::FIELD_STEP[$f])));
        if (empty($changedFields)) {
            return [];
        }

        $journal = is_array($plugin->step_journal) ? $plugin->step_journal : [];
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

        $plugin->update(['step_journal' => $journal]);
        return $affected;
    }

    /** Reverte um passo: restaura o snapshot anterior da última entrada do passo. */
    public static function revertStep(StudioPlugin $plugin, string $step): bool
    {
        $journal = is_array($plugin->step_journal) ? $plugin->step_journal : [];
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
            if ((self::FIELD_STEP[$field] ?? null) === $step) {
                $restore[$field] = $value;
            }
        }
        if (!empty($restore)) {
            $plugin->update($restore);
        }

        $node['history'] = $history;
        if (empty($history)) {
            $node['status'] = 'pending';
        }
        $node['updated_at'] = now()->toIso8601String();
        $node['source']     = 'revert';
        $journal[$step]     = $node;
        $plugin->update(['step_journal' => $journal]);

        return true;
    }

    /** Versão "pública" do espelho para enviar ao frontend. */
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
                    unset($e['before']);
                    return $e;
                }, $node['history']);
            }
            $out[$step] = $node;
        }
        return $out;
    }

    /** Indica se a última entrada de um passo ainda é revertível. */
    public static function canRevert(?array $node): bool
    {
        if (!is_array($node) || empty($node['history'])) {
            return false;
        }
        $last = $node['history'][array_key_last($node['history'])];
        return is_array($last['before'] ?? null) && !empty($last['before']);
    }

    /** Metadados leves (hash + tamanho) por campo. */
    private static function fieldsMeta(array $values): array
    {
        $meta = [];
        foreach ($values as $field => $value) {
            $json = json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
            $meta[$field] = ['size' => strlen($json), 'hash' => substr(sha1($json), 0, 12)];
        }
        return $meta;
    }

    /** Mantém no máximo HISTORY_LIMIT entradas e retém o snapshot em SNAPSHOT_WINDOW. */
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
