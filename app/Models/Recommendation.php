<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Recommendation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'analysis_id',
        'details',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'details' => 'array',
    ];

    /**
     * Атрибуты, которые следует добавить к массивам модели.
     *
     * @var array
     */
    protected $appends = ['date', 'recommendations'];

    /**
     * Get the user that owns the recommendation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the skin analysis that generated this recommendation.
     */
    public function analysis(): BelongsTo
    {
        return $this->belongsTo(SkinAnalysis::class, 'analysis_id');
    }

    /**
     * Получить продукты, рекомендованные в этой рекомендации
     */
    public function recommendedProducts():BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'recommendation_products')
            ->withPivot('recommendation_reason')
            ->withTimestamps();
    }

    /**
     * Получить дату рекомендации в формате ISO 8601
     */
    public function getDateAttribute(): string
    {
        return $this->created_at->toIso8601String();
    }

    /**
     * Получить текстовые рекомендации
     */
    public function getRecommendationsAttribute(): array
    {
        return $this->details['recommendations'] ?? [];
    }

    /**
     * Create a recommendation based on skin analysis and user's cosmetics.
     *
     * @param SkinAnalysis $analysis
     * @return Recommendation
     */
    public static function generateFromAnalysis(SkinAnalysis $analysis): Recommendation
    {
        // Get the user associated with this analysis
        $user = $analysis->photo->user;

        // Get skin condition from analysis
        $skinCondition = $analysis->skin_condition;
        $skinIssues = $analysis->skin_issues ?? [];

        // Here you would implement your AI recommendation logic
        // This is a placeholder example
        $recommendationText = [
            "Используйте увлажняющий крем дважды в день",
            "Избегайте горячей воды при умывании",
            "Пейте больше воды"
        ];

        $details = [
            'recommendations' => $recommendationText,
            'skin_issues' => $skinIssues,
            'avoid_ingredients' => ['alcohol', 'fragrance'],
        ];

        // Create the recommendation
        $recommendation = self::create([
            'user_id' => $user->id,
            'analysis_id' => $analysis->id,
            'details' => $details
        ]);

        // Find suitable products to recommend
        $hydrationLevel = $skinCondition['hydration'] ?? 0;

        if ($hydrationLevel < 50) {
            // Find a hydrating product
            $product = Product::where('skin_type_target', 'Сухая')
                ->orWhereJsonContains('skin_concerns_target', 'Сухость')
                ->first();

            if ($product) {
                $recommendation->recommendedProducts()->attach($product->id, [
                    'recommendation_reason' => 'Подходит для сухой кожи'
                ]);
            }
        }

        return $recommendation;
    }
}
