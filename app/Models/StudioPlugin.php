<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class StudioPlugin extends Model
{
    use SoftDeletes;

    protected $table = 'studio_plugins';

    protected $fillable = [
        'name', 'label', 'description', 'version',
        'hooks', 'settings_schema', 'plugin_php', 'widget_blade', 'widget_js',
        'status', 'is_published', 'animus_package_uuid',
    ];

    protected $casts = [
        'hooks'           => 'array',
        'settings_schema' => 'array',
        'is_published'    => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($m) => $m->uuid ??= (string) Str::uuid());
    }
}
