<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comment;
use App\Models\Recipe;
use App\Http\Requests\StoreCommentRequest;

class CommentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(StoreCommentRequest $request, Recipe $recipe)
    {
        $comment = $recipe->allComments()->create([
            'content' => $request->content,
            'user_id' => auth()->id(),
            'parent_id' => $request->parent_id,
        ]);

        $recipe->increment('comments_count');

        if ($request->expectsJson()) {
            return response()->json([
                'comment' => $comment->load('user'),
                'message' => 'Comment created successfully',
            ], 201);
        }

        return back()->with('success', 'Comment created successfully');
    }

    public function destroy(Comment $comment)
    {
        $this->authorize('delete', $comment);

        $recipe = $comment->recipe;
        $comment->delete();
        $recipe->decrement('comments_count');

        return back()->with('success', 'Comment deleted successfully');
    }
}
