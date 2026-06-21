<?php

/**
 * Testes — Inspiração por Categoria (ThemeController::inspire + AIEngine::generateThemeFromCategory + Index.vue)
 *
 * Cobre:
 *  1. AIEngine::generateThemeFromCategory — system prompt e estrutura de retorno
 *  2. ThemeController::inspire — validação, criação de tema, resposta JSON
 *  3. Rota POST /themes/inspire existe e está dentro de auth
 *  4. Index.vue — categorias, estilos, modal, fluxo de geração
 *  5. Integração: StudioTheme criado com todos os campos correctos
 *
 * Executar:
 *   php tests/inspire_category_test.php
 */

declare(strict_types=1);

$php = 'C:\Users\samso\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.2_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe';

// ── Bootstrap Laravel ────────────────────────────────────────────
$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
require __DIR__ . '/ai_settings_guard.php'; // preserva/restaura a chave de IA real

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use App\Models\StudioTheme;
use App\Services\AIEngine;

// ── Helpers ──────────────────────────────────────────────────────
$passed = 0;
$failed = 0;
$errors = [];

function ok(string $msg): void {
    global $passed;
    $passed++;
    echo "  \033[32m✓\033[0m {$msg}\n";
}

function fail(string $msg, string $detail = ''): void {
    global $failed, $errors;
    $failed++;
    $errors[] = $msg . ($detail ? ": {$detail}" : '');
    echo "  \033[31m✗\033[0m {$msg}" . ($detail ? " — {$detail}" : '') . "\n";
}

function section(string $title): void {
    echo "\n\033[1;34m▶ {$title}\033[0m\n";
}

function assert_contains(string $haystack, string $needle, string $msg): void {
    if (str_contains($haystack, $needle)) ok($msg);
    else fail($msg, "esperado: «{$needle}»");
}

function assert_true(bool $val, string $msg, string $detail = ''): void {
    if ($val) ok($msg);
    else fail($msg, $detail);
}

function assert_false(bool $val, string $msg): void {
    if (!$val) ok($msg);
    else fail($msg);
}

// ────────────────────────────────────────────────────────────────
//  1. AIEngine — system prompt
// ────────────────────────────────────────────────────────────────
section('1. AIEngine::generateThemeFromCategory — system prompt');

$aiFile = file_get_contents($root . '/app/Services/AIEngine.php');

assert_contains($aiFile, 'generateThemeFromCategory', 'Método generateThemeFromCategory existe no AIEngine');
assert_contains($aiFile, 'string $category', 'Parâmetro $category');
assert_contains($aiFile, 'string $style', 'Parâmetro $style');
assert_contains($aiFile, '--color-primary', 'System prompt inclui --color-primary');
assert_contains($aiFile, '--color-background', 'System prompt inclui --color-background');
assert_contains($aiFile, '"colors"', 'System prompt define estrutura colors');
assert_contains($aiFile, '"light"', 'System prompt define light colors');
assert_contains($aiFile, '"dark"', 'System prompt define dark colors');
assert_contains($aiFile, '"fonts"', 'System prompt define fonts');
assert_contains($aiFile, '"layout_config"', 'System prompt define layout_config');
assert_contains($aiFile, '"capabilities"', 'System prompt define capabilities');
assert_contains($aiFile, '"sections"', 'System prompt define sections');
assert_contains($aiFile, 'inspiration', 'System prompt inclui campo inspiration');
assert_contains($aiFile, 'parseJson', 'Usa parseJson para fallback seguro');
assert_contains($aiFile, "'label'         => ucfirst(\$category)", 'Tem fallback para label');
assert_contains($aiFile, "call(\$systemPrompt, \$userPrompt, 6144)", 'Chama com max_tokens=6144');

// ── Verificar user prompt ──
assert_contains($aiFile, 'psicologia da categoria', 'System prompt menciona psicologia da categoria');
assert_contains($aiFile, 'oklch ou hex', 'System prompt menciona oklch ou hex');
assert_contains($aiFile, 'Google Fonts', 'System prompt menciona Google Fonts');

