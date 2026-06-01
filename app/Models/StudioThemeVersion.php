<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StudioThemeVersion extends Model
{
    protected $table = 'studio_theme_versions';

    protected $fillable = [
        'studio_theme_id',
        'version',
        'label',
        'changelog',
        'snapshot_type',
        'colors',
        'fonts',
        'sections',
        'layout_config',
        'capabilities',
        'assets',
        'components',
        'variants',
        'custom_css',
        'custom_js',
        'description',
    ];

    protected $casts = [
        'colors'        => 'array',
        'fonts'         => 'array',
        'sections'      => 'array',
        'layout_config' => 'array',
        'capabilities'  => 'array',
        'assets'        => 'array',
        'components'    => 'array',
        'variants'      => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($m) => $m->uuid ??= (string) Str::uuid());
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(StudioTheme::class, 'studio_theme_id');
    }

    /**
     * Create a version snapshot from a StudioTheme instance.
     */
    public static function snapshot(StudioTheme $theme, string $changelog = '', string $type = 'manual'): self
    {
        return self::create([
            'studio_theme_id' => $theme->id,
            'version'         => $theme->version,
            'label'           => $theme->label,
            'changelog'       => $changelog,
            'snapshot_type'   => $type,
            'colors'          => $theme->getRawOriginal('colors')
                                    ? json_decode($theme->getRawOriginal('colors'), true)
                                    : $theme->colors,
            'fonts'           => $theme->getRawOriginal('fonts')
                                    ? json_decode($theme->getRawOriginal('fonts'), true)
                                    : $theme->fonts,
            'sections'        => $theme->getRawOriginal('sections')
                                    ? json_decode($theme->getRawOriginal('sections'), true)
                                    : $theme->sections,
            'layout_config'   => $theme->getRawOriginal('layout_config')
                                    ? json_decode($theme->getRawOriginal('layout_config'), true)
                                    : $theme->layout_config,
            'capabilities'    => $theme->getRawOriginal('capabilities')
                                    ? json_decode($theme->getRawOriginal('capabilities'), true)
                                    : $theme->capabilities,
            'assets'          => $theme->getRawOriginal('assets')
                                    ? json_decode($theme->getRawOriginal('assets'), true)
                                    : $theme->assets,
            'components'      => $theme->getRawOriginal('components')
                                    ? json_decode($theme->getRawOriginal('components'), true)
                                    : $theme->components,
            'variants'        => $theme->getRawOriginal('variants')
                                    ? json_decode($theme->getRawOriginal('variants'), true)
                                    : $theme->variants,
            'custom_css'      => $theme->custom_css,
            'custom_js'       => $theme->custom_js,
            'description'     => $theme->description,
        ]);
    }
}
