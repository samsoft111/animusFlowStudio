<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$theme = \App\Models\StudioTheme::where('name', 'inov9dor')->first();
if ($theme) {
    $sections = $theme->sections ?? [];
    if (isset($sections['hero'])) {
        echo "Current hero: " . $sections['hero'] . "\n";
        $sections['hero'] = "<div>\n  <h1>" . htmlspecialchars($theme->label) . "</h1>\n  <p>Bem-vindo ao novo tema.</p>\n</div>";
        $theme->sections = $sections;
        $theme->save();
        echo "Updated hero section to valid HTML for theme 'inov9dor'.\n";
    }
} else {
    echo "Theme inov9dor not found.\n";
}
