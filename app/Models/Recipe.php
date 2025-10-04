<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Recipe extends Model
{
    /** @use HasFactory<\Database\Factories\RecipeFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'ingredients',
        'instructions',
        'cooking_time',
        'servings',
        'image',
        'user_id',
        'parent_recipe_id',
        'is_public',
    ];

    protected $casts = [
        'ingredients' => 'array',
        'is_public' => 'boolean',
    ];

    protected $with = ['user', 'tags'];

    // Boot method for auto-generating slug
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($recipe) {
            if (!$recipe->slug) {
                $recipe->slug = Str::slug($recipe->title);

                // Ensure uniqueness
                $originalSlug = $recipe->slug;
                $counter = 1;

                while (static::where('slug', $recipe->slug)->exists()) {
                    $recipe->slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }
        });
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)->whereNull('parent_id');
    }

    public function allComments()
    {
        return $this->hasMany(Comment::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function parentRecipe()
    {
        return $this->belongsTo(Recipe::class, 'parent_recipe_id');
    }

    public function forks()
    {
        return $this->hasMany(Recipe::class, 'parent_recipe_id');
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    public function likedBy()
    {
        return $this->belongsToMany(User::class, 'likes')->withTimestamps();
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeTrending($query)
    {
        return $query->orderByDesc('likes_count')
            ->orderByDesc('forks_count')
            ->orderByDesc('created_at');
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('ingredients', 'like', "%{$search}%")
                ->orWhereHas('tags', function ($tagQuery) use ($search) {
                    $tagQuery->where('name', 'like', "%{$search}%");
                });
        });
    }

    // Helper methods
    public function getImageUrlAttribute()
    {
        return $this->image
            ? asset('storage/' . $this->image)
            : 'https://via.placeholder.com/400x300/EBF4FF/7F9CF5?text=' . urlencode($this->title);
    }

    public function isForkedFrom(Recipe $recipe)
    {
        return $this->parent_recipe_id === $recipe->id;
    }

    public function getLineage()
    {
        $lineage = collect([$this]);
        $current = $this;

        while ($current->parentRecipe) {
            $current = $current->parentRecipe;
            $lineage->prepend($current);
        }

        return $lineage;
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
