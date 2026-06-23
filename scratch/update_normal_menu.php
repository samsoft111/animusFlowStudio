<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$skillPath = __DIR__ . '/../skills/themes/aerospace_theme_skill.md';

if (!file_exists($skillPath)) {
    echo "Skill file not found!\n";
    exit(1);
}

$skillContent = file_get_contents($skillPath);

// The exact string inside the JSON updates block of the markdown file:
$target = '<nav class=\"horizontal-links\">
        <ul>
          @foreach($nav_links ?? [] as $link)
            <li>
              <a href=\"{{ $link[\'url\'] }}\" 
                 class=\"@if(request()->path() === ltrim($link[\'url\'], \'/\')) active @endif\" 
                 onmouseenter=\"playHoverChirp()\">
                {{ $link[\'label\'] }}
              </a>
            </li>
          @endforeach
        </ul>
      </nav>';

// We'll replace it with the nested loop version, escaped for JSON:
$replacement = '<nav class=\"horizontal-links\">
        <ul>
          @foreach($nav_links ?? [] as $link)
            @php
              $children = $link[\'children\'] ?? [];
            @endphp
            @if(!empty($children))
              <li class=\"has-dropdown\">
                <a href=\"{{ $link[\'url\'] }}\" onmouseenter=\"playHoverChirp()\">{{ $link[\'label\'] }} ▾</a>
                <ul class=\"normal-dropdown\">
                  @foreach($children as $child)
                    <li><a href=\"{{ $child[\'url\'] }}\" onmouseenter=\"playHoverChirp()\">{{ $child[\'label\'] }}</a></li>
                  @endforeach
                </ul>
              </li>
            @else
              <li>
                <a href=\"{{ $link[\'url\'] }}\" 
                   class=\"@if(request()->path() === ltrim($link[\'url\'], \'/\')) active @endif\" 
                   onmouseenter=\"playHoverChirp()\">
                  {{ $link[\'label\'] }}
                </a>
              </li>
            @endif
          @endforeach
        </ul>
      </nav>';

if (str_contains($skillContent, $target)) {
    $skillContent = str_replace($target, $replacement, $skillContent);
    file_put_contents($skillPath, $skillContent);
    echo "Updated skill file successfully.\n";
} else {
    // Try without escaping of single quotes
    $targetNoSingleSlash = str_replace("\\'", "'", $target);
    $replacementNoSingleSlash = str_replace("\\'", "'", $replacement);
    
    if (str_contains($skillContent, $targetNoSingleSlash)) {
        $skillContent = str_replace($targetNoSingleSlash, $replacementNoSingleSlash, $skillContent);
        file_put_contents($skillPath, $skillContent);
        echo "Updated skill file successfully (without single quote escape in match).\n";
    } else {
        echo "Skill file target pattern not found.\n";
    }
}
