<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'skin_type',
        'avatar',
        'skin_concerns',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'skin_concerns' => 'array',
    ];

    /**
     * Get the skin photos for the user.
     */
    public function skinPhotos(): User|HasMany
    {
        return $this->hasMany(SkinPhoto::class);
    }

    /**
     * Get the cosmetics for the user.
     */
    public function cosmetics(): User|HasMany
    {
        return $this->hasMany(Cosmetic::class);
    }

    /**
     * Get the recommendations for the user.
     */
    public function recommendations(): User|HasMany
    {
        return $this->hasMany(Recommendation::class);
    }

    /**
     * Get the latest skin photo.
     */
    public function latestSkinPhoto()
    {
        return $this->skinPhotos()->latest('taken_at')->first();
    }

    /**
     * Get the skin analyses through the photos.
     */
    public function skinAnalyses(): HasManyThrough|User
    {
        return $this->hasManyThrough(SkinAnalysis::class, SkinPhoto::class, 'user_id', 'photo_id');
    }

    /**
     * Get the avatar URL.
     */
    public function getAvatarUrlAttribute()
    {
        if (empty($this->avatar)) {
            return null;
        }

        // Если аватар - это URL
        if (filter_var($this->avatar, FILTER_VALIDATE_URL)) {
            return $this->avatar;
        }

        // Если аватар - это локальный файл
        return url('storage/' . str_replace('public/', '', $this->avatar));
    }
}
