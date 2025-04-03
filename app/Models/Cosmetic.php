<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cosmetic extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'ingredients',
        'parsed_ingredients',
        'added_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'added_at' => 'datetime',
        'parsed_ingredients' => 'array',
    ];

    /**
     * Get the user that owns the cosmetic product.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Parse raw ingredients into a structured format.
     *
     * @param string $ingredients
     * @return array
     */
    public static function parseIngredients(string $ingredients): array
    {
        // Simple parsing logic - split by commas and clean up
        $parsed = array_map('trim', explode(',', $ingredients));

        // Remove empty elements
        $parsed = array_filter($parsed, function($ingredient) {
            return !empty($ingredient);
        });

        return array_values($parsed);
    }

    /**
     * Set ingredients and automatically parse them.
     *
     * @param string $value
     * @return void
     */
    public function setIngredientsAttribute(string $value): void
    {
        $this->attributes['ingredients'] = $value;
        $this->attributes['parsed_ingredients'] = json_encode(self::parseIngredients($value));
    }

    /**
     * Check if the cosmetic contains a specific ingredient.
     *
     * @param string $ingredient
     * @return bool
     */
    public function containsIngredient(string $ingredient): bool
    {
        $ingredients = $this->parsed_ingredients ?? [];

        // Check if any of the ingredients contain the search term
        foreach ($ingredients as $item) {
            if (stripos($item, $ingredient) !== false) {
                return true;
            }
        }

        return false;
    }
}
