<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Schema de "Definições do site" do tema — lista declarativa de campos
     * configuráveis pelo criador no AnimusFlow ao usar este tema. Cada campo:
     * { key, label, type, group, default, options?, hint?, source?, min/max/step? }.
     * Viaja no theme.json exportado como "settings", para o CMS desenhar o formulário.
     */
    public function up(): void
    {
        Schema::table('studio_themes', function (Blueprint $table) {
            $table->json('theme_settings')->nullable()->after('step_journal')
                ->comment('Schema de definições configuráveis pelo criador no AnimusFlow');
        });
    }

    public function down(): void
    {
        Schema::table('studio_themes', function (Blueprint $table) {
            $table->dropColumn('theme_settings');
        });
    }
};
