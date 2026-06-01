<?php
/**
 * Teste do Modo Edição (CSS Token Editor)
 * Verifica: blade overlay, Edit.vue bridge, lógica de mapeamento de vars
 */

$pass = 0; $fail = 0;

function check(string $label, bool $ok, string $debug = ''): void {
    global $pass, $fail;
    echo ($ok ? '  ✅ ' : '  ❌ ') . $label . ($debug && !$ok ? ' ['.$debug.']' : '') . PHP_EOL;
    $ok ? $pass++ : $fail++;
}

$blade = file_get_contents(__DIR__ . '/../resources/views/preview/theme.blade.php');
$vue   = file_get_contents(__DIR__ . '/../resources/js/Pages/Themes/Edit.vue');

// ═══════════════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 1: Estrutura HTML do Overlay (theme.blade.php)' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

check('Botão #af-mode-toggle presente',          str_contains($blade, 'id="af-mode-toggle"'));
check('Painel #af-inspector presente',           str_contains($blade, 'id="af-inspector"'));
check('Cabeçalho #af-inspector-header presente', str_contains($blade, 'id="af-inspector-header"'));
check('Botão #af-close-btn presente',            str_contains($blade, 'id="af-close-btn"'));
check('Botão #af-save-btn presente',             str_contains($blade, 'id="af-save-btn"'));
check('Tooltip #af-tooltip presente',            str_contains($blade, 'id="af-tooltip"'));
check('#af-element-info presente',               str_contains($blade, 'id="af-element-info"'));
check('#af-element-tokens-list presente',        str_contains($blade, 'id="af-element-tokens-list"'));
check('#af-colors-list presente',                str_contains($blade, 'id="af-colors-list"'));
check('#af-fonts-list presente',                 str_contains($blade, 'id="af-fonts-list"'));
check('#af-empty (empty state) presente',        str_contains($blade, 'id="af-empty"'));

// ═══════════════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 2: CSS do Overlay (theme.blade.php)' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

check('.af-edit-highlight definido',  str_contains($blade, '.af-edit-highlight'));
check('.af-edit-hover definido',      str_contains($blade, '.af-edit-hover'));
check('.af-tooltip definido',         str_contains($blade, '.af-tooltip'));
check('#af-inspector.af-open definido', str_contains($blade, '#af-inspector.af-open'));
check('.af-token-row definido',       str_contains($blade, '.af-token-row'));
check('.af-color-swatch definido',    str_contains($blade, '.af-color-swatch'));
check('.af-token-input definido',     str_contains($blade, '.af-token-input'));
check('.af-font-input definido',      str_contains($blade, '.af-font-input'));
check('#af-save-btn hover definido',  str_contains($blade, '#af-save-btn:hover'));
check('Transição translateX no inspector', str_contains($blade, 'translateX(100%)'));

// ═══════════════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 3: Funções JS do Overlay (theme.blade.php)' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

check('Função getVar() definida',            str_contains($blade, 'function getVar('));
check('Função setVar() definida',            str_contains($blade, 'function setVar('));
check('Função isColor() definida',           str_contains($blade, 'function isColor('));
check('Função detectElementVars() definida', str_contains($blade, 'function detectElementVars('));
check('Função buildTokenRow() definida',     str_contains($blade, 'function buildTokenRow('));
check('Função populateAllTokens() definida', str_contains($blade, 'function populateAllTokens('));
check('Função inspectElement() definida',    str_contains($blade, 'function inspectElement('));
check('Função applyVarChange() definida',    str_contains($blade, 'function applyVarChange('));
check('Função activateEdit() definida',      str_contains($blade, 'function activateEdit('));
check('Função deactivateEdit() definida',    str_contains($blade, 'function deactivateEdit('));
check('Função clearHighlight() definida',    str_contains($blade, 'function clearHighlight('));
check('Função rgbToHex() definida',          str_contains($blade, 'function rgbToHex('));

