<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use IIluminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except('show');
    }

    public function show(User $user): View
    {
        $recipe = $user->recipe()
            ->public()
            ->with(['tags'])
            ->latest()
            ->paginate(12);

        $stats = [
            'recipes_count' => $user->recipes()->count(),
            'liked_received' => $user->recipes()->sum('likes_count'),
            'forked_received' => $user->recipes()->sum('forks_count'),
        ];

        return view('profile.show', compact('user', 'recipe', 'stats'));
    }

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $use = Auth::user();
        $request->user()->fill($request->validated());

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function dashboard()
    {
        $user = auth()->user();

        $myRecipes = $user->recipes()
            ->with(['tags'])
            ->latest()
            ->take(6)
            ->get();

        $recentLikes = $user->likes()
            ->with(['recipe.user'])
            ->latest()
            ->take(5)
            ->get();

        $stats = [
            'recipes_count' => $user->recipes()->count(),
            'likes_given' => $user->likes()->count(),
            'favorites_count' => $user->favorites()->count(),
            'comments_count' => $user->comments()->count(),
        ];

        return view('dashboard', compact('myRecipes', 'recentLikes', 'stats'));
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