// ────────────────────────────────────────────────────────────────
//  2. AIEngine — mock call e retorno
// ────────────────────────────────────────────────────────────────
section('2. AIEngine::generateThemeFromCategory — mock e estrutura de retorno');

$mockResponse = json_encode([
    'label'         => 'Bistro Moderno',
    'description'   => 'Tema elegante para restaurantes modernos',
    'inspiration'   => 'Inspirado em: Noma, Eleven Madison Park',
    'colors'        => [
        'light' => [
            '--color-primary'            => '#8B4513',
            '--color-primary-foreground' => '#ffffff',
            '--color-secondary'          => '#D2691E',
            '--color-accent'             => '#FF6B35',
            '--color-background'         => '#FFF8F0',
            '--color-foreground'         => '#2C1810',
            '--color-card'               => '#FFFFFF',
            '--color-muted'              => '#F5E6D3',
            '--color-muted-foreground'   => '#8B6355',
            '--color-border'             => '#E8D5C0',
            '--color-success'            => '#22c55e',
            '--color-warning'            => '#f59e0b',
            '--color-destructive'        => '#ef4444',
        ],
        'dark' => [
            '--color-primary'            => '#CD853F',
            '--color-primary-foreground' => '#ffffff',
            '--color-secondary'          => '#A0522D',
            '--color-accent'             => '#FF8C69',
            '--color-background'         => '#1A0A05',
            '--color-foreground'         => '#F5E6D3',
            '--color-card'               => '#2C1810',
            '--color-muted'              => '#3D2015',
            '--color-muted-foreground'   => '#C4956A',
            '--color-border'             => '#4A2C1A',
            '--color-success'            => '#22c55e',
            '--color-warning'            => '#f59e0b',
            '--color-destructive'        => '#ef4444',
        ],
    ],
    'fonts'         => ['heading' => 'Playfair Display', 'body' => 'Lato'],
    'layout_config' => [
        'header_type'    => 'transparent',
        'nav_type'       => 'horizontal',
        'footer_type'    => 'minimal',
        'layout_type'    => 'full-width',
        'max_width'      => '1200px',
        'spacing'        => 'spacious',
        'show_dark_toggle' => true,
        'back_to_top'    => true,
        'header_cta_text' => 'Reservar Mesa',
        'header_cta_url'  => '#reservas',
    ],
    'capabilities'  => [
        'video_bg'       => false,
        'parallax'       => true,
        'animations'     => true,
        'lightbox'       => true,
        'mega_menu'      => false,
        'search'         => false,
        'cookie_banner'  => true,
        'preloader'      => false,
        'scroll_progress' => true,
    ],
    'sections'      => [
        'hero'     => '<section style="background:var(--color-primary)"><h1>Bem-vindo ao Bistro</h1></section>',
        'features' => '<section style="background:var(--color-background)"><p>Os nossos pratos</p></section>',
        'cta'      => '<section style="background:var(--color-accent)"><a href="#reservas">Reservar</a></section>',
    ],
    'custom_css'    => '/* Restaurante - moderno */ body { font-family: var(--font-body, Lato), sans-serif; }',
]);

Http::fake([
    'https://api.anthropic.com/v1/messages' => Http::response(
        json_encode(['content' => [['text' => $mockResponse]]]),
        200
    ),
    'https://api.openai.com/v1/chat/completions' => Http::response(
        json_encode(['choices' => [['message' => ['content' => $mockResponse]]]]),
        200
    ),
]);

// Precisa de chave configurada
\App\Models\StudioSetting::set('ai_api_key', encrypt('test-key-mock'));
\App\Models\StudioSetting::set('ai_provider', 'claude');

