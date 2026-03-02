<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminOtp extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'admin_id',
        'otp_hash',
        'expired_at',
        'verified_at',
        'attempt_count',
        'last_attempt_at',
        'locked_until',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'expired_at' => 'datetime',
            'verified_at' => 'datetime',
            'last_attempt_at' => 'datetime',
            'locked_until' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the admin.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * Check if OTP is expired.
     */
    public function isExpired(): bool
    {
        return now()->gt($this->expired_at);
    }

    /**
     * Check if locked.
     */
    public function isLocked(): bool
    {
        return $this->locked_until && now()->lt($this->locked_until);
    }

    /**
     * Increment attempts.
     */
    public function incrementAttempts(): void
    {
        $this->increment('attempt_count');
        $this->update(['last_attempt_at' => now()]);
    }

    /**
     * Lock the record.
     */
    public function lock(int $minutes = 60): void
    {
        $this->update(['locked_until' => now()->addMinutes($minutes)]);
    }

    /**
     * Mark as verified.
     */
    public function markVerified(): void
    {
        $this->update([
            'verified_at' => now(),
            'attempt_count' => 0,
            'locked_until' => null,
        ]);
    }

    /**
     * Check if exceeded max attempts.
     */
    public function hasExceededMaxAttempts(int $max = 5): bool
    {
        return $this->attempt_count >= $max;
    }
}
