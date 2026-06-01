<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('studio_plugins', function (Blueprint $table) {
            $table->string('author', 200)->nullable()->after('version');
            $table->string('author_url', 500)->nullable()->after('author');
            $table->string('category', 100)->nullable()->after('author_url');
            $table->json('tags')->nullable()->after('category');
            $table->string('license', 100)->default('MIT')->after('tags');
            $table->string('min_animusflow_version', 20)->default('1.0.0')->after('license');
            $table->string('homepage_url', 500)->nullable()->after('min_animusflow_version');
            $table->longText('custom_css')->nullable()->after('widget_js');
            $table->longText('readme')->nullable()->after('custom_css');
        });
    }

    public function down(): void
    {
        Schema::table('studio_plugins', function (Blueprint $table) {
            $table->dropColumn([
                'author', 'author_url', 'category', 'tags', 'license',
                'min_animusflow_version', 'homepage_url', 'custom_css', 'readme',
            ]);
        });
    }
};
