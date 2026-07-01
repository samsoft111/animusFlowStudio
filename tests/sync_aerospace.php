<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\StudioTheme;

$theme = StudioTheme::where('label', 'AeroSpace')->first();
if (!$theme) {
    echo "Theme 'AeroSpace' not found!\n";
    exit(1);
}

$basePath = 'c:/Users/samso/AntigravityWorkspace/animusFlow/core/resources/views/theme/aerospace';
$filesToSync = [
    'hero'   => "$basePath/sections/hero.blade.php",
    'layout' => "$basePath/layout.blade.php",
];

$sections = $theme->sections ?? [];
foreach ($filesToSync as $key => $path) {
    if (!file_exists($path)) {
        echo "WARNING: $path not found, skipping.\n";
        continue;
    }
    $sections[$key] = file_get_contents($path);
    echo "Synced section '$key'\n";
}
$theme->sections = $sections;
$theme->save();
echo "AeroSpace theme DB updated.\n";
