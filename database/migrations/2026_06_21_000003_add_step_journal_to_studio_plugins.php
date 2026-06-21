<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Schema "espelho" do processo para plugins: por cada passo do progresso
     * guarda estado, origem (chat/manual/build), data e um histórico de
     * alterações com snapshot do valor anterior de cada campo (permite
     * reverter um passo específico).
     */
    public function up(): void
    {
        Schema::table('studio_plugins', function (Blueprint $table) {
            $table->json('step_journal')->nullable()->after('readme')
                ->comment('Espelho por passo para plugins: estado + origem + histórico + snapshots para revert');
        });
    }

    public function down(): void
    {
        Schema::table('studio_plugins', function (Blueprint $table) {
            $table->dropColumn('step_journal');
        });
    }
};