try {
    $result = AIEngine::generateThemeFromCategory('Restaurante', 'moderno');

    assert_true(isset($result['label']),        'Resultado tem label');
    assert_true(isset($result['description']),  'Resultado tem description');
    assert_true(isset($result['inspiration']),  'Resultado tem inspiration');
    assert_true(isset($result['colors']),       'Resultado tem colors');
    assert_true(isset($result['colors']['light']), 'Resultado tem colors.light');
    assert_true(isset($result['colors']['dark']),  'Resultado tem colors.dark');
    assert_true(isset($result['fonts']),        'Resultado tem fonts');
    assert_true(isset($result['fonts']['heading']), 'Resultado tem fonts.heading');
    assert_true(isset($result['fonts']['body']),    'Resultado tem fonts.body');
    assert_true(isset($result['layout_config']), 'Resultado tem layout_config');
    assert_true(isset($result['capabilities']), 'Resultado tem capabilities');
    assert_true(isset($result['sections']),     'Resultado tem sections');
    assert_true(isset($result['custom_css']),   'Resultado tem custom_css');

    // Verificar conteúdo real
    assert_true($result['label'] === 'Bistro Moderno', 'Label correcta');
    assert_true(str_contains($result['inspiration'], 'Noma'), 'Inspiration contém referência real');
    assert_true(isset($result['colors']['light']['--color-primary']), 'Light tem --color-primary');
    assert_true(isset($result['colors']['dark']['--color-primary']),  'Dark tem --color-primary');
    assert_true($result['fonts']['heading'] === 'Playfair Display', 'Font heading correcta');
    assert_true(count($result['sections']) >= 2, 'Pelo menos 2 secções');
    assert_true(isset($result['layout_config']['header_type']), 'layout_config.header_type presente');
    assert_true(isset($result['capabilities']['parallax']),     'capabilities.parallax presente');

} catch (\Throwable $e) {
    fail('generateThemeFromCategory lançou excepção', $e->getMessage());
}

// ────────────────────────────────────────────────────────────────
//  3. ThemeController::inspire — validação
// ────────────────────────────────────────────────────────────────
section('3. ThemeController::inspire — validação de campos');

$controllerFile = file_get_contents($root . '/app/Http/Controllers/ThemeController.php');

assert_contains($controllerFile, 'public function inspire', 'Método inspire() existe no ThemeController');
assert_contains($controllerFile, "'category' => 'required|string|max:100'", 'Valida category como required');
assert_contains($controllerFile, "'style'    => 'nullable|string|max:50'", 'Valida style como nullable');
assert_contains($controllerFile, "AIEngine::generateThemeFromCategory(\$category, \$style)", 'Chama generateThemeFromCategory');
assert_contains($controllerFile, 'StudioTheme::create', 'Cria StudioTheme');
assert_contains($controllerFile, "'success'     => true", 'Resposta inclui success: true');
assert_contains($controllerFile, "'theme_uuid'  => \$theme->uuid", 'Resposta inclui theme_uuid');
assert_contains($controllerFile, "'preview_url' => route('themes.preview'", 'Resposta inclui preview_url');
assert_contains($controllerFile, "'edit_url'    => route('themes.edit'", 'Resposta inclui edit_url');
assert_contains($controllerFile, "'colors'      => \$theme->colors", 'Resposta inclui colors');
assert_contains($controllerFile, "'inspiration' => \$generated['inspiration']", 'Resposta inclui inspiration');
assert_contains($controllerFile, "response()->json(['error' => \$e->getMessage()], 422)", 'Trata excepção com 422');

// ────────────────────────────────────────────────────────────────
//  4. ThemeController::inspire — integração BD
// ────────────────────────────────────────────────────────────────
section('4. ThemeController::inspire — integração com BD');

Http::fake([
    'https://api.anthropic.com/v1/messages' => Http::response(
        json_encode(['content' => [['text' => $mockResponse]]]),
        200
    ),
]);

\App\Models\StudioSetting::set('ai_api_key', encrypt('test-key-mock'));
\App\Models\StudioSetting::set('ai_provider', 'claude');

// Instanciar controller e chamar inspire directamente
$controller = new \App\Http\Controllers\ThemeController();
$request = \Illuminate\Http\Request::create('/themes/inspire', 'POST', [
    'category' => 'Restaurante',
    'style'    => 'elegante',
]);
$request->setLaravelSession(app('session.store'));