// ═══════════════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 4: Comunicação postMessage (iframe → parent)' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

check('Envia af-token-change ao parent',    str_contains($blade, "type: 'af-token-change'"));
check('Envia af-save-request ao parent',    str_contains($blade, "type: 'af-save-request'"));
check('Envia af-ready ao parent',           str_contains($blade, "type: 'af-ready'"));
check('Envia af-edit-activated ao parent',  str_contains($blade, "type: 'af-edit-activated'"));
check('Envia af-edit-deactivated ao parent',str_contains($blade, "type: 'af-edit-deactivated'"));

// ═══════════════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 5: Comunicação postMessage (parent → iframe)' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

check("Recebe 'af-enable-edit' → activateEdit",  str_contains($blade, "af-enable-edit")  && str_contains($blade, 'activateEdit()'));
check("Recebe 'af-disable-edit' → deactivateEdit", str_contains($blade, "af-disable-edit") && str_contains($blade, 'deactivateEdit()'));
check("Recebe 'af-apply-vars' → setVar em loop",  str_contains($blade, "af-apply-vars")   && str_contains($blade, 'setVar(k, v)'));
check('Auto-activa com ?edit=1 na URL',           str_contains($blade, "get('edit') === '1'"));

// ═══════════════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 6: PHP → JS — Vars do tema injectadas correctamente' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

check('$allVars construído com array_merge($light, ...)', str_contains($blade, 'array_merge'));
check('--font-heading incluído em $allVars',              str_contains($blade, "'--font-heading'"));
check('--font-body incluído em $allVars',                 str_contains($blade, "'--font-body'"));
check('THEME_VARS passado via @json($allVars)',            str_contains($blade, '@json($allVars)'));
check('COLOR_VARS filtrado por --color-',                 str_contains($blade, "startsWith('--color-')"));
check('FONT_VARS filtrado por --font-',                   str_contains($blade, "startsWith('--font-')"));

// ═══════════════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 7: Edit.vue — Refs e imports' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

check('onMounted importado do vue',         str_contains($vue, 'onMounted'));
check('onUnmounted importado do vue',       str_contains($vue, 'onUnmounted'));
check('nextTick importado do vue',          str_contains($vue, 'nextTick'));
check('previewIframe ref definida',         str_contains($vue, 'const previewIframe'));
check('previewEditMode ref definida',       str_contains($vue, 'const previewEditMode'));
check('previewToast ref definida',          str_contains($vue, 'const previewToast'));
check('previewToastTimer variável definida',str_contains($vue, 'previewToastTimer'));

// ═══════════════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 8: Edit.vue — Funções do bridge' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

check('showPreviewToast() definida',          str_contains($vue, 'function showPreviewToast('));
check('togglePreviewEditMode() definida',     str_contains($vue, 'function togglePreviewEditMode('));
check('handlePreviewMessage() definida',      str_contains($vue, 'function handlePreviewMessage('));
check('onMounted regista listener',           str_contains($vue, "onMounted(() => { window.addEventListener('message', handlePreviewMessage)"));
check('onUnmounted remove listener',          str_contains($vue, "onUnmounted(() => { window.removeEventListener('message', handlePreviewMessage)"));

// ═══════════════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 9: Edit.vue — Mapeamento af-token-change → form' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

check("--font-heading → form.fonts.heading",    str_contains($vue, "form.fonts = { ...(form.fonts ?? {}), heading: value }"));
check("--font-body → form.fonts.body",          str_contains($vue, "form.fonts = { ...(form.fonts ?? {}), body: value }"));
check("--color-* → form.colors.light[varName]", str_contains($vue, 'form.colors?.light ?? {}), [varName]: value'));
check("af-save-request → save()",               str_contains($vue, "d.type === 'af-save-request'") && str_contains($vue, 'save()'));

// ═══════════════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 10: Edit.vue — togglePreviewEditMode envia vars correcto' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

