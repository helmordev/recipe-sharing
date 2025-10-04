<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Recipe;
use App\Models\Tag;

class HomeController extends Controller
{
    public function index(): View
    {
        $featureRecipes = Recipe::public()
            ->with(['user', 'tags'])
            ->trending()
            ->take(6)
            ->get();

        $recentRecipes = Recipe::public()
            ->with(['user', 'tags'])
            ->latest()
            ->take(6)
            ->get();

        $popularTags = Tag::withCount('recipes')
            ->orderByDesc('recipes_count')
            ->take(10)
            ->get();

        return view('home', compact('featureRecipes', 'recentRecipes', 'popularTags'));
    }

    public function trending(): View
    {
        $recipes = Recipe::public()
            ->with(['user', 'tags'])
            ->trending()
            ->paginate(12);

        return view('trending', compact('recipes'));
    }
}