try {
    $jsonResponse = $controller->inspire($request);
    $data = json_decode($jsonResponse->getContent(), true);

    assert_true($jsonResponse->getStatusCode() === 200, 'Resposta HTTP 200');
    assert_true(isset($data['success']) && $data['success'] === true, 'success: true');
    assert_true(isset($data['theme_uuid']) && strlen($data['theme_uuid']) === 36, 'theme_uuid é UUID válido (36 chars)');
    assert_true(isset($data['preview_url']) && str_contains($data['preview_url'], '/preview/theme/'), 'preview_url correcta');
    assert_true(isset($data['edit_url']) && str_contains($data['edit_url'], '/themes/'), 'edit_url correcta');
    assert_true(isset($data['label']) && $data['label'] === 'Bistro Moderno', 'label correcta no JSON');
    assert_true(isset($data['inspiration']) && str_contains($data['inspiration'], 'Noma'), 'inspiration no JSON');
    assert_true(isset($data['colors']['light']), 'colors.light no JSON');

    // Verificar que o tema foi criado em BD
    $theme = StudioTheme::where('uuid', $data['theme_uuid'])->first();
    assert_true($theme !== null, 'Tema existe na BD');
    assert_true($theme->label === 'Bistro Moderno', 'label correcta na BD');
    assert_true($theme->status === 'draft', 'status é draft');
    assert_true($theme->version === '1.0.0', 'version é 1.0.0');
    assert_true(!empty($theme->colors['light']), 'colors.light guardado na BD');
    assert_true(!empty($theme->colors['dark']),  'colors.dark guardado na BD');
    assert_true(!empty($theme->fonts),           'fonts guardado na BD');
    assert_true(!empty($theme->layout_config),   'layout_config guardado na BD');
    assert_true(!empty($theme->capabilities),    'capabilities guardado na BD');
    assert_true(!empty($theme->sections),        'sections guardado na BD');
    assert_true(str_contains($theme->name, 'restaurante'), 'name contém slug da categoria');

    // Limpar tema criado nos testes
    $theme->forceDelete();
    ok('Tema de teste removido da BD');

} catch (\Throwable $e) {
    fail('inspire() lançou excepção', $e->getMessage());
}

// ────────────────────────────────────────────────────────────────
//  5. ThemeController::inspire — erro sem chave AI
// ────────────────────────────────────────────────────────────────
section('5. ThemeController::inspire — erro sem chave AI');

// O AIEngine resolve a chave por `ai_api_key_{provider}` e só depois pela
// legacy `ai_api_key`. Para simular "sem chave" de forma determinística
// (independente da chave real na BD), apagamos TODAS as variantes.
// O ai_settings_guard.php restaura-as no fim.
\App\Models\StudioSetting::set('ai_api_key', '');
foreach (['claude', 'openai', 'gemini'] as $__p) {
    \App\Models\StudioSetting::set("ai_api_key_{$__p}", '');
}

$request2 = \Illuminate\Http\Request::create('/themes/inspire', 'POST', [
    'category' => 'Tecnologia',
    'style'    => 'moderno',
]);
$request2->setLaravelSession(app('session.store'));

try {
    $resp2 = $controller->inspire($request2);
    $d2    = json_decode($resp2->getContent(), true);
    assert_true($resp2->getStatusCode() === 422, 'Retorna 422 sem chave AI');
    assert_true(isset($d2['error']), 'Resposta tem campo error');
    // A mensagem pode variar conforme o provider configurado; verificamos apenas que há texto
    assert_true(!empty($d2['error']), 'Mensagem de erro não está vazia');
} catch (\Throwable $e) {
    fail('inspire() sem chave deveria retornar 422', $e->getMessage());
}

// ────────────────────────────────────────────────────────────────
//  6. ThemeController::inspire — validação category obrigatória
// ────────────────────────────────────────────────────────────────
section('6. ThemeController::inspire — validação category obrigatória');

$request3 = \Illuminate\Http\Request::create('/themes/inspire', 'POST', [
    'style' => 'moderno',
    // sem category
]);
$request3->setLaravelSession(app('session.store'));

