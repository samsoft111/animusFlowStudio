<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudioAiRecipe extends Model
{
    protected $table = 'studio_ai_recipes';

    protected $fillable = [
        'recipe_type',
        'name',
        'description',
        'prompt_pattern',
        'code_templates',
        'reply_template',
        'hits',
        'confidence_score',
        'is_enabled',
        'last_used_at',
        'tokens_saved',
        'placeholder_types',
        'fuzzy_threshold',
    ];

    protected $casts = [
        'code_templates'   => 'array',
        'placeholder_types'=> 'array',
        'hits'             => 'integer',
        'confidence_score' => 'integer',
        'is_enabled'       => 'boolean',
        'tokens_saved'     => 'integer',
        'fuzzy_threshold'  => 'integer',
        'last_used_at'     => 'datetime',
    ];

    /** Tokens estimated per local recipe execution */
    const TOKENS_PER_HIT = 200;

    /** Minimum confidence score to auto-execute */
    const MIN_CONFIDENCE = 70;

    /**
     * Match a prompt against enabled, sufficiently confident recipes.
     * First tries exact Regex match; falls back to fuzzy similarity.
     *
     * @return array|null [reply, updates, recipe, cached, fuzzy, similarity]
     */
    public static function matchAndResolve(string $contextType, string $prompt): ?array
    {
        $normalizedPrompt = preg_replace('/\s+/', ' ', mb_strtolower(trim($prompt)));

        $recipes = self::where('recipe_type', $contextType)
            ->where('is_enabled', true)
            ->where('confidence_score', '>=', self::MIN_CONFIDENCE)
            ->get();

        // 1. Exact Regex match
        foreach ($recipes as $recipe) {
            $result = self::tryExactMatch($recipe, $normalizedPrompt, $prompt);
            if ($result) return $result;
        }

        // 2. Fuzzy match fallback
        foreach ($recipes as $recipe) {
            $result = self::tryFuzzyMatch($recipe, $normalizedPrompt, $prompt);
            if ($result) return $result;
        }

        return null;
    }

    private static function tryExactMatch(self $recipe, string $normalizedPrompt, string $originalPrompt): ?array
    {
        $normalizedPattern = preg_replace('/\s+/', ' ', mb_strtolower(trim($recipe->prompt_pattern)));

        $regex = preg_quote($normalizedPattern, '/');
        $regex = preg_replace('/\\\\\{([a-zA-Z0-9_]+)\\\\\}/', '(?P<$1>.+?)', $regex);
        $regex = '/^' . $regex . '$/iu';

        // Match against the whitespace-normalised ORIGINAL prompt (not the
        // lowercased one) so captured placeholder values keep their casing —
        // e.g. the font name "Outfit" must not become "outfit". The regex is
        // case-insensitive (/i), so the literal pattern text still matches.
        $wsOriginal = preg_replace('/\s+/', ' ', trim($originalPrompt));

        if (!preg_match($regex, $wsOriginal, $matches)) {
            return null;
        }

        $variables = [];
        foreach ($matches as $key => $val) {
            if (is_string($key)) {
                $variables[$key] = trim($val);
            }
        }

        // Validate placeholder types if defined
        $validationError = self::validatePlaceholders($recipe, $variables);
        if ($validationError) return null;

        return self::buildResult($recipe, $variables, false, 100);
    }

    private static function tryFuzzyMatch(self $recipe, string $normalizedPrompt, string $originalPrompt): ?array
    {
        $threshold = $recipe->fuzzy_threshold ?? 80;

        // Strip placeholders from pattern to compare bare text
        $barePattern = mb_strtolower(preg_replace('/\{[a-zA-Z0-9_]+\}/', '', $recipe->prompt_pattern));
        $barePattern = preg_replace('/\s+/', ' ', trim($barePattern));
        $barePrompt  = preg_replace('/\{[a-zA-Z0-9_]+\}/', '', $normalizedPrompt);
        $barePrompt  = preg_replace('/\s+/', ' ', trim($barePrompt));

        similar_text($barePrompt, $barePattern, $percent);

        if ($percent < $threshold) return null;

        // Try to extract placeholder values by matching what we can
        $normalizedPattern = preg_replace('/\s+/', ' ', mb_strtolower(trim($recipe->prompt_pattern)));
        $regex = preg_quote($normalizedPattern, '/');
        $regex = preg_replace('/\\\\\{([a-zA-Z0-9_]+)\\\\\}/', '(?P<$1>.+?)', $regex);
        $regex = '/^' . $regex . '$/iu';

        // Same as exact match: capture from the original-cased prompt so font
        // names and other text placeholders preserve their casing.
        $wsOriginal = preg_replace('/\s+/', ' ', trim($originalPrompt));

        $variables = [];
        if (preg_match($regex, $wsOriginal, $matches)) {
            foreach ($matches as $key => $val) {
                if (is_string($key)) {
                    $variables[$key] = trim($val);
                }
            }
        }

        return self::buildResult($recipe, $variables, true, (int) round($percent));
    }

    private static function validatePlaceholders(self $recipe, array $variables): ?string
    {
        $types = $recipe->placeholder_types ?? [];
        foreach ($types as $name => $type) {
            $val = $variables[$name] ?? '';
            switch ($type) {
                case 'color':
                    if ($val && !preg_match('/^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/', $val)) {
                        return "O valor '{$val}' não é uma cor válida (use formato #rgb ou #rrggbb).";
                    }
                    break;
                case 'url':
                    if ($val && !filter_var($val, FILTER_VALIDATE_URL)) {
                        return "O valor '{$val}' não é uma URL válida.";
                    }
                    break;
                case 'number':
                    if ($val && !is_numeric($val)) {
                        return "O valor '{$val}' não é um número válido.";
                    }
                    break;
            }
        }
        return null;
    }

    private static function buildResult(self $recipe, array $variables, bool $fuzzy, int $similarity): array
    {
        $reply = $recipe->reply_template;
        foreach ($variables as $varName => $varVal) {
            $reply = str_ireplace('{{' . $varName . '}}', $varVal, $reply);
        }

        $updates = $recipe->code_templates;
        if (is_array($updates)) {
            $updates = self::replacePlaceholders($updates, $variables);
        }

        $recipe->increment('hits');
        $recipe->increment('tokens_saved', self::TOKENS_PER_HIT);
        $recipe->update(['last_used_at' => now()]);

        return [
            'reply'      => $reply,
            'updates'    => $updates,
            'recipe'     => $recipe->name,
            'fuzzy'      => $fuzzy,
            'similarity' => $similarity,
        ];
    }

    /**
     * Recursively replace placeholders {{variable}} in arrays.
     */
    private static function replacePlaceholders(array $array, array $variables): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result[$key] = self::replacePlaceholders($value, $variables);
            } elseif (is_string($value)) {
                $resolved = $value;
                foreach ($variables as $varName => $varVal) {
                    $resolved = str_ireplace('{{' . $varName . '}}', $varVal, $resolved);
                }
                $result[$key] = $resolved;
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Dry-run match and resolve a specific recipe against a test prompt.
     * Does not persist hits, tokens_saved, or last_used_at.
     */
    public static function testResolve(int $id, string $prompt): array
    {
        $recipe = self::findOrFail($id);
        $normalizedPrompt = preg_replace('/\s+/', ' ', mb_strtolower(trim($prompt)));

        $normalizedPattern = preg_replace('/\s+/', ' ', mb_strtolower(trim($recipe->prompt_pattern)));
        $regex = preg_quote($normalizedPattern, '/');
        $regex = preg_replace('/\\\\\{([a-zA-Z0-9_]+)\\\\\}/', '(?P<$1>.+?)', $regex);
        $regex = '/^' . $regex . '$/iu';

        // 1. Exact match attempt
        if (preg_match($regex, $normalizedPrompt, $matches)) {
            $variables = [];
            foreach ($matches as $key => $val) {
                if (is_string($key)) {
                    $variables[$key] = trim($val);
                }
            }
            $err = self::validatePlaceholders($recipe, $variables);
            if ($err) {
                return ['success' => false, 'error' => $err];
            }

            $reply = $recipe->reply_template;
            foreach ($variables as $varName => $varVal) {
                $reply = str_ireplace('{{' . $varName . '}}', $varVal, $reply);
            }
            $updates = $recipe->code_templates;
            if (is_array($updates)) {
                $updates = self::replacePlaceholders($updates, $variables);
            }

            return [
                'success'    => true,
                'reply'      => $reply,
                'updates'    => $updates,
                'fuzzy'      => false,
                'similarity' => 100,
            ];
        }

        // 2. Fuzzy match fallback
        $threshold = $recipe->fuzzy_threshold ?? 80;
        $barePattern = mb_strtolower(preg_replace('/\{[a-zA-Z0-9_]+\}/', '', $recipe->prompt_pattern));
        $barePattern = preg_replace('/\s+/', ' ', trim($barePattern));
        $barePrompt  = preg_replace('/\{[a-zA-Z0-9_]+\}/', '', $normalizedPrompt);
        $barePrompt  = preg_replace('/\s+/', ' ', trim($barePrompt));

        similar_text($barePrompt, $barePattern, $percent);

        if ($percent >= $threshold) {
            $variables = [];
            if (preg_match($regex, $normalizedPrompt, $matches)) {
                foreach ($matches as $key => $val) {
                    if (is_string($key)) {
                        $variables[$key] = trim($val);
                    }
                }
            }

            $reply = $recipe->reply_template;
            foreach ($variables as $varName => $varVal) {
                $reply = str_ireplace('{{' . $varName . '}}', $varVal, $reply);
            }
            $updates = $recipe->code_templates;
            if (is_array($updates)) {
                $updates = self::replacePlaceholders($updates, $variables);
            }

            return [
                'success'    => true,
                'reply'      => $reply,
                'updates'    => $updates,
                'fuzzy'      => true,
                'similarity' => (int) round($percent),
            ];
        }

        return [
            'success' => false,
            'error'   => "O prompt não corresponde ao padrão desta receita.",
        ];
    }
}

