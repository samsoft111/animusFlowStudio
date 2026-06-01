<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('studio_theme_versions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('studio_theme_id')->constrained('studio_themes')->cascadeOnDelete();
            $table->string('version', 20);                   // ex: "1.0.0", "1.1.0", "2.0.0"
            $table->string('label', 200);                    // snapshot do label no momento
            $table->text('changelog')->nullable();           // nota da versão (o que mudou)
            $table->string('snapshot_type', 20)->default('manual'); // manual | auto | publish
            // Snapshot completo de todos os campos do tema
            $table->json('colors')->nullable();
            $table->json('fonts')->nullable();
            $table->json('sections')->nullable();
            $table->json('layout_config')->nullable();
            $table->json('capabilities')->nullable();
            $table->json('assets')->nullable();
            $table->json('components')->nullable();
            $table->json('variants')->nullable();
            $table->longText('custom_css')->nullable();
            $table->longText('custom_js')->nullable();
            $table->string('description', 1000)->nullable();
            $table->timestamps();

            $table->index(['studio_theme_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('studio_theme_versions');
    }
};
