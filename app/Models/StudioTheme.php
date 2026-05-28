<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class StudioTheme extends Model
{
    use SoftDeletes;

    protected $table = 'studio_themes';

    protected $fillable = [
        'name', 'label', 'description', 'version', 'preview_url',
        'colors', 'fonts', 'sections', 'status', 'is_published', 'animus_package_uuid',
    ];

    protected $casts = [
        'colors'       => 'array',
        'fonts'        => 'array',
        'sections'     => 'array',
        'is_published' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($m) => $m->uuid ??= (string) Str::uuid());
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::where('key', $key)->value('value') ?? $default;
    }
}
