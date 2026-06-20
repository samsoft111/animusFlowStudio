<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudioAiCommandCache extends Model
{
    protected $table = 'studio_ai_command_cache';

    protected $fillable = [
        'prompt_hash',
        'prompt',
        'context_type',
        'reply',
        'updates',
        'build',
        'hits',
    ];

    protected $casts = [
        'updates' => 'array',
        'build'   => 'array',
        'hits'    => 'integer',
    ];

    /**
     * Resolve a cached resolution for a given prompt and context type.
     */
    public static function getResolution(string $contextType, string $prompt): ?self
    {
        $hash = hash('sha256', trim(mb_strtolower($prompt)));
        return self::where('context_type', $contextType)->where('prompt_hash', $hash)->first();
    }

    /**
     * Cache a new resolution.
     */
    public static function cacheResolution(string $contextType, string $prompt, string $reply, ?array $updates, ?array $build): self
    {
        $hash = hash('sha256', trim(mb_strtolower($prompt)));
        return self::updateOrCreate(
            ['context_type' => $contextType, 'prompt_hash' => $hash],
            [
                'prompt'  => $prompt,
                'reply'   => $reply,
                'updates' => $updates,
                'build'   => $build,
            ]
        );
    }
}
