<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PluginController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ThemeController;
use Illuminate\Support\Facades\Route;

/* ── Auth (guest only) ── */
Route::middleware('guest')->group(function () {
    Route::get('/login',  [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

/* ── Theme preview (auth required: o preview compila Blade das secções server-side) ── */
Route::get('/preview/theme/{uuid}', [ThemeController::class, 'preview'])->name('themes.preview')->middleware('auth');
Route::get('/sobre', [ThemeController::class, 'previewPage'])->name('themes.preview.sobre')->middleware('auth');
Route::get('/servicos', [ThemeController::class, 'previewPage'])->name('themes.preview.servicos')->middleware('auth');
Route::get('/galeria', [ThemeController::class, 'previewPage'])->name('themes.preview.galeria')->middleware('auth');
Route::get('/contactos', [ThemeController::class, 'previewPage'])->name('themes.preview.contactos')->middleware('auth');
Route::get('/preview-home', [ThemeController::class, 'previewPage'])->name('themes.preview.home')->middleware('auth');


/* ── Authenticated routes ── */
Route::middleware('auth')->group(function () {

    Route::get('/', fn () => redirect()->route('dashboard'));

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/about', [AboutController::class, 'index'])->name('about');

    /* ── Themes ── */
    Route::get('/themes',               [ThemeController::class, 'index'])->name('themes.index');
    Route::get('/themes/create',        [ThemeController::class, 'create'])->name('themes.create');
    Route::post('/themes',              [ThemeController::class, 'store'])->name('themes.store');
    Route::post('/themes/inspire',      [ThemeController::class, 'inspire'])->name('themes.inspire');
    Route::get('/themes/{uuid}/edit',   [ThemeController::class, 'edit'])->name('themes.edit');
    Route::put('/themes/{uuid}',        [ThemeController::class, 'update'])->name('themes.update');
    Route::delete('/themes/{uuid}',     [ThemeController::class, 'destroy'])->name('themes.destroy');
    Route::get('/themes/{uuid}/export',        [ThemeController::class, 'export'])->name('themes.export');
    Route::get('/themes/{uuid}/export-prompt', [ThemeController::class, 'exportPrompt'])->name('themes.export-prompt');

    Route::post('/themes/{uuid}/chat',           [ThemeController::class, 'chat'])->name('themes.chat');
    Route::post('/themes/{uuid}/chat-history',   [ThemeController::class, 'saveChatHistory'])->name('themes.chat.history');
    Route::get('/themes/{uuid}/journal',         [ThemeController::class, 'journal'])->name('themes.journal');
    Route::post('/themes/{uuid}/classify',       [ThemeController::class, 'classifyRequest'])->name('themes.classify');
    Route::post('/themes/{uuid}/revert-step',    [ThemeController::class, 'revertStep'])->name('themes.revert-step');
    Route::post('/themes/{uuid}/build/plan',     [ThemeController::class, 'buildPlan'])->name('themes.build.plan');
    Route::post('/themes/{uuid}/build/step',     [ThemeController::class, 'buildStep'])->name('themes.build.step');
    Route::post('/themes/{uuid}/build/verify',   [ThemeController::class, 'buildVerify'])->name('themes.build.verify');
    Route::post('/themes/{uuid}/generate-ai',   [ThemeController::class, 'generateAi'])->name('themes.generate-ai');
    Route::post('/themes/{uuid}/publish',       [ThemeController::class, 'publish'])->name('themes.publish');
    Route::post('/themes/{uuid}/install-in-cms', [ThemeController::class, 'installInCms'])->name('themes.install-cms');
    Route::post('/themes/{uuid}/upload-asset',  [ThemeController::class, 'uploadAsset'])->name('themes.upload-asset');
    Route::delete('/themes/{uuid}/asset',       [ThemeController::class, 'deleteAsset'])->name('themes.delete-asset');

    // Versionamento
    Route::get('/themes/{uuid}/versions',                          [ThemeController::class, 'listVersions'])->name('themes.versions.list');
    Route::post('/themes/{uuid}/versions',                         [ThemeController::class, 'createVersion'])->name('themes.versions.create');
    Route::post('/themes/{uuid}/versions/{versionUuid}/restore',   [ThemeController::class, 'restoreVersion'])->name('themes.versions.restore');
    Route::delete('/themes/{uuid}/versions/{versionUuid}',         [ThemeController::class, 'deleteVersion'])->name('themes.versions.delete');
    Route::get('/themes/{uuid}/recipes',                           [ThemeController::class, 'recipes'])->name('themes.recipes');

    /* ── Plugins ── */
    Route::get('/plugins',               [PluginController::class, 'index'])->name('plugins.index');
    Route::get('/plugins/create',        [PluginController::class, 'create'])->name('plugins.create');
    Route::post('/plugins',              [PluginController::class, 'store'])->name('plugins.store');
    Route::get('/plugins/{uuid}/edit',   [PluginController::class, 'edit'])->name('plugins.edit');
    Route::put('/plugins/{uuid}',        [PluginController::class, 'update'])->name('plugins.update');
    Route::delete('/plugins/{uuid}',     [PluginController::class, 'destroy'])->name('plugins.destroy');
    Route::get('/plugins/{uuid}/export',        [PluginController::class, 'export'])->name('plugins.export');
    Route::get('/plugins/{uuid}/export-prompt', [PluginController::class, 'exportPrompt'])->name('plugins.export-prompt');
    Route::get('/plugins/{uuid}/recipes',       [PluginController::class, 'recipes'])->name('plugins.recipes');

    /* ── Plugin versioning ── */
    Route::get ('/plugins/{uuid}/versions',              [PluginController::class, 'versions'])->name('plugins.versions.list');
    Route::post('/plugins/{uuid}/versions',              [PluginController::class, 'saveVersion'])->name('plugins.versions.save');
    Route::get ('/plugins/{uuid}/versions/{versionId}',  [PluginController::class, 'versionSnapshot'])->name('plugins.versions.snapshot');
    Route::post('/plugins/{uuid}/versions/{versionId}/restore', [PluginController::class, 'restoreVersion'])->name('plugins.versions.restore');
    Route::post('/plugins/{uuid}/versions/compare',      [PluginController::class, 'compareVersions'])->name('plugins.versions.compare');

    Route::get('/plugins/{uuid}/preview-widget',   [PluginController::class, 'previewWidget'])->name('plugins.preview-widget');
    Route::get('/plugins/{uuid}/export-doc',       [PluginController::class, 'exportDoc'])->name('plugins.export-doc');
    Route::post('/plugins/{uuid}/generate-docs',   [PluginController::class, 'generateDocs'])->name('plugins.generate-docs');
    Route::post('/plugins/{uuid}/inspire',         [PluginController::class, 'inspire'])->name('plugins.inspire');
    Route::post('/plugins/{uuid}/chat',           [PluginController::class, 'chat'])->name('plugins.chat');
    Route::post('/plugins/{uuid}/chat-history',   [PluginController::class, 'saveChatHistory'])->name('plugins.chat.history');
    Route::get('/plugins/{uuid}/journal',         [PluginController::class, 'journal'])->name('plugins.journal');
    Route::post('/plugins/{uuid}/classify',       [PluginController::class, 'classifyRequest'])->name('plugins.classify');
    Route::post('/plugins/{uuid}/revert-step',    [PluginController::class, 'revertStep'])->name('plugins.revert-step');
    Route::post('/plugins/{uuid}/build/plan',     [PluginController::class, 'buildPlan'])->name('plugins.build.plan');
    Route::post('/plugins/{uuid}/build/step',     [PluginController::class, 'buildStep'])->name('plugins.build.step');
    Route::post('/plugins/{uuid}/build/verify',   [PluginController::class, 'buildVerify'])->name('plugins.build.verify');
    Route::post('/plugins/{uuid}/generate-ai',   [PluginController::class, 'generateAi'])->name('plugins.generate-ai');
    Route::post('/plugins/{uuid}/publish',        [PluginController::class, 'publish'])->name('plugins.publish');
    Route::post('/plugins/{uuid}/install-in-cms', [PluginController::class, 'installInCms'])->name('plugins.install-cms');

    /* ── Recipes ── */
    Route::get('/recipes',               [RecipeController::class, 'index'])->name('recipes.index');
    Route::get('/recipes/create',        [RecipeController::class, 'create'])->name('recipes.create');
    Route::post('/recipes',              [RecipeController::class, 'store'])->name('recipes.store');
    Route::get('/recipes/analytics',     [RecipeController::class, 'analytics'])->name('recipes.analytics');
    Route::get('/recipes/{id}/edit',     [RecipeController::class, 'edit'])->name('recipes.edit');
    Route::put('/recipes/{id}',          [RecipeController::class, 'update'])->name('recipes.update');
    Route::delete('/recipes/{id}',       [RecipeController::class, 'destroy'])->name('recipes.destroy');
    Route::post('/recipes/{id}/toggle',  [RecipeController::class, 'toggle'])->name('recipes.toggle');
    Route::post('/recipes/{id}/test',    [RecipeController::class, 'test'])->name('recipes.test');
    Route::post('/recipes/export',       [RecipeController::class, 'export'])->name('recipes.export');
    Route::post('/recipes/import',       [RecipeController::class, 'import'])->name('recipes.import');

    /* ── Settings ── */
    Route::get('/settings',             [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings',             [SettingsController::class, 'update'])->name('settings.update');
    Route::get('/settings/reveal-key',  [SettingsController::class, 'revealKey'])->name('settings.reveal-key');
});
