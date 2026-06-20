<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StudioAiRecipe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response as FacadeResponse;
use Inertia\Inertia;
use Inertia\Response;

class RecipeController extends Controller
{
    public function index(Request $request): Response
    {
        $query = StudioAiRecipe::query();

        if ($request->filled('q')) {
            $search = '%' . $request->input('q') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('description', 'like', $search)
                  ->orWhere('prompt_pattern', 'like', $search);
            });
        }

        if ($request->filled('type')) {
            $query->where('recipe_type', $request->input('type'));
        }

        $recipes = $query->orderBy('name')->get();

        // Calculate analytics summary inline
        $totalHits = (int) StudioAiRecipe::sum('hits');
        $totalTokens = (int) StudioAiRecipe::sum('tokens_saved');
        $activeCount = (int) StudioAiRecipe::where('is_enabled', true)->count();

        return Inertia::render('Recipes/Index', [
            'recipes' => $recipes,
            'filters' => $request->only(['q', 'type']),
            'stats' => [
                'total_hits' => $totalHits,
                'total_tokens_saved' => $totalTokens,
                'active_count' => $activeCount,
            ]
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Recipes/Form', [
            'recipe' => [
                'id' => null,
                'recipe_type' => 'theme',
                'name' => '',
                'description' => '',
                'prompt_pattern' => '',
                'code_templates' => [],
                'reply_template' => '',
                'confidence_score' => 100,
                'is_enabled' => true,
                'fuzzy_threshold' => 80,
                'placeholder_types' => new \stdClass(),
            ]
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'recipe_type'       => 'required|in:theme,plugin',
            'name'              => 'required|string|max:100|unique:studio_ai_recipes,name',
            'description'       => 'nullable|string|max:500',
            'prompt_pattern'    => 'required|string|max:1000',
            'code_templates'    => 'nullable|array',
            'reply_template'    => 'required|string|max:1000',
            'confidence_score'  => 'required|integer|between:0,100',
            'is_enabled'        => 'required|boolean',
            'fuzzy_threshold'   => 'required|integer|between:0,100',
            'placeholder_types' => 'nullable|array',
        ]);

        if (empty($data['code_templates'])) {
            $data['code_templates'] = [];
        }
        if (empty($data['placeholder_types'])) {
            $data['placeholder_types'] = [];
        }

        StudioAiRecipe::create($data);

        return redirect()->route('recipes.index')
            ->with('success', 'Receita criada com sucesso.');
    }

    public function edit(int $id): Response
    {
        $recipe = StudioAiRecipe::findOrFail($id);

        if (is_null($recipe->placeholder_types) || empty($recipe->placeholder_types)) {
            $recipe->placeholder_types = new \stdClass();
        }

        return Inertia::render('Recipes/Form', [
            'recipe' => $recipe
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $recipe = StudioAiRecipe::findOrFail($id);

        $data = $request->validate([
            'recipe_type'       => 'required|in:theme,plugin',
            'name'              => 'required|string|max:100|unique:studio_ai_recipes,name,' . $id,
            'description'       => 'nullable|string|max:500',
            'prompt_pattern'    => 'required|string|max:1000',
            'code_templates'    => 'nullable|array',
            'reply_template'    => 'required|string|max:1000',
            'confidence_score'  => 'required|integer|between:0,100',
            'is_enabled'        => 'required|boolean',
            'fuzzy_threshold'   => 'required|integer|between:0,100',
            'placeholder_types' => 'nullable|array',
        ]);

        if (empty($data['code_templates'])) {
            $data['code_templates'] = [];
        }
        if (empty($data['placeholder_types'])) {
            $data['placeholder_types'] = [];
        }

        $recipe->update($data);

        return redirect()->route('recipes.index')
            ->with('success', 'Receita atualizada com sucesso.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $recipe = StudioAiRecipe::findOrFail($id);
        $recipe->delete();

        return redirect()->route('recipes.index')
            ->with('success', 'Receita eliminada com sucesso.');
    }

    public function toggle(int $id): RedirectResponse
    {
        $recipe = StudioAiRecipe::findOrFail($id);
        $recipe->is_enabled = !$recipe->is_enabled;
        $recipe->save();

        $status = $recipe->is_enabled ? 'ativada' : 'desativada';
        return redirect()->back()
            ->with('success', "Receita {$status} com sucesso.");
    }

    public function test(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'prompt' => 'required|string',
        ]);

        $result = StudioAiRecipe::testResolve($id, $request->input('prompt'));

        return response()->json($result);
    }

    public function export(Request $request)
    {
        $ids = $request->input('ids');
        $query = StudioAiRecipe::query();

        if (is_array($ids) && !empty($ids)) {
            $query->whereIn('id', $ids);
        }

        $recipes = $query->get()->makeHidden(['id', 'hits', 'last_used_at', 'tokens_saved']);

        $exportData = [
            'version' => '1.0',
            'exported_at' => now()->toIso8601String(),
            'workspace' => 'animusFlowStudio',
            'recipes' => $recipes->toArray(),
        ];

        $json = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filename = 'recipes-studio-' . date('YmdHis') . '.afrecipes';

        return FacadeResponse::streamDownload(function () use ($json) {
            echo $json;
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file',
        ]);

        $file = $request->file('file');
        $content = file_get_contents($file->getRealPath());
        $data = json_decode($content, true);

        if (!$data || !isset($data['recipes']) || !is_array($data['recipes'])) {
            return redirect()->back()->with('error', 'Ficheiro .afrecipes inválido ou corrompido.');
        }

        $imported = 0;
        foreach ($data['recipes'] as $recipeData) {
            if (empty($recipeData['name']) || empty($recipeData['prompt_pattern'])) {
                continue;
            }

            StudioAiRecipe::updateOrCreate(
                [
                    'recipe_type' => $recipeData['recipe_type'] ?? 'theme',
                    'name' => $recipeData['name'],
                ],
                [
                    'description' => $recipeData['description'] ?? null,
                    'prompt_pattern' => $recipeData['prompt_pattern'],
                    'code_templates' => $recipeData['code_templates'] ?? [],
                    'reply_template' => $recipeData['reply_template'] ?? 'Resolvido via receita local.',
                    'confidence_score' => $recipeData['confidence_score'] ?? 100,
                    'is_enabled' => $recipeData['is_enabled'] ?? true,
                    'fuzzy_threshold' => $recipeData['fuzzy_threshold'] ?? 80,
                    'placeholder_types' => $recipeData['placeholder_types'] ?? [],
                ]
            );
            $imported++;
        }

        return redirect()->route('recipes.index')
            ->with('success', "{$imported} receitas importadas com sucesso.");
    }

    public function analytics(): Response
    {
        $totalHits = (int) StudioAiRecipe::sum('hits');
        $totalTokens = (int) StudioAiRecipe::sum('tokens_saved');
        $activeCount = (int) StudioAiRecipe::where('is_enabled', true)->count();

        // Top 10 recipes
        $topRecipes = StudioAiRecipe::orderByDesc('hits')
            ->where('hits', '>', 0)
            ->limit(10)
            ->get(['name', 'hits', 'tokens_saved']);

        // Unused recipes
        $unusedRecipes = StudioAiRecipe::where('hits', 0)
            ->orderBy('name')
            ->get(['id', 'name', 'recipe_type', 'confidence_score']);

        // Recent activity based on last_used_at
        $recentUsed = StudioAiRecipe::whereNotNull('last_used_at')
            ->orderByDesc('last_used_at')
            ->limit(5)
            ->get(['name', 'last_used_at', 'hits']);

        return Inertia::render('Recipes/Analytics', [
            'stats' => [
                'total_hits' => $totalHits,
                'total_tokens_saved' => $totalTokens,
                'active_count' => $activeCount,
            ],
            'top_recipes' => $topRecipes,
            'unused_recipes' => $unusedRecipes,
            'recent_used' => $recentUsed,
        ]);
    }
}
