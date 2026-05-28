<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StudioPlugin;
use App\Models\StudioTheme;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => [
                'themes'           => StudioTheme::count(),
                'plugins'          => StudioPlugin::count(),
                'published_themes' => StudioTheme::where('is_published', true)->count(),
                'published_plugins'=> StudioPlugin::where('is_published', true)->count(),
            ],
            'recentThemes'  => StudioTheme::latest()->limit(5)->get(['id', 'uuid', 'name', 'label', 'status', 'created_at']),
            'recentPlugins' => StudioPlugin::latest()->limit(5)->get(['id', 'uuid', 'name', 'label', 'status', 'created_at']),
        ]);
    }
}
