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
        Schema::create('studio_ai_recipes', function (Blueprint $table) {
            $table->id();
            $table->string('recipe_type', 50); // theme, plugin
            $table->string('name', 100)->unique();
            $table->string('description', 255)->nullable();
            $table->string('prompt_pattern', 255);
            $table->json('code_templates');
            $table->text('reply_template');
            $table->integer('hits')->default(0);
            $table->timestamps();

            $table->index(['recipe_type', 'name']);
        });

        // Seed default theme recipes
        \DB::table('studio_ai_recipes')->insert([
            [
                'recipe_type'    => 'theme',
                'name'           => 'mudar-cor-principal',
                'description'    => 'Mudar a cor primária do tema',
                'prompt_pattern' => 'mudar a cor principal para {cor}',
                'code_templates' => json_encode(['colors' => ['light' => ['--color-primary' => '{{cor}}']]]),
                'reply_template' => 'Atualizei a cor principal do tema para {{cor}}.',
                'hits'           => 0,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'recipe_type'    => 'theme',
                'name'           => 'mudar-fonte-titulo',
                'description'    => 'Alterar a fonte do título do tema',
                'prompt_pattern' => 'mudar a fonte do título para {fonte}',
                'code_templates' => json_encode(['fonts' => ['heading' => '{{fonte}}']]),
                'reply_template' => 'Atualizei a fonte dos títulos do tema para {{fonte}}.',
                'hits'           => 0,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('studio_ai_recipes');
    }
};
