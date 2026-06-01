<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('studio_plugin_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_plugin_id')->constrained('studio_plugins')->cascadeOnDelete();
            $table->string('version', 30);                   // semver: 1.0.0, 1.2.3-beta
            $table->string('label', 255)->nullable();        // human label at snapshot time
            $table->text('changelog')->nullable();           // what changed in this version
            $table->json('snapshot');                        // full plugin state
            $table->boolean('is_published')->default(false); // was this version published to marketplace?
            $table->string('published_uuid', 100)->nullable();// marketplace UUID at publish time
            $table->string('created_by', 255)->nullable();   // author email / name
            $table->timestamps();

            $table->unique(['studio_plugin_id', 'version']);
            $table->index('studio_plugin_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('studio_plugin_versions');
    }
};
