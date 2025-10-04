<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Recipe;
use Illuminate\Http\JsonResponse;

class LikeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Recipe $recipe): JsonResponse
    {
        if (auth()->user()->hasLiked($recipe)) {
            return response()->json(['message' => 'You have already liked this recipe.'], 400);
        }

        auth()->user()->likes()->create([
            'recipe_id' => $recipe->id,
        ]);

        $recipe->increment('likes_count');

        return response()->json([
            'message' => 'Recipe liked successfully.',
            'likes_count' => $recipe->fresh()->likes_count,
        ]);
    }

    public function destroy(Recipe $recipe): JsonResponse
    {
        $like = auth()->user()->likes()->where('recipe_id', $recipe->id)->first();

        if (!$like) {
            return response()->json(['message' => 'Not liked'], 400);
        }

        $like->delete();

        $recipe->decrement('likes_count');

        return response()->json([
            'message' => 'Recipe unliked successfully.',
            'likes_count' => $recipe->fresh()->likes_count,
        ]);
    }
}
