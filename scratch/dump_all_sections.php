<?php

$aerospaceSkillPath = __DIR__ . '/../skills/themes/aerospace_theme_skill.md';
$content = file_get_contents($aerospaceSkillPath);

if (preg_match('/```json_updates\s*([\s\S]*?)```/m', $content, $matches)) {
    $json = json_decode(trim($matches[1]), true);
    if (isset($json['sections']['cta'])) {
        echo "=== CTA HTML ===\n";
        echo $json['sections']['cta'] . "\n";
    }
}
