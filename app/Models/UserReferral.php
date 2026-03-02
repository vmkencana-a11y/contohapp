<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserReferral extends Model
{
    use HasFactory;

    /**
     * Disable timestamps, we use custom datetime fields.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'referrer_id',
        'referred_at',
        'status',
        'created_at',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'referred_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function (UserReferral $referral) {
            $referral->created_at = $referral->created_at ?? now();
            $referral->referred_at = $referral->referred_at ?? now();
        });
    }

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * Get the referred user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the referrer (upline).
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    // ==========================================
    // Status Helpers
    // ==========================================

    /**
     * Check if referral is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if referral is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Cancel the referral.
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    // ==========================================
    // Static Helpers
    // ==========================================

    /**
     * Create active referral.
     */
    public static function createActive(string $userId, string $referrerId): self
    {
        return self::create([
            'user_id' => $userId,
            'referrer_id' => $referrerId,
            'status' => 'active',
        ]);
    }

    /**
     * Cancel all referrals for a user (when banned).
     */
    public static function cancelForUser(string $userId): int
    {
        return self::where('user_id', $userId)
            ->where('status', 'active')
            ->update(['status' => 'cancelled']);
    }
}
