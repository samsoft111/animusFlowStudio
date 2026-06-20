<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('studio_ai_command_cache', function (Blueprint $table) {
            $table->id();
            $table->string('prompt_hash', 64);
            $table->text('prompt');
            $table->string('context_type', 30); // 'theme', 'plugin'
            $table->text('reply')->nullable();
            $table->json('updates')->nullable();
            $table->json('build')->nullable();
            $table->integer('hits')->default(0);
            $table->timestamps();

            $table->index(['context_type', 'prompt_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('studio_ai_command_cache');
    }
};
