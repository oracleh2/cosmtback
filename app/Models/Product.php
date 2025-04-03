<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'brand',
        'image_path',
        'category',
        'ingredients',
        'description',
        'rating',
        'skin_type_target',
        'skin_concerns_target',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'ingredients' => 'array',
        'skin_concerns_target' => 'array',
        'rating' => 'float',
    ];

    /**
     * Атрибуты, которые следует добавить к массивам модели.
     *
     * @var array
     */
    protected $appends = ['image_url'];

    /**
     * Get recommendations that include this product.
     */
    public function recommendations(): BelongsToMany
    {
        return $this->belongsToMany(Recommendation::class, 'recommendation_products')
            ->withPivot('recommendation_reason')
            ->withTimestamps();
    }

    /**
     * Get the URL for the product image.
     */
    public function getImageUrlAttribute()
    {
        if (empty($this->image_path)) {
            return null;
        }

        // Если путь к изображению - это URL
        if (filter_var($this->image_path, FILTER_VALIDATE_URL)) {
            return $this->image_path;
        }

        // Если путь к изображению - это локальный файл
        return url('storage/' . str_replace('public/', '', $this->image_path));
    }
}
