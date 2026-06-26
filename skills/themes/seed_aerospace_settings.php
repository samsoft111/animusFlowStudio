<?php

declare(strict_types=1);

/**
 * Seed do schema de "Definições do site" do tema AeroSpace — AnimusFlowStudio
 *
 * Popula StudioTheme.theme_settings com a lista declarativa de campos que o
 * criador poderá configurar no AnimusFlow. A construção do schema vive em
 * App\Support\ThemeSettingsRecommender (fonte única, partilhada com o botão
 * "Repor definições recomendadas" do editor).
 *
 * Idempotente — corre as vezes que precisares:
 *   php skills/themes/seed_aerospace_settings.php
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\StudioTheme;
use App\Support\ThemeSettingsRecommender;

$theme = StudioTheme::where('label', 'AeroSpace')->first();
if (!$theme) {
    fwrite(STDERR, "❌ Tema 'AeroSpace' não existe na BD.\n");
    exit(2);
}

$settings = ThemeSettingsRecommender::recommend($theme);
$theme->theme_settings = $settings;
$theme->save();

// ─── Resumo ──────────────────────────────────────────────────────────────────
$byGroup = [];
foreach ($settings as $s) {
    $byGroup[$s['group']] = ($byGroup[$s['group']] ?? 0) + 1;
}
echo '✅ theme_settings semeado no AeroSpace — ' . count($settings) . " campos.\n";
foreach ($byGroup as $g => $n) {
    printf("   · %-16s %d campo(s)\n", $g, $n);
}
exit(0);
