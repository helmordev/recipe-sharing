<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Recipe;
use App\Models\Tag;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Http\Requests\StoreRecipeRequest;
use App\Http\Requests\StoreRecipeRequest;
use IIluminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;

class RecipeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except('index', 'show');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $recipes = Recipe::public()->with(['user', 'tags'])->paginate(12);

        // search functionality
        if ($request->has('seach') && $request->search) {
            $query->search($request->search);
        }

        // filter by tag
        if ($request->has('tag') && $request->tag) {
            $recipes->whereHas('tags', function ($query) use ($request) {
                $query->where('slug', $request->tag);
            });
        }

        // Sorting
        $sort = $request->get('sort', 'latest');
        switch ($sort) {
            case 'trending':
                query->trending();
                break;
            case 'popular':
                query->orderByDesc('likes_count');
                break;
            case 'oldest':
                query->oldest();
                break;
            default:
                query->latest();
                break;
        }

        $tags = Tag::all();

        return view('recipes.index', compact('recipes', 'tags'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        $tags = Tag::all();
        $parentRecipe = null;

        if ($request->has('fork') && $request->fork) {
            $parentRecipe = Recipe::where('slug', $request->fork)->first();
        }

        return view('recipes.create', compact('tags', 'parentRecipe'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRecipeRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = Auth::id();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('recipes', 'public');
        }

        $recipe = Recipe::create($data);

        if ($request->has('tags')) {
            $recipe->tags()->attach($request->tags);
        }

        // update fork count if this is a fork
        if ($recipe->parent_recipe_id) {
            Recipe::where('id', $recipe->parent_recipe_id)->increment('forks_count');
        }

        return redirect()->route('recipes.show', $recipe)->with('success', 'Recipe created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Recipe $recipe): View
    {
        $recipe->load(['user', 'tags', 'comments.user', 'comments.replies.user', 'forks.user']);

        $relatedRecipes = Recipe::public()
            ->where('id', '!=', $recipe->id)
            ->where(function ($query) use ($recipe) {
                $query->where('user_id', $recipe->user_id)
                    ->orWhereHas('tags', function ($q) use ($recipe) {
                        $q->whereIn('tags.id', $recipe->tags->pluck('id'));
                    });
            })
            ->with(['user', 'tags'])
            ->take(3)
            ->get();

        $lineage = $recipe->getLineage();

        return view('recipes.show', compact('recipe', 'relatedRecipes', 'lineage'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Recipe $recipe): View
    {
        $this->authorize('update', $recipe);

        $tags = Tag::all();
        $selectedTags = $recipe->tags->pluck('id')->toArray();

        return view('recipes.edit', compact('recipe', 'tags', 'selectedTags'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreRecipeRequest $request, Recipe $recipe): RedirectResponse
    {
        $this->authorize('update', $recipe);

        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($recipe->image) {
                Storage::disk('public')->delete($recipe->image);
            }
            $data['image'] = $request->file('image')->store('recipes', 'public');
        }

        $recipe->update($data);

        if ($request->has('tags')) {
            $recipe->tags()->sync($request->tags);
        }

        return redirect()->route('recipes.show', $recipe)->with('success', 'Recipe updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Recipe $recipe): RedirectResponse
    {
        $this->authorize('delete', $recipe);

        if ($recipe->image) {
            Storage::disk('public')->delete($recipe->image);
        }

        $recipe->delete();

        return redirect()->route('recipes.index')->with('success', 'Recipe deleted successfully.');
    }

    public function fork(Recipe $recipe): RedirectResponse
    {
        return redirect()->route('recipes.create', ['fork' => $recipe->slug]);
    }
}
