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

/* ── Authenticated routes ── */
Route::middleware('auth')->group(function () {

    Route::get('/', fn () => redirect()->route('dashboard'));

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/about', [AboutController::class, 'index'])->name('about');

    // Themes
    Route::get('/themes',               [ThemeController::class, 'index'])->name('themes.index');
    Route::get('/themes/create',        [ThemeController::class, 'create'])->name('themes.create');
    Route::post('/themes',              [ThemeController::class, 'store'])->name('themes.store');
    Route::get('/themes/{uuid}/edit',   [ThemeController::class, 'edit'])->name('themes.edit');
    Route::put('/themes/{uuid}',        [ThemeController::class, 'update'])->name('themes.update');
    Route::delete('/themes/{uuid}',     [ThemeController::class, 'destroy'])->name('themes.destroy');
    Route::get('/themes/{uuid}/export', [ThemeController::class, 'export'])->name('themes.export');

    // Plugins
    Route::get('/plugins',               [PluginController::class, 'index'])->name('plugins.index');
    Route::get('/plugins/create',        [PluginController::class, 'create'])->name('plugins.create');
    Route::post('/plugins',              [PluginController::class, 'store'])->name('plugins.store');
    Route::get('/plugins/{uuid}/edit',   [PluginController::class, 'edit'])->name('plugins.edit');
    Route::put('/plugins/{uuid}',        [PluginController::class, 'update'])->name('plugins.update');
    Route::delete('/plugins/{uuid}',     [PluginController::class, 'destroy'])->name('plugins.destroy');
    Route::get('/plugins/{uuid}/export', [PluginController::class, 'export'])->name('plugins.export');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
});
