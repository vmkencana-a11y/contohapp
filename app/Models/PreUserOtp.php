<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class PreUserOtp extends Model
{
    use HasFactory;

    /**
     * The table name.
     */
    protected $table = 'pre_user_otps';

    /**
     * Disable timestamps, we use custom created_at.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'email',
        'email_hash',
        'name',
        'referral_code',
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

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'email',
        'name',
        'otp_hash',
    ];

    /**
     * Get the attributes that should be cast.
     */
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
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function (PreUserOtp $otp) {
            $otp->created_at = $otp->created_at ?? now();
        });
    }

    // ==========================================
    // Encrypted Attribute Accessors
    // ==========================================

    /**
     * Get decrypted email.
     */
    public function getDecryptedEmailAttribute(): string
    {
        return Crypt::decryptString($this->attributes['email']);
    }

    /**
     * Set encrypted email with hash.
     */
    public function setEmailAttribute(string $value): void
    {
        $this->attributes['email'] = Crypt::encryptString($value);
        $this->attributes['email_hash'] = hash('sha256', strtolower($value));
    }

    /**
     * Get decrypted name.
     */
    public function getDecryptedNameAttribute(): ?string
    {
        if (empty($this->attributes['name'])) {
            return null;
        }
        return Crypt::decryptString($this->attributes['name']);
    }

    /**
     * Set encrypted name.
     */
    public function setNameAttribute(?string $value): void
    {
        $this->attributes['name'] = $value ? Crypt::encryptString($value) : null;
    }

    // ==========================================
    // Status Helpers
    // ==========================================

    /**
     * Check if OTP is expired.
     */
    public function isExpired(): bool
    {
        return now()->gt($this->expired_at);
    }

    /**
     * Check if OTP is verified.
     */
    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * Check if locked due to brute force.
     */
    public function isLocked(): bool
    {
        return $this->locked_until && now()->lt($this->locked_until);
    }

    /**
     * Check if max attempts exceeded.
     */
    public function hasExceededMaxAttempts(int $maxAttempts = 5): bool
    {
        return $this->attempt_count >= $maxAttempts;
    }

    /**
     * Increment attempt count.
     */
    public function incrementAttempts(): void
    {
        $this->increment('attempt_count');
        $this->update(['last_attempt_at' => now()]);
    }

    /**
     * Lock for brute force protection.
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

    // ==========================================
    // Static Helpers
    // ==========================================

    /**
     * Find latest OTP by email (not verified, not expired).
     */
    public static function findLatestByEmail(string $email): ?self
    {
        $emailHash = hash('sha256', strtolower($email));
        
        return self::where('email_hash', $emailHash)
            ->whereNull('verified_at')
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Create OTP record.
     */
    public static function createOtp(
        string $email,
        string $otpHash,
        ?string $name = null,
        ?string $referralCode = null,
        int $ttlMinutes = 5
    ): self {
        return self::create([
            'email' => $email,
            'name' => $name,
            'referral_code' => $referralCode,
            'otp_hash' => $otpHash,
            'expired_at' => now()->addMinutes($ttlMinutes),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
