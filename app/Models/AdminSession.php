<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminSession extends Model
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
        'admin_id',
        'token_hash',
        'ip_address',
        'user_agent',
        'last_activity_at',
        'created_at',
        'idle_timeout',
        'absolute_timeout',
        'last_rotated_at',
        'revoked_at',
        'revoke_reason',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'token_hash',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'last_activity_at' => 'datetime',
            'created_at'       => 'datetime',
            'idle_timeout'     => 'integer',
            'absolute_timeout' => 'integer',
            'last_rotated_at'  => 'datetime',
            'revoked_at'       => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function (AdminSession $session) {
            $session->created_at       = $session->created_at       ?? now();
            $session->last_activity_at = $session->last_activity_at ?? now();
            $session->last_rotated_at  = $session->last_rotated_at  ?? now();
        });
    }

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * Get the admin.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    // ==========================================
    // Status Helpers
    // ==========================================

    /**
     * Check if session is revoked.
     */
    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    /**
     * Check if session has exceeded idle timeout.
     */
    public function hasIdleTimeoutExpired(): bool
    {
        return now()->diffInSeconds($this->last_activity_at) > $this->idle_timeout;
    }

    /**
     * Check if session has exceeded absolute timeout.
     */
    public function hasAbsoluteTimeoutExpired(): bool
    {
        return now()->diffInSeconds($this->created_at) > $this->absolute_timeout;
    }

    /**
     * Check if session is valid (not revoked and not expired).
     */
    public function isValid(): bool
    {
        if ($this->isRevoked()) {
            return false;
        }

        if ($this->hasIdleTimeoutExpired() || $this->hasAbsoluteTimeoutExpired()) {
            return false;
        }

        return true;
    }

    /**
     * Check if token rotation is needed.
     */
    public function needsRotation(int $hoursThreshold = 6): bool
    {
        if (!$this->last_rotated_at) {
            return true;
        }

        return now()->diffInHours($this->last_rotated_at) >= $hoursThreshold;
    }

    /**
     * Update last activity timestamp.
     */
    public function touch($attribute = null): bool
    {
        if ($attribute) {
            return parent::touch($attribute);
        }
        return $this->update(['last_activity_at' => now()]);
    }

    /**
     * Revoke the session.
     */
    public function revoke(string $reason = 'admin_logout'): void
    {
        $this->update([
            'revoked_at'    => now(),
            'revoke_reason' => $reason,
        ]);
    }

    /**
     * Update token hash after rotation.
     */
    public function rotateToken(string $newTokenHash): void
    {
        $this->update([
            'token_hash' => $newTokenHash,
            'last_rotated_at' => now(),
        ]);
    }

    // ==========================================
    // Static Helpers
    // ==========================================

    /**
     * Find session by token hash.
     */
    public static function findByToken(string $token): ?self
    {
        $tokenHash = hash('sha256', $token);
        return self::where('token_hash', $tokenHash)
            ->whereNull('revoked_at')
            ->first();
    }

    /**
     * Revoke all sessions for an admin.
     */
    public static function revokeAllForAdmin(string $adminId, string $reason = 'forced_logout'): int
    {
        return self::where('admin_id', $adminId)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at'    => now(),
                'revoke_reason' => $reason,
            ]);
    }
}
