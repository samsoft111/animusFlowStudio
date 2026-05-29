<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('studio_themes', function (Blueprint $table) {
            // ── Layout configuration ──────────────────────────────────────
            // header_type: glass|solid|transparent|sidebar|centered
            // nav_type: horizontal|hamburger|mega|fullscreen|sidebar
            // nav_position: left|center|right
            // footer_type: simple|columns|minimal|dark|accent
            // layout_type: full-width|boxed|sidebar-left|sidebar-right
            // max_width: 960|1120|1280|1440|full
            // spacing: compact|normal|spacious
            // header_sticky: bool
            // header_cta_text / header_cta_url
            // show_dark_toggle: bool
            // back_to_top: bool
            $table->json('layout_config')->nullable()->comment('Header/nav/footer/layout variant configuration');

            // ── Feature capabilities ──────────────────────────────────────
            // video_bg, parallax, animations, lightbox, mega_menu,
            // sticky_header, search, cookie_banner, back_to_top,
            // preloader, scroll_progress
            $table->json('capabilities')->nullable()->comment('Feature flags: video_bg, parallax, lightbox, etc.');

            // ── Uploaded assets ───────────────────────────────────────────
            // logo, logo_dark, favicon, hero_image, hero_video, og_image
            $table->json('assets')->nullable()->comment('Uploaded media: logo, hero_image, hero_video, favicon, og_image');

            // ── Custom component overrides ────────────────────────────────
            // header, footer, nav — full Blade template overrides
            $table->json('components')->nullable()->comment('Custom Blade: header, footer, nav component overrides');

            // ── Custom CSS / JS ───────────────────────────────────────────
            $table->longText('custom_css')->nullable()->comment('Custom CSS injected after theme tokens');
            $table->longText('custom_js')->nullable()->comment('Custom JS injected before </body>');

            // ── Color/layout variants (skins) ─────────────────────────────
            // Array of {name, label, colors: {light, dark}} — alternative palettes
            $table->json('variants')->nullable()->comment('Alternative colour skins for this theme');
        });
    }

    public function down(): void
    {
        Schema::table('studio_themes', function (Blueprint $table) {
            $table->dropColumn([
                'layout_config', 'capabilities', 'assets',
                'components', 'custom_css', 'custom_js', 'variants',
            ]);
        });
    }
};
