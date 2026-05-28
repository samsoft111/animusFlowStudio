<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StudioPlugin;
use App\Models\StudioTheme;
use Inertia\Inertia;
use Inertia\Response;

class AboutController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('About', [
            'stats' => [
                'themes'    => StudioTheme::count(),
                'plugins'   => StudioPlugin::count(),
                'published' => StudioTheme::where('is_published', true)->count()
                             + StudioPlugin::where('is_published', true)->count(),
            ],
        ]);
    }
}
