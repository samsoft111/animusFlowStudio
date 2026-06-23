<?php

$aerospaceSkillPath = __DIR__ . '/../skills/themes/aerospace_theme_skill.md';
$content = file_get_contents($aerospaceSkillPath);

if (preg_match('/```json_updates\s*([\s\S]*?)```/m', $content, $matches)) {
    $json = json_decode(trim($matches[1]), true);
    
    if (isset($json['sections']['hero'])) {
        echo "=== HERO FORM HTML ===\n";
        if (preg_match('/<form[\s\S]*?<\/form>/', $json['sections']['hero'], $formMatches)) {
            echo $formMatches[0] . "\n\n";
        } else {
            echo "No form found in hero.\n\n";
        }
    }
    
    if (isset($json['sections']['cta'])) {
        echo "=== CTA FORM HTML ===\n";
        if (preg_match('/<form[\s\S]*?<\/form>/', $json['sections']['cta'], $formMatches)) {
            echo $formMatches[0] . "\n\n";
        } else {
            echo "No form found in cta.\n\n";
        }
    }
}
