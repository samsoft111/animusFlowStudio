<?php

$aerospaceSkillPath = __DIR__ . '/../skills/themes/aerospace_theme_skill.md';
$in9vadorSkillPath = __DIR__ . '/../skills/themes/in9vador_theme_skill.md';

// ── 1. Update aerospace_theme_skill.md ──
if (file_exists($aerospaceSkillPath)) {
    $content = file_get_contents($aerospaceSkillPath);
    
    $pos = strpos($content, '<nav class=\"horizontal-links\">');
    if ($pos !== false) {
        $endPos = strpos($content, '</nav>', $pos);
        if ($endPos !== false) {
            $exactTarget = substr($content, $pos, $endPos + strlen('</nav>') - $pos);
            
            $replacement = '<nav class=\"horizontal-links\">\n        <ul>\n          @foreach($nav_links ?? [] as $link)\n            @php\n              $children = $link[\'children\'] ?? [];\n              $isActive = request()-\u003eis(ltrim($link[\'url\'], \'/\')) || (request()-\u003eis(\'/\') \u0026\u0026 $link[\'url\'] === \'/\');\n            @endphp\n            <li>\n              <a href=\"{{ $link[\'url\'] }}\" class=\"{{ $isActive ? \'active\' : \'\' }}\" onmouseenter=\"playHoverChirp()\" @if(($link[\'target\'] ?? \'_self\') === \'_blank\') target=\"_blank\" @endif>\n                {{ $link[\'label\'] }} @if(!empty($children)) ▾ @endif\n              </a>\n              @if(!empty($children))\n                <ul class=\"normal-dropdown\">\n                  @foreach($children as $child)\n                    <li>\n                      <a href=\"{{ $child[\'url\'] }}\" onmouseenter=\"playHoverChirp()\" @if(($child[\'target\'] ?? \'_self\') === \'_blank\') target=\"_blank\" @endif>\n                        {{ $child[\'label\'] }}\n                      </a>\n                    </li>\n                  @endforeach\n                </ul>\n              @endif\n            </li>\n          @endforeach\n        </ul>\n      </nav>';
            
            $content = str_replace($exactTarget, $replacement, $content);
            file_put_contents($aerospaceSkillPath, $content);
            echo "Updated aerospace_theme_skill.md successfully.\n";
        } else {
            echo "AEROSPACE: </nav> not found after start pos.\n";
        }
    } else {
        echo "AEROSPACE: start tag <nav class=\"horizontal-links\"> not found.\n";
    }
} else {
    echo "aerospace_theme_skill.md not found.\n";
}

// ── 2. Update in9vador_theme_skill.md ──
if (file_exists($in9vadorSkillPath)) {
    $content = file_get_contents($in9vadorSkillPath);
    
    $pos = strpos($content, '<nav class=\"horizontal-links\">');
    if ($pos !== false) {
        $endPos = strpos($content, '</nav>', $pos);
        if ($endPos !== false) {
            $exactTarget = substr($content, $pos, $endPos + strlen('</nav>') - $pos);
            
            $replacement = '<nav class=\"horizontal-links\">\n        <ul>\n          @foreach($nav_links ?? [] as $link)\n            @php\n              $children = $link[\'children\'] ?? [];\n              $isActive = request()-\u003eis(ltrim($link[\'url\'], \'/\')) || (request()-\u003eis(\'/\') \u0026\u0026 $link[\'url\'] === \'/\');\n            @endphp\n            <li>\n              <a href=\"{{ $link[\'url\'] }}\" class=\"{{ $isActive ? \'active\' : \'\' }}\" @if(($link[\'target\'] ?? \'_self\') === \'_blank\') target=\"_blank\" @endif>\n                {{ $link[\'label\'] }} @if(!empty($children)) ▾ @endif\n              </a>\n              @if(!empty($children))\n                <ul class=\"normal-dropdown\">\n                  @foreach($children as $child)\n                    <li>\n                      <a href=\"{{ $child[\'url\'] }}\" @if(($child[\'target\'] ?? \'_self\') === \'_blank\') target=\"_blank\" @endif>\n                        {{ $child[\'label\'] }}\n                      </a>\n                    </li>\n                  @endforeach\n                </ul>\n              @endif\n            </li>\n          @endforeach\n        </ul>\n      </nav>';
            
            $content = str_replace($exactTarget, $replacement, $content);
            file_put_contents($in9vadorSkillPath, $content);
            echo "Updated in9vador_theme_skill.md successfully.\n";
        } else {
            echo "IN9VADOR: </nav> not found after start pos.\n";
        }
    } else {
        echo "IN9VADOR: start tag <nav class=\"horizontal-links\"> not found.\n";
    }
} else {
    echo "in9vador_theme_skill.md not found.\n";
}