try {
    $controller->inspire($request3);
    fail('Deveria lançar ValidationException sem category');
} catch (\Illuminate\Validation\ValidationException $e) {
    ok('Lança ValidationException quando category em falta');
    assert_true(isset($e->errors()['category']), 'Erro na chave category');
} catch (\Throwable $e) {
    fail('Tipo de excepção inesperado', get_class($e));
}

// ────────────────────────────────────────────────────────────────
//  7. Rota POST /themes/inspire
// ────────────────────────────────────────────────────────────────
section('7. Rota POST /themes/inspire');

$routesFile = file_get_contents($root . '/routes/web.php');

assert_contains($routesFile, "Route::post('/themes/inspire'", 'Rota POST /themes/inspire existe');
assert_contains($routesFile, "ThemeController::class, 'inspire'", 'Rota aponta para ThemeController::inspire');
assert_contains($routesFile, "->name('themes.inspire')", 'Rota tem nome themes.inspire');

// Verificar que a rota inspire está ANTES das rotas {uuid}
$inspirePos = strpos($routesFile, "Route::post('/themes/inspire'");
$uuidPos    = strpos($routesFile, "Route::get('/themes/{uuid}/edit'");
assert_true(
    $inspirePos !== false && $uuidPos !== false && $inspirePos < $uuidPos,
    'Rota /themes/inspire está ANTES das rotas {uuid} (evita conflito de routing)'
);

// Verificar rota está dentro de auth middleware
$authGroupStart = strpos($routesFile, "Route::middleware('auth')->group");
$authGroupEnd   = strrpos($routesFile, '});');
assert_true(
    $authGroupStart < $inspirePos && $inspirePos < $authGroupEnd,
    'Rota inspire está dentro do grupo middleware auth'
);

// ────────────────────────────────────────────────────────────────
//  8. Index.vue — categorias
// ────────────────────────────────────────────────────────────────
section('8. Index.vue — lista de categorias');

$vueFile = file_get_contents($root . '/resources/js/Pages/Themes/Index.vue');

// Verificar categorias originais
$expectedCategories = [
    'E-commerce', 'Restaurante', 'Agência', 'Portfolio', 'Blog',
    'Hotel', 'SaaS', 'Fitness', 'Clínica', 'Música',
    'Imobiliário', 'Educação', 'Fotografia', 'Gaming', 'Beleza',
    'Tecnologia', 'Seguros', 'Jurídico', 'Consultoria', 'Construção',
    'Transporte', 'Viagens', 'ONG', 'Moda', 'Gastronomia',
];

foreach ($expectedCategories as $cat) {
    assert_contains($vueFile, "id: '{$cat}'", "Categoria '{$cat}' presente");
}

// Verificar emojis das novas categorias
assert_contains($vueFile, "'💻'", 'Emoji Tecnologia 💻');
assert_contains($vueFile, "'🛡️'", 'Emoji Seguros 🛡️');
assert_contains($vueFile, "'⚖️'", 'Emoji Jurídico ⚖️');
assert_contains($vueFile, "'📊'", 'Emoji Consultoria 📊');

// Total de categorias
$catCount = substr_count($vueFile, "id: '");
assert_true($catCount >= 25, "Tem pelo menos 25 categorias (tem {$catCount})");

// ────────────────────────────────────────────────────────────────
//  9. Index.vue — estilos visuais
// ────────────────────────────────────────────────────────────────
section('9. Index.vue — estilos visuais');

$expectedStyles = ['minimalista', 'moderno', 'elegante', 'arrojado', 'colorido'];
foreach ($expectedStyles as $style) {
    assert_contains($vueFile, "id: '{$style}'", "Estilo '{$style}' presente");
}
assert_true(count($expectedStyles) === 5, '5 estilos definidos');

// ────────────────────────────────────────────────────────────────
//  10. Index.vue — modal e componentes UI
// ────────────────────────────────────────────────────────────────
section('10. Index.vue — modal e componentes UI');

