<?php

$aerospaceSkillPath = __DIR__ . '/../skills/themes/aerospace_theme_skill.md';
$content = file_get_contents($aerospaceSkillPath);

if (preg_match('/```json_updates\s*([\s\S]*?)```/m', $content, $matches)) {
    $json = json_decode(trim($matches[1]), true);
    
    if (isset($json['sections'])) {
        foreach ($json['sections'] as $secName => $secHtml) {
            if (preg_match_all('/<img[\s\S]*?>/', $secHtml, $imgMatches)) {
                echo "=== Section: {$secName} ===\n";
                foreach ($imgMatches[0] as $img) {
                    echo "  * " . htmlspecialchars($img) . "\n";
                }
                echo "\n";
            }
        }
    }
}
