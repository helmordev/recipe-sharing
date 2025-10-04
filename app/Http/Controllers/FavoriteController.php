<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use App\Models\Recipe;

class FavoriteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): View
    {
        $favorites = auth()->user()
            ->favoriteRecipes()
            ->with(['user', 'tags'])
            ->latest('favorites.created_at')
            ->paginate(12);

        return view('favorites.index', compact('favorites'));
    }

    public function store(Recipe $recipe): JsonResponse
    {
        if (auth()->user()->hasFavorited($recipe)) {
            return response()->json(['message' => 'You have already favorited this recipe.'], 400);
        }

        auth()->user()->favorites()->create([
            'recipe_id' => $recipe->id,
        ]);

        return response()->json([
            'message' => 'Recipe favorited successfully.',
        ], 200);
    }

    public function destroy(Recipe $recipe): JsonResponse
    {
        $favorite = auth()->user()->favorites()->where('recipe_id', $recipe->id)->first();

        if (!$favorite) {
            return response()->json(['message' => 'Not favorited'], 400);
        }

        $favorite->delete();

        return response()->json([
            'message' => 'Recipe unfavorited successfully.',
        ], 200);
    }
}
