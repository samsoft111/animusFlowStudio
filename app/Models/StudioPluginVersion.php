<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudioPluginVersion extends Model
{
    protected $table = 'studio_plugin_versions';

    protected $fillable = [
        'studio_plugin_id',
        'version',
        'label',
        'changelog',
        'snapshot',
        'is_published',
        'published_uuid',
        'created_by',
    ];

    protected $casts = [
        'snapshot'     => 'array',
        'is_published' => 'boolean',
    ];

    public function plugin(): BelongsTo
    {
        return $this->belongsTo(StudioPlugin::class, 'studio_plugin_id');
    }

    // ── Fields captured in a snapshot ──────────────────────────────────────────
    public static array $snapshotFields = [
        'name', 'label', 'description', 'version',
        'author', 'author_url', 'category', 'tags', 'license',
        'min_animusflow_version', 'homepage_url', 'status',
        'hooks', 'plugin_php', 'widget_blade', 'widget_js',
        'custom_css', 'readme', 'settings_schema',
    ];

    /** Build a snapshot array from a StudioPlugin instance. */
    public static function snapshotFrom(StudioPlugin $plugin): array
    {
        $data = [];
        foreach (self::$snapshotFields as $field) {
            $data[$field] = $plugin->{$field};
        }
        return $data;
    }
}
