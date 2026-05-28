<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('studio_themes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 100)->unique()->comment('slug');
            $table->string('label', 200);
            $table->text('description')->nullable();
            $table->string('version', 20)->default('1.0.0');
            $table->string('preview_url', 500)->nullable();
            $table->json('colors')->nullable()->comment('light/dark token map');
            $table->json('fonts')->nullable();
            $table->json('sections')->nullable()->comment('AI-generated Blade sections');
            $table->string('status', 20)->default('draft')->comment('draft|ready|published');
            $table->boolean('is_published')->default(false);
            $table->string('animus_package_uuid', 36)->nullable()->comment('Animus registry UUID after publish');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('studio_plugins', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 100)->unique()->comment('slug, e.g. af-hello-bar');
            $table->string('label', 200);
            $table->text('description')->nullable();
            $table->string('version', 20)->default('1.0.0');
            $table->json('hooks')->nullable()->comment('page.render|content.publish|admin.sidebar');
            $table->json('settings_schema')->nullable()->comment('Dynamic settings fields schema');
            $table->longText('plugin_php')->nullable()->comment('Generated Plugin.php source');
            $table->longText('widget_blade')->nullable()->comment('Generated widget.blade.php');
            $table->longText('widget_js')->nullable()->comment('Generated widget.js');
            $table->string('status', 20)->default('draft')->comment('draft|ready|published');
            $table->boolean('is_published')->default(false);
            $table->string('animus_package_uuid', 36)->nullable()->comment('Animus registry UUID after publish');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('studio_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('group', 50)->default('general');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('studio_settings');
        Schema::dropIfExists('studio_plugins');
        Schema::dropIfExists('studio_themes');
    }
};