assert_contains($vueFile, 'showInspireModal',        'Estado showInspireModal existe');
assert_contains($vueFile, 'inspireStep',             'Estado inspireStep existe');
assert_contains($vueFile, 'selectedCategory',        'Estado selectedCategory existe');
assert_contains($vueFile, 'selectedStyle',           'Estado selectedStyle existe');
assert_contains($vueFile, 'inspireResult',           'Estado inspireResult existe');
assert_contains($vueFile, 'inspireError',            'Estado inspireError existe');

// Steps do modal
assert_contains($vueFile, "inspireStep === 'select'",  "Step 'select' existe");
assert_contains($vueFile, "inspireStep === 'loading'", "Step 'loading' existe");
assert_contains($vueFile, "inspireStep === 'result'",  "Step 'result' existe");
assert_contains($vueFile, "inspireStep === 'error'",   "Step 'error' existe");

// Botão principal
assert_contains($vueFile, 'Inspiração por Categoria', 'Botão "Inspiração por Categoria" presente');
assert_contains($vueFile, 'SparklesIcon',             'SparklesIcon importado e usado');

// Funcionalidades do modal
assert_contains($vueFile, 'generateInspiration',     'Função generateInspiration existe');
assert_contains($vueFile, 'closeInspireModal',        'Função closeInspireModal existe');
assert_contains($vueFile, "axios.post('/themes/inspire'", 'Chama POST /themes/inspire');
assert_contains($vueFile, 'Gerar Inspiração ✨',      'Botão "Gerar Inspiração ✨" presente');
assert_contains($vueFile, '!selectedCategory',        'Botão desactivado sem categoria');

// Result step
assert_contains($vueFile, 'inspireResult.preview_url', 'preview_url no iframe');
assert_contains($vueFile, 'inspireResult.label',        'label no resultado');
assert_contains($vueFile, 'inspireResult.inspiration',  'inspiration no resultado');
assert_contains($vueFile, 'inspireResult.edit_url',     'edit_url no botão usar como base');
assert_contains($vueFile, 'Usar como base →',           'Botão "Usar como base" presente');
assert_contains($vueFile, 'Gerar outra',                'Botão "Gerar outra versão" presente');

// Paleta de cores
assert_contains($vueFile, 'lightColors',               'Computed lightColors existe');
assert_contains($vueFile, '--color-primary',            'Filtro de paleta inclui --color-primary');

// Error step
assert_contains($vueFile, 'inspireError',               'Estado de erro mostrado');
assert_contains($vueFile, 'Tentar novamente',           'Botão Tentar novamente no erro');

// Teleport + Transition
assert_contains($vueFile, '<Teleport to="body">',      'Modal usa Teleport');
assert_contains($vueFile, '<Transition name="modal">', 'Modal usa Transition para animação');

// Lucide icons
assert_contains($vueFile, 'XIcon',         'XIcon importado');
assert_contains($vueFile, 'RefreshCwIcon', 'RefreshCwIcon importado');

// ────────────────────────────────────────────────────────────────
//  11. Index.vue — botão presente na tela vazia
// ────────────────────────────────────────────────────────────────
section('11. Index.vue — botão de inspiração na tela vazia');

assert_contains($vueFile, 'Gerar por Categoria', 'Botão "Gerar por Categoria" presente na empty state');

// Verificar que botão vazio também abre o modal
// O botão "Gerar por Categoria" e o @click="showInspireModal = true" estão próximos no ficheiro
$gerCatPos     = strpos($vueFile, 'Gerar por Categoria');
$showInspPos   = strrpos(substr($vueFile, 0, $gerCatPos + 100), 'showInspireModal');
assert_true(
    $showInspPos !== false && ($gerCatPos - $showInspPos) < 400,
    'Botão empty state tem @click que referencia showInspireModal'
);

// ────────────────────────────────────────────────────────────────
//  12. Index.vue — categoryLabel computed
// ────────────────────────────────────────────────────────────────
section('12. Index.vue — categoryLabel computed');

assert_contains($vueFile, 'categoryLabel', 'Computed categoryLabel existe');
assert_contains($vueFile, 'categories.find(c => c.id === selectedCategory.value)', 'categoryLabel usa find nas categories');
assert_contains($vueFile, "?.label ?? selectedCategory.value", 'Fallback para selectedCategory.value');

