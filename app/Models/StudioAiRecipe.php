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
    ];

    protected $casts = [
        'code_templates' => 'array',
        'hits'           => 'integer',
    ];

    /**
     * Match a prompt against registered recipes and resolve variables.
     *
     * @param string $contextType theme or plugin
     * @param string $prompt
     * @return array|null [reply, updates, recipe]
     */
    public static function matchAndResolve(string $contextType, string $prompt): ?array
    {
        $normalizedPrompt = preg_replace('/\s+/', ' ', trim($prompt));

        $recipes = self::where('recipe_type', $contextType)->get();

        foreach ($recipes as $recipe) {
            $normalizedPattern = preg_replace('/\s+/', ' ', trim($recipe->prompt_pattern));
            
            // Build regex from pattern. Convert {variable} to named capture group.
            $regex = preg_quote($normalizedPattern, '/');
            $regex = preg_replace('/\\\\\{([a-zA-Z0-9_]+)\\\\\}/', '(?P<$1>.+?)', $regex);
            $regex = '/^' . $regex . '$/iu';

            if (preg_match($regex, $normalizedPrompt, $matches)) {
                // Extract only named capture groups
                $variables = [];
                foreach ($matches as $key => $val) {
                    if (is_string($key)) {
                        $variables[$key] = trim($val);
                    }
                }

                // Replace in reply
                $reply = $recipe->reply_template;
                foreach ($variables as $varName => $varVal) {
                    $reply = str_ireplace('{{' . $varName . '}}', $varVal, $reply);
                }

                // Replace recursively in code templates
                $updates = $recipe->code_templates;
                if (is_array($updates)) {
                    $updates = self::replacePlaceholders($updates, $variables);
                }

                $recipe->increment('hits');

                return [
                    'reply'   => $reply,
                    'updates' => $updates,
                    'recipe'  => $recipe->name,
                ];
            }
        }

        return null;
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
}
