<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Routing\UrlGenerator;

class SkinPhoto extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'file_path',
        'thumbnail_path',
        'taken_at',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'taken_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Атрибуты, которые следует добавить к массивам модели.
     *
     * @var array
     */
    protected $appends = [
        'image_url',
        'thumbnail_url',
        'upload_date'
    ];

    /**
     * Get the user that owns the skin photo.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the skin analysis for the photo.
     */
    public function skinAnalysis(): HasOne
    {
        return $this->hasOne(SkinAnalysis::class, 'photo_id');
    }

    /**
     * Get the full file path.
     */
    public function getFullPathAttribute(): string
    {
        return storage_path('app/' . $this->file_path);
    }

    /**
     * Get the URL for the photo.
     */
//    public function getUrlAttribute(): string
//    {
//        return url('storage/' . str_replace('public/', '', $this->file_path));
//    }

    /**
     * Get the URL for the photo.
     */
    public function getImageUrlAttribute()
    {
        return url('storage/' . str_replace('public/', '', $this->file_path));
    }

    /**
     * Get the URL for the thumbnail.
     */
    public function getThumbnailUrlAttribute()
    {
        if (!$this->thumbnail_path) {
            return $this->getImageUrlAttribute();
        }

        return url('storage/' . str_replace('public/', '', $this->thumbnail_path));
    }

    /**
     * Get the upload date in ISO 8601 format
     */
    public function getUploadDateAttribute(): string
    {
        return $this->taken_at->toIso8601String();
    }
}