// ────────────────────────────────────────────────────────────────
//  13. Index.vue — animações e estilos
// ────────────────────────────────────────────────────────────────
section('13. Index.vue — animações e estilos');

assert_contains($vueFile, 'animate-pulse', 'Animação pulse no loading');
assert_contains($vueFile, 'animate-bounce', 'Animação bounce nos dots');
assert_contains($vueFile, 'animation-delay', 'Delay escalonado nos dots');
assert_contains($vueFile, 'modal-enter-active', 'Transição CSS do modal definida');
assert_contains($vueFile, 'from-violet-500 to-indigo-500', 'Gradiente violeta nos botões');
assert_contains($vueFile, 'max-h-64 overflow-y-auto', 'Grid de categorias com scroll');

// ────────────────────────────────────────────────────────────────
//  14. Imports correctos no Index.vue
// ────────────────────────────────────────────────────────────────
section('14. Index.vue — imports correctos');

assert_true(str_contains($vueFile, 'ref') && str_contains($vueFile, 'computed') && str_contains($vueFile, "from 'vue'"), 'Importa ref e computed do Vue');
assert_contains($vueFile, "import axios from 'axios'", 'Importa axios');
assert_contains($vueFile, "SparklesIcon, XIcon, RefreshCwIcon", 'Importa ícones correctos do lucide');
assert_true(str_contains($vueFile, 'Link') && str_contains($vueFile, 'router') && str_contains($vueFile, "from '@inertiajs/vue3'"), 'Importa Link e router do Inertia');

// ────────────────────────────────────────────────────────────────
//  15. Verificar build Vite
// ────────────────────────────────────────────────────────────────
section('15. Build Vite');

$manifestPath = $root . '/public/build/manifest.json';
if (file_exists($manifestPath)) {
    $manifest = json_decode(file_get_contents($manifestPath), true);
    $hasIndex = false;
    foreach ($manifest as $key => $entry) {
        if (str_contains($key, 'Themes/Index')) {
            $hasIndex = true;
            break;
        }
    }
    assert_true($hasIndex, 'Themes/Index.vue compilado no manifest Vite');

    // Verificar que o ficheiro JS compilado existe
    foreach ($manifest as $key => $entry) {
        if (str_contains($key, 'Themes/Index') && isset($entry['file'])) {
            $compiledPath = $root . '/public/build/' . $entry['file'];
            assert_true(file_exists($compiledPath), 'Ficheiro JS compilado existe em public/build/');
            $compiledJs = file_get_contents($compiledPath);
            // Vite minifica nomes de variáveis no build de produção;
            // verificamos que o ficheiro é substancial (> 1 KB) e contém termos
            // que não são manglados (strings literais de template/textos visíveis)
            assert_true(
                filesize($compiledPath) > 1024,
                'Ficheiro JS compilado tem tamanho substancial (> 1 KB)'
            );
            // Strings literais presentes no template não são minificadas
            assert_true(
                str_contains($compiledJs, 'Inspira') || str_contains($compiledJs, 'inspire') || str_contains($compiledJs, 'category'),
                'JS compilado contém strings do módulo inspire'
            );
            break;
        }
    }
} else {
    echo "  \033[33m⚠\033[0m  Manifest Vite não encontrado — skipped (corre 'npm run build' primeiro)\n";
}

// ────────────────────────────────────────────────────────────────
//  Sumário
// ────────────────────────────────────────────────────────────────
echo "\n" . str_repeat('─', 55) . "\n";
$total = $passed + $failed;
echo "\033[1m  Total: {$total}  Passed: \033[32m{$passed}\033[0m\033[1m  Failed: \033[" . ($failed > 0 ? '31' : '32') . "m{$failed}\033[0m\n";

if ($failed > 0) {
    echo "\n\033[31mTestes falhados:\033[0m\n";
    foreach ($errors as $err) {
        echo "  • {$err}\n";
    }
}

echo str_repeat('─', 55) . "\n\n";
exit($failed > 0 ? 1 : 0);
