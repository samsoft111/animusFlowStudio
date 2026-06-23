<?php

$aerospaceSkillPath = __DIR__ . '/../skills/themes/aerospace_theme_skill.md';

if (!file_exists($aerospaceSkillPath)) {
    echo "Skill file not found.\n";
    exit(1);
}

$content = file_get_contents($aerospaceSkillPath);

// Extract the json updates block
if (preg_match('/```json_updates\s*([\s\S]*?)```/m', $content, $matches)) {
    $json = json_decode(trim($matches[1]), true);
    if (!$json) {
        echo "Failed to parse JSON updates.\n";
        exit(1);
    }
    
    echo "=== THEME PROPERTIES ===\n";
    echo "Label: " . ($json['label'] ?? 'not set') . "\n";
    echo "Version: " . ($json['version'] ?? 'not set') . "\n\n";

    // 1. Analyze layout.blade.php
    if (isset($json['layout'])) {
        echo "=== LAYOUT.BLADE.PHP ANALYSIS ===\n";
        $layout = $json['layout'];
        
        // Search for title, meta, og tags
        $hasTitle = str_contains($layout, '<title>') || str_contains($layout, '@yield(\'title\'');
        $hasMetaDesc = str_contains($layout, 'name="description"') || str_contains($layout, 'name=\"description\"');
        $hasOgTitle = str_contains($layout, 'og:title');
        $hasOgDesc = str_contains($layout, 'og:description');
        $hasOgImage = str_contains($layout, 'og:image');
        
        echo "SEO Tags:\n";
        echo "  - Has <title>: " . ($hasTitle ? 'YES' : 'NO') . "\n";
        echo "  - Has Meta Description: " . ($hasMetaDesc ? 'YES' : 'NO') . "\n";
        echo "  - Has OG Title: " . ($hasOgTitle ? 'YES' : 'NO') . "\n";
        echo "  - Has OG Description: " . ($hasOgDesc ? 'YES' : 'NO') . "\n";
        echo "  - Has OG Image: " . ($hasOgImage ? 'YES' : 'NO') . "\n\n";

        // Security check
        $hasPluginHtml = str_contains($layout, 'plugin_html');
        $hasCsrf = str_contains($layout, 'csrf_token') || str_contains($layout, '@csrf');
        $hasUnescaped = preg_match_all('/{!!\s*(.*?)\s*!!}/', $layout, $unescapedMatches);
        
        echo "Security features:\n";
        echo "  - Renders plugin_html: " . ($hasPluginHtml ? 'YES' : 'NO') . "\n";
        echo "  - Unescaped print tags ({!! ... !!}):\n";
        if ($hasUnescaped) {
            foreach (array_unique($unescapedMatches[0]) as $match) {
                echo "    * " . $match . "\n";
            }
        } else {
            echo "    * None\n";
        }
        echo "\n";
        
        // Show layout structure
        echo "First 500 chars of layout:\n" . substr($layout, 0, 500) . "\n\n";
    }

    // 2. Analyze Sections
    if (isset($json['sections'])) {
        echo "=== SECTIONS ANALYSIS ===\n";
        foreach ($json['sections'] as $secName => $secHtml) {
            echo "Section: {$secName}\n";
            // Check for forms and CSRF
            $hasForm = str_contains($secHtml, '<form');
            $hasCsrf = str_contains($secHtml, '@csrf');
            $hasAction = str_contains($secHtml, 'action=');
            
            echo "  - Contains Form: " . ($hasForm ? 'YES' : 'NO') . "\n";
            if ($hasForm) {
                echo "  - Contains CSRF token: " . ($hasCsrf ? 'YES' : 'NO') . "\n";
                echo "  - Form action defined: " . ($hasAction ? 'YES' : 'NO') . "\n";
            }
            
            // Unescaped matches in sections
            $hasSecUnescaped = preg_match_all('/{!!\s*(.*?)\s*!!}/', $secHtml, $secUnescapedMatches);
            if ($hasSecUnescaped) {
                echo "  - Unescaped print tags ({!! ... !!}):\n";
                foreach (array_unique($secUnescapedMatches[0]) as $match) {
                    echo "    * " . $match . "\n";
                }
            }
        }
    }

} else {
    echo "No json_updates block found in skill file.\n";
}
