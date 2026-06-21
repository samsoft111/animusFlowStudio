<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class StudioTheme extends Model
{
    use SoftDeletes;

    protected $table = 'studio_themes';

    protected $fillable = [
        'name', 'label', 'description', 'version', 'preview_url',
        // Design layer
        'colors', 'fonts', 'sections',
        // Layout layer
        'layout_config',
        // Capabilities layer
        'capabilities',
        // Assets layer
        'assets',
        // Components layer
        'components',
        // Custom code
        'custom_css', 'custom_js',
        // Variants (colour skins)
        'variants',
        // Chat IA history (editor conversation + build cards)
        'chat_history',
        // Meta
        'status', 'is_published', 'animus_package_uuid',
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
        'chat_history'  => 'array',
        'is_published'  => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($m) => $m->uuid ??= (string) Str::uuid());
        static::saving(function ($m) {
            $m->is_published = ($m->status === 'published');
        });
    }

    public function versions(): HasMany
    {
        return $this->hasMany(StudioThemeVersion::class, 'studio_theme_id')->latest();
    }

    // ── Defaults merged automatically ──────────────────────────────────

    public function getLayoutConfigAttribute(mixed $value): array
    {
        $data = is_array($value) ? $value : (json_decode($value ?? '{}', true) ?? []);
        return array_merge(self::defaultLayoutConfig(), $data);
    }

    public function getCapabilitiesAttribute(mixed $value): array
    {
        $data = is_array($value) ? $value : (json_decode($value ?? '{}', true) ?? []);
        return array_merge(self::defaultCapabilities(), $data);
    }

    public static function defaultLayoutConfig(): array
    {
        return [
            'header_type'      => 'glass',       // glass|solid|transparent|sidebar|centered
            'header_sticky'    => true,
            'header_cta_text'  => '',
            'header_cta_url'   => '#',
            'nav_type'         => 'horizontal',  // horizontal|hamburger|mega|fullscreen|sidebar
            'nav_position'     => 'right',       // left|center|right
            'footer_type'      => 'simple',      // simple|columns|minimal|dark|accent
            'footer_copyright' => '',
            'footer_columns'   => [],
            'layout_type'      => 'full-width',  // full-width|boxed|sidebar-left|sidebar-right
            'max_width'        => '1120',
            'spacing'          => 'normal',      // compact|normal|spacious
            'show_dark_toggle' => true,
            'back_to_top'      => true,
        ];
    }

    public static function defaultCapabilities(): array
    {
        return [
            'video_bg'        => false,
            'parallax'        => false,
            'animations'      => true,
            'lightbox'        => false,
            'mega_menu'       => false,
            'search'          => false,
            'cookie_banner'   => false,
            'preloader'       => false,
            'scroll_progress' => false,
            'back_to_top'     => true,
        ];
    }
}
