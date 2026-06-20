<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('studio_ai_recipes', function (Blueprint $table) {
            $table->unsignedSmallInteger('confidence_score')->default(100)->after('hits');
            $table->boolean('is_enabled')->default(true)->after('confidence_score');
            $table->timestamp('last_used_at')->nullable()->after('is_enabled');
            $table->unsignedBigInteger('tokens_saved')->default(0)->after('last_used_at');
            $table->json('placeholder_types')->nullable()->after('tokens_saved');
            $table->unsignedTinyInteger('fuzzy_threshold')->default(80)->after('placeholder_types');
        });

        // Recipes seeded in Fase 1 get max confidence
        \DB::table('studio_ai_recipes')->update(['confidence_score' => 100, 'is_enabled' => true]);
    }

    public function down(): void
    {
        Schema::table('studio_ai_recipes', function (Blueprint $table) {
            $table->dropColumn([
                'confidence_score', 'is_enabled', 'last_used_at',
                'tokens_saved', 'placeholder_types', 'fuzzy_threshold',
            ]);
        });
    }
};
