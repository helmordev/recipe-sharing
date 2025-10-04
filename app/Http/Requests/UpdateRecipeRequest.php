<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRecipeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('recipe'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'ingredients' => 'required|array|min:1',
            'ingredients.*' => 'required|string|max:255',
            'instructions' => 'required|string|min:50',
            'cooking_time' => 'required|integer|min:1|max:1440',
            'servings' => 'required|integer|min:1|max:50',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
            'is_public' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'ingredients.required' => 'At least one ingredient is required.',
            'ingredients.*.required' => 'All ingredient fields must be filled.',
            'instructions.min' => 'Instructions must be at least 50 characters long.',
            'cooking_time.max' => 'Cooking time cannot exceed 24 hours (1440 minutes).',
            'image.max' => 'Image size cannot exceed 2MB.',
        ];
    }

    public function prepareForValidation(): void
    {
        // filter out empty ingredients
        if ($this->hasFile('inredients')) {
            $inredients = array_filter($this->inredients, function ($ingredient) {
                return !empty(trim($ingredient));
            });

            $this->merge([
                'ingredients' => array_values($inredients)
            ]);
        }
    }
}
