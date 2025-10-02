<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->json('ingredients');
            $table->text('instructions');
            $table->integer('cooking_time'); // in minutes
            $table->integer('servings');
            $table->string('image')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_recipe_id')->nullable()->constrained('recipes')->onDelete('set null');
            $table->integer('likes_count')->default(0);
            $table->integer('forks_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->boolean('is_public')->default(true);
            $table->timestamps();

            $table->index(['created_at', 'likes_count']);
            $table->index(['parent_recipe_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
