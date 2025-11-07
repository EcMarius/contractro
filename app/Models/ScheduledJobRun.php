<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduledJobRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_name',
        'status',
        'started_at',
        'completed_at',
        'duration_seconds',
        'output',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Mark the job as completed successfully
     */
    public function markSuccess(string $output = null, array $metadata = []): void
    {
        $this->update([
            'status' => 'success',
            'completed_at' => now(),
            'duration_seconds' => now()->diffInSeconds($this->started_at),
            'output' => $output,
            'metadata' => array_merge($this->metadata ?? [], $metadata),
        ]);
    }

    /**
     * Mark the job as failed
     */
    public function markFailed(string $errorMessage, string $output = null): void
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'duration_seconds' => now()->diffInSeconds($this->started_at),
            'error_message' => $errorMessage,
            'output' => $output,
        ]);
    }

    /**
     * Scopes
     */
    public function scopeForJob($query, string $jobName)
    {
        return $query->where('job_name', $jobName);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('started_at', '>=', now()->subHours($hours));
    }

    /**
     * Get the last successful run for a job
     */
    public static function getLastSuccessfulRun(string $jobName): ?self
    {
        return static::forJob($jobName)
            ->success()
            ->orderBy('started_at', 'desc')
            ->first();
    }

    /**
     * Get recent failures for a job
     */
    public static function getRecentFailures(string $jobName, int $hours = 24): int
    {
        return static::forJob($jobName)
            ->failed()
            ->recent($hours)
            ->count();
    }

    /**
     * Check if a job has failed recently
     */
    public static function hasRecentFailures(string $jobName, int $threshold = 3, int $hours = 24): bool
    {
        return static::getRecentFailures($jobName, $hours) >= $threshold;
    }
}
