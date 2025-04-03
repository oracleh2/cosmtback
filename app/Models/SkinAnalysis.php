<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SkinAnalysis extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'photo_id',
        'skin_condition',
        'skin_issues',
        'metrics',
    ];


    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'skin_condition' => 'array',
        'skin_issues' => 'array',
        'metrics' => 'array',
    ];

    /**
     * Атрибуты, которые следует добавить к массивам модели.
     *
     * @var array
     */
    protected $appends = [
        'analysis_date',
        'image_url',
        'thumbnail_url'
    ];

    /**
     * Get the photo that owns the skin analysis.
     */
    public function photo(): BelongsTo
    {
        return $this->belongsTo(SkinPhoto::class, 'photo_id');
    }

    /**
     * Get the user that owns the skin analysis through the photo.
     */
    public function user(): BelongsTo
    {
        return $this->photo->user();
    }

    /**
     * Get the recommendation based on this analysis.
     */
    public function recommendation(): HasOne
    {
        return $this->hasOne(Recommendation::class, 'analysis_id');
    }

    /**
     * Get specific skin condition data.
     */
    public function getSkinConditionData($key = null)
    {
        if ($key === null) {
            return $this->skin_condition;
        }

        return $this->skin_condition[$key] ?? null;
    }

    /**
     * Check if the skin has a specific issue.
     */
    public function hasSkinIssue($issue): bool
    {
        $issues = $this->skin_issues ?? [];
        return in_array($issue, $issues);
    }

    /**
     * Get the skin hydration level.
     */
    public function getHydrationLevel()
    {
        return $this->getSkinConditionData('hydration') ?? 0;
    }

    /**
     * Get the analysis date in ISO 8601 format
     */
    public function getAnalysisDateAttribute(): string
    {
        return $this->created_at->toIso8601String();
    }

    /**
     * Get the image URL through the photo relationship
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->photo ? $this->photo->image_url : null;
    }

    /**
     * Get the thumbnail URL through the photo relationship
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->photo ? $this->photo->thumbnail_url : null;
    }
}
