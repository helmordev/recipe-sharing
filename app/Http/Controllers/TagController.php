<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tag;

class TagController extends Controller
{
    public function show(Tag $tag)
    {
        $recipes = $tag->recipes()
            ->public()
            ->with(['user', 'tags'])
            ->latest()
            ->paginate(12);

        return view('tags.show', compact('tag', 'recipes'));
    }

    public function index()
    {
        $tags = Tag::withCount('recipes')
            ->orderByDesc('recipes_count')
            ->get();

        return view('tags.index', compact('tags'));
    }
}
