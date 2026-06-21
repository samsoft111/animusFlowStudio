<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Persiste o histórico do Chat IA (mensagens + cartões de construção) por tema,
     * para que ao reentrar no editor a conversa e as tarefas feitas não se percam.
     */
    public function up(): void
    {
        Schema::table('studio_themes', function (Blueprint $table) {
            $table->json('chat_history')->nullable()->after('custom_js')
                ->comment('Histórico do Chat IA do editor (mensagens + cartões de build)');
        });
    }

    public function down(): void
    {
        Schema::table('studio_themes', function (Blueprint $table) {
            $table->dropColumn('chat_history');
        });
    }
};