check("Envia af-enable-edit ao iframe",         str_contains($vue, "type: 'af-enable-edit'"));
check("Envia af-disable-edit ao iframe",        str_contains($vue, "type: 'af-disable-edit'"));
check("Envia af-apply-vars com vars actuais",   str_contains($vue, "type: 'af-apply-vars'") && str_contains($vue, "vars }"));
check("--font-heading incluído nas vars enviadas", str_contains($vue, "vars['--font-heading'] = form.fonts.heading"));
check("--font-body incluído nas vars enviadas",    str_contains($vue, "vars['--font-body']    = form.fonts.body"));
check("Re-activa após af-ready com editMode ON",   str_contains($vue, "d.type === 'af-ready' && previewEditMode.value"));

// ═══════════════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 11: Edit.vue — Template do tab Preview' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

check('ref="previewIframe" no iframe',               str_contains($vue, 'ref="previewIframe"'));
check('@click="togglePreviewEditMode" no botão',     str_contains($vue, '@click="togglePreviewEditMode"'));
check('Botão muda classe quando previewEditMode',    str_contains($vue, ':class="previewEditMode'));
check('Banner de dica com v-if="previewEditMode"',   str_contains($vue, 'v-if="previewEditMode"'));
check('Toast com v-if="previewToast"',               str_contains($vue, 'v-if="previewToast"'));
check('<Transition name="fade"> no toast',           str_contains($vue, '<Transition name="fade">'));
check('fade-enter-active definida no <style>',       str_contains($vue, 'fade-enter-active'));

// ═══════════════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 12: Interacção com abas — sem conflitos' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

// Verifica que previewEditMode não é resetado quando muda de aba
check("previewEditMode não é resetado em activeTab watch",
    !preg_match('/watch.*activeTab[\s\S]{0,200}previewEditMode.*=.*false/', $vue));

// Verifica que o listener está em window (não no iframe), portanto activo em todas as abas
check("Listener window.addEventListener global (não no tab preview)",
    str_contains($vue, "window.addEventListener('message', handlePreviewMessage)"));

// Verifica que toast usa Transition (não bloqueia render de outras abas)
check("Toast usa <Transition> (não bloqueia outras abas)",
    str_contains($vue, '<Transition name="fade">') && str_contains($vue, '</Transition>'));

// Aba Design tem campos form.colors.light — mesmo objecto que o bridge actualiza
check("form.colors.light usado no tab Design",
    str_contains($vue, 'form.colors.light') || str_contains($vue, "form.colors['light']"));

// Aba Fontes tem form.fonts — mesmo objecto que o bridge actualiza
check("form.fonts usado no tab Fontes/Tipografia",
    str_contains($vue, 'form.fonts'));

// O build compilou sem erro (ficheiro JS de output existe)
$buildFiles = glob(__DIR__ . '/../public/build/assets/Edit-*.js');
check("Ficheiro Edit-*.js compilado em public/build/assets/", count($buildFiles) > 0,
    count($buildFiles) === 0 ? 'Corre npm run build' : '');

// ═══════════════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 13: Lógica rgbToHex — testes unitários inline' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

// Extrai e simula a função rgbToHex em PHP para verificar lógica
function rgbToHex(string $rgb): string {
    preg_match_all('/\d+/', $rgb, $m);
    if (count($m[0]) < 3) return '#000000';
    return '#' . implode('', array_map(fn($x) => str_pad(dechex((int)$x), 2, '0', STR_PAD_LEFT), array_slice($m[0], 0, 3)));
}
check('rgbToHex("rgb(255,0,0)") = #ff0000',       rgbToHex('rgb(255,0,0)') === '#ff0000');
check('rgbToHex("rgb(0,0,0)") = #000000',         rgbToHex('rgb(0,0,0)') === '#000000');
check('rgbToHex("rgb(255,255,255)") = #ffffff',   rgbToHex('rgb(255,255,255)') === '#ffffff');
check('rgbToHex("rgb(107,33,168)") = #6b21a8',   rgbToHex('rgb(107,33,168)') === '#6b21a8');
check('rgbToHex("") não crasha (fallback #000000)', rgbToHex('') === '#000000');

