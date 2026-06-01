<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PluginController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ThemeController;
use Illuminate\Support\Facades\Route;

/* ── Auth (guest only) ── */
Route::middleware('guest')->group(function () {
    Route::get('/login',  [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

/* ── Public — theme preview (no auth required) ── */
Route::get('/preview/theme/{uuid}', [ThemeController::class, 'preview'])->name('themes.preview');

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
    Route::post('/themes/{uuid}/generate-ai',   [ThemeController::class, 'generateAi'])->name('themes.generate-ai');
    Route::post('/themes/{uuid}/publish',       [ThemeController::class, 'publish'])->name('themes.publish');
    Route::post('/themes/{uuid}/install-in-cms', [ThemeController::class, 'installInCms'])->name('themes.install-cms');
    Route::post('/themes/{uuid}/upload-asset',  [ThemeController::class, 'uploadAsset'])->name('themes.upload-asset');
    Route::delete('/themes/{uuid}/asset',       [ThemeController::class, 'deleteAsset'])->name('themes.delete-asset');

    /* ── Plugins ── */
    Route::get('/plugins',               [PluginController::class, 'index'])->name('plugins.index');
    Route::get('/plugins/create',        [PluginController::class, 'create'])->name('plugins.create');
    Route::post('/plugins',              [PluginController::class, 'store'])->name('plugins.store');
    Route::get('/plugins/{uuid}/edit',   [PluginController::class, 'edit'])->name('plugins.edit');
    Route::put('/plugins/{uuid}',        [PluginController::class, 'update'])->name('plugins.update');
    Route::delete('/plugins/{uuid}',     [PluginController::class, 'destroy'])->name('plugins.destroy');
    Route::get('/plugins/{uuid}/export',        [PluginController::class, 'export'])->name('plugins.export');
    Route::get('/plugins/{uuid}/export-prompt', [PluginController::class, 'exportPrompt'])->name('plugins.export-prompt');

    Route::get('/plugins/{uuid}/preview-widget',   [PluginController::class, 'previewWidget'])->name('plugins.preview-widget');
    Route::get('/plugins/{uuid}/export-doc',       [PluginController::class, 'exportDoc'])->name('plugins.export-doc');
    Route::post('/plugins/{uuid}/generate-docs',   [PluginController::class, 'generateDocs'])->name('plugins.generate-docs');
    Route::post('/plugins/{uuid}/inspire',         [PluginController::class, 'inspire'])->name('plugins.inspire');
    Route::post('/plugins/{uuid}/chat',           [PluginController::class, 'chat'])->name('plugins.chat');
    Route::post('/plugins/{uuid}/generate-ai',   [PluginController::class, 'generateAi'])->name('plugins.generate-ai');
    Route::post('/plugins/{uuid}/publish',        [PluginController::class, 'publish'])->name('plugins.publish');
    Route::post('/plugins/{uuid}/install-in-cms', [PluginController::class, 'installInCms'])->name('plugins.install-cms');

    /* ── Settings ── */
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
});
