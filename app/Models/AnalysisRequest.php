<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalysisRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'photo_id',
        'status',
        'analysis_id',
        'error_message',
        'additional_data',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'additional_data' => 'array',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the analysis request.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the photo associated with the analysis request.
     */
    public function photo()
    {
        return $this->belongsTo(SkinPhoto::class, 'photo_id');
    }

    /**
     * Get the skin analysis result, if completed.
     */
    public function analysis()
    {
        return $this->belongsTo(SkinAnalysis::class, 'analysis_id');
    }

    /**
     * Check if the analysis is pending.
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the analysis is processing.
     */
    public function isProcessing()
    {
        return $this->status === 'processing';
    }

    /**
     * Check if the analysis is completed.
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the analysis failed.
     */
    public function isFailed()
    {
        return $this->status === 'failed';
    }

    /**
     * Mark the analysis as processing.
     */
    public function markAsProcessing()
    {
        $this->status = 'processing';
        $this->save();
    }

    /**
     * Mark the analysis as completed.
     */
    public function markAsCompleted($analysisId)
    {
        $this->status = 'completed';
        $this->analysis_id = $analysisId;
        $this->completed_at = now();
        $this->save();
    }

    /**
     * Mark the analysis as failed.
     */
    public function markAsFailed($errorMessage)
    {
        $this->status = 'failed';
        $this->error_message = $errorMessage;
        $this->save();
    }
}