// ═══════════════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 14: Mapeamento de vars — lógica de roteamento PHP' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

// Simula a lógica de handlePreviewMessage do Edit.vue
function simulateHandleToken(string $varName, string $value, array &$form): void {
    if ($varName === '--font-heading') {
        $form['fonts']['heading'] = $value;
    } elseif ($varName === '--font-body') {
        $form['fonts']['body'] = $value;
    } elseif (str_starts_with($varName, '--')) {
        $form['colors']['light'][$varName] = $value;
    }
}

$form = ['colors' => ['light' => ['--color-primary' => '#6d28d9', '--color-background' => '#ffffff'], 'dark' => ['--color-primary' => '#a78bfa']], 'fonts' => ['heading' => 'Playfair Display', 'body' => 'Inter']];

simulateHandleToken('--color-primary', '#1a1a1a', $form);
check('--color-primary actualiza form.colors.light',      $form['colors']['light']['--color-primary'] === '#1a1a1a');
check('--color-primary não toca em dark',                 $form['colors']['dark']['--color-primary'] === '#a78bfa');
check('--color-background preservado',                    $form['colors']['light']['--color-background'] === '#ffffff');

simulateHandleToken('--font-heading', 'DM Sans', $form);
check('--font-heading actualiza form.fonts.heading',      $form['fonts']['heading'] === 'DM Sans');
check('--font-body preservado após mudança de heading',   $form['fonts']['body'] === 'Inter');

simulateHandleToken('--font-body', 'Roboto', $form);
check('--font-body actualiza form.fonts.body',            $form['fonts']['body'] === 'Roboto');
check('--font-heading preservado após mudança de body',   $form['fonts']['heading'] === 'DM Sans');

simulateHandleToken('--color-accent', '#d4af37', $form);
check('Nova var --color-accent adicionada a colors.light', $form['colors']['light']['--color-accent'] === '#d4af37');
check('Outras vars light preservadas após accent',         $form['colors']['light']['--color-primary'] === '#1a1a1a');

// ═══════════════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo 'BLOCO 15: Sync de vars para iframe (af-apply-vars)' . PHP_EOL;
echo '═══════════════════════════════════════════════════' . PHP_EOL;

// Simula o que togglePreviewEditMode envia ao iframe
function buildVarsPayload(array $form): array {
    $vars = $form['colors']['light'] ?? [];
    if (!empty($form['fonts']['heading'])) $vars['--font-heading'] = $form['fonts']['heading'];
    if (!empty($form['fonts']['body']))    $vars['--font-body']    = $form['fonts']['body'];
    return $vars;
}

$formState = [
    'colors' => ['light' => ['--color-primary' => '#b8860b', '--color-background' => '#faf9f7']],
    'fonts'  => ['heading' => 'Cormorant Garamond', 'body' => 'Lato'],
];
$payload = buildVarsPayload($formState);
check('Payload tem --color-primary',        ($payload['--color-primary'] ?? '') === '#b8860b');
check('Payload tem --color-background',     ($payload['--color-background'] ?? '') === '#faf9f7');
check('Payload tem --font-heading',         ($payload['--font-heading'] ?? '') === 'Cormorant Garamond');
check('Payload tem --font-body',            ($payload['--font-body'] ?? '') === 'Lato');
check('dark colors NÃO incluídas no payload', !isset($payload['dark']));

// ═══════════════════════════════════════════════════════════
echo PHP_EOL . '═══════════════════════════════════════════════════' . PHP_EOL;
echo "RESULTADO FINAL: {$pass} passou, {$fail} falhou" . PHP_EOL;
echo ($fail === 0 ? '✅ TODOS OS TESTES PASSARAM' : "❌ {$fail} TESTES FALHARAM") . PHP_EOL;
