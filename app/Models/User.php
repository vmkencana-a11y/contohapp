<?php

namespace App\Models;

use App\Enums\UserStatusEnum;
use App\Support\IdGenerator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Crypt;

class User extends Model
{
    use HasFactory;

    /**
     * The primary key type.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * Disable default timestamps, we use custom datetime fields.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'id',
        'name',
        'email',
        'phone',
        'email_hash',
        'google_sub',
        'google_sub_hash',
        'google_linked_at',
        'status',
        'level',
        'referral_code',
        'referred_by',
        'referred_at',
        'status_changed_at',
        'status_changed_by',
        'status_reason',
        'suspended_at',
        'suspended_by',
        'suspended_reason',
        'banned_at',
        'banned_by',
        'banned_reason',
        'last_login_at',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'email',
        'phone',
        'email_hash',
        'google_sub',
        'google_sub_hash',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'status' => UserStatusEnum::class,
            'referred_at' => 'datetime',
            'status_changed_at' => 'datetime',
            'suspended_at' => 'datetime',
            'banned_at' => 'datetime',
            'google_linked_at' => 'datetime',
            'last_login_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function (User $user) {
            if (empty($user->id)) {
                $user->id = IdGenerator::generateUnique(fn($id) => self::find($id) !== null);
            }
            $user->created_at = $user->created_at ?? now();
            $user->updated_at = now();
        });

        static::updating(function (User $user) {
            $user->updated_at = now();
        });
    }

    // ==========================================
    // Encrypted Attribute Accessors
    // ==========================================

    /**
     * Get decrypted name (auto-decrypt on access).
     */
    public function getNameAttribute(): string
    {
        if (empty($this->attributes['name'])) {
            return '';
        }
        return Crypt::decryptString($this->attributes['name']);
    }

    /**
     * Set encrypted name.
     */
    public function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = Crypt::encryptString($value);
    }

    /**
     * Get decrypted email (auto-decrypt on access).
     */
    public function getEmailAttribute(): string
    {
        if (empty($this->attributes['email'])) {
            return '';
        }
        return Crypt::decryptString($this->attributes['email']);
    }

    /**
     * Set encrypted email.
     */
    public function setEmailAttribute(string $value): void
    {
        $this->attributes['email'] = Crypt::encryptString($value);
        $this->attributes['email_hash'] = hash('sha256', strtolower($value));
    }

    /**
     * Get decrypted phone (auto-decrypt on access).
     */
    public function getPhoneAttribute(): ?string
    {
        if (empty($this->attributes['phone'])) {
            return null;
        }
        return Crypt::decryptString($this->attributes['phone']);
    }

    /**
     * Set encrypted phone.
     */
    public function setPhoneAttribute(?string $value): void
    {
        $this->attributes['phone'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Get masked email for display.
     */
    public function getMaskedEmailAttribute(): string
    {
        $email = $this->email;
        $parts = explode('@', $email);
        $name = $parts[0];
        $domain = $parts[1] ?? '';
        
        $masked = substr($name, 0, 2) . str_repeat('*', max(strlen($name) - 2, 0));
        return $masked . '@' . $domain;
    }

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * Get user sessions.
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    /**
     * Get active sessions.
     */
    public function activeSessions(): HasMany
    {
        return $this->hasMany(UserSession::class)
            ->whereNull('revoked_at');
    }

    /**
     * Get user's KYC record.
     */
    public function kyc(): HasOne
    {
        return $this->hasOne(UserKyc::class);
    }

    /**
     * Get user's referrals (downlines).
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(UserReferral::class, 'referrer_id');
    }

    /**
     * Get the user who referred this user.
     */
    public function referrer(): HasOne
    {
        return $this->hasOne(UserReferral::class, 'user_id');
    }

    /**
     * Get login logs.
     */
    public function loginLogs(): HasMany
    {
        return $this->hasMany(Logs\UserLoginLog::class);
    }

    /**
     * Get status change logs.
     */
    public function statusLogs(): HasMany
    {
        return $this->hasMany(Logs\UserStatusLog::class);
    }

    // ==========================================
    // Status Helpers
    // ==========================================

    /**
     * Check if user can login.
     */
    public function canLogin(): bool
    {
        return $this->status->canLogin();
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->status === UserStatusEnum::ACTIVE;
    }

    /**
     * Check if user is banned.
     */
    public function isBanned(): bool
    {
        return $this->status === UserStatusEnum::BANNED;
    }

    // ==========================================
    // Static Helpers
    // ==========================================

    /**
     * Find user by email.
     */
    public static function findByEmail(string $email): ?self
    {
        $emailHash = hash('sha256', strtolower($email));
        return self::where('email_hash', $emailHash)->first();
    }

    /**
     * Find user by referral code.
     */
    public static function findByReferralCode(string $code): ?self
    {
        return self::where('referral_code', $code)->first();
    }

    /**
     * Generate unique referral code.
     */
    public static function generateReferralCode(): string
    {
        do {
            $code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        } while (self::where('referral_code', $code)->exists());

        return $code;
    }

    /**
     * Find user by Google sub (unique Google user ID).
     */
    public static function findByGoogleSub(string $googleSub): ?self
    {
        $hash = hash('sha256', $googleSub);
        return self::where('google_sub_hash', $hash)->first();
    }

    /**
     * Check if user has Google account linked.
     */
    public function hasGoogleLinked(): bool
    {
        return !empty($this->google_sub_hash);
    }

    // ==========================================
    // Google Sub Encrypted Accessors
    // ==========================================

    /**
     * Get decrypted google_sub.
     */
    public function getGoogleSubAttribute(): ?string
    {
        if (empty($this->attributes['google_sub'])) {
            return null;
        }
        return Crypt::decryptString($this->attributes['google_sub']);
    }

    /**
     * Set encrypted google_sub + hash for lookup.
     */
    public function setGoogleSubAttribute(?string $value): void
    {
        if ($value) {
            $this->attributes['google_sub'] = Crypt::encryptString($value);
            $this->attributes['google_sub_hash'] = hash('sha256', $value);
        } else {
            $this->attributes['google_sub'] = null;
            $this->attributes['google_sub_hash'] = null;
        }
    }
}
