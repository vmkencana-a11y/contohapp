<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class Admin extends Authenticatable
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
        'name',
        'email',
        'email_hash',
        'password',
        'google_2fa_secret',
        'status',
        'working_days',
        'work_start_time',
        'work_end_time',
        'last_login_at',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'google_2fa_secret',
        'email_hash',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'working_days' => 'array',
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

        static::creating(function (Admin $admin) {
            $admin->created_at = $admin->created_at ?? now();
            $admin->updated_at = now();
        });

        static::updating(function (Admin $admin) {
            $admin->updated_at = now();
        });
    }

    // ==========================================
    // Encrypted Attribute Accessors
    // ==========================================

    /**
     * Get decrypted name.
     */
    public function getNameAttribute(): string
    {
        if (empty($this->attributes['name'])) {
            return '';
        }
        
        // Handle legacy unencrypted data during migration
        try {
            return Crypt::decryptString($this->attributes['name']);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            // Data is not encrypted yet (legacy), return as-is
            return $this->attributes['name'];
        }
    }

    /**
     * Set encrypted name.
     */
    public function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = Crypt::encryptString($value);
    }

    /**
     * Get decrypted email.
     */
    public function getEmailAttribute(): string
    {
        if (empty($this->attributes['email'])) {
            return '';
        }
        
        // Handle legacy unencrypted data during migration
        try {
            return Crypt::decryptString($this->attributes['email']);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            // Data is not encrypted yet (legacy), return as-is
            return $this->attributes['email'];
        }
    }

    /**
     * Set encrypted email with hash for lookup.
     */
    public function setEmailAttribute(string $value): void
    {
        $this->attributes['email'] = Crypt::encryptString($value);
        $this->attributes['email_hash'] = hash('sha256', strtolower($value));
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
    // Password & 2FA
    // ==========================================

    /**
     * Set password with Argon2id hashing.
     */
    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password'] = Hash::make($value);
    }

    /**
     * Check password.
     */
    public function checkPassword(string $password): bool
    {
        return Hash::check($password, $this->password);
    }

    /**
     * Get decrypted 2FA secret.
     */
    public function getDecrypted2faSecretAttribute(): ?string
    {
        if (empty($this->attributes['google_2fa_secret'])) {
            return null;
        }
        return Crypt::decryptString($this->attributes['google_2fa_secret']);
    }

    /**
     * Set encrypted 2FA secret.
     */
    public function setGoogle2faSecretAttribute(?string $value): void
    {
        $this->attributes['google_2fa_secret'] = $value 
            ? Crypt::encryptString($value) 
            : null;
    }

    /**
     * Check if 2FA is enabled.
     */
    public function has2faEnabled(): bool
    {
        return !empty($this->google_2fa_secret);
    }

    // ==========================================
    // Working Hours
    // ==========================================

    /**
     * Check if current time is within admin's working hours.
     * 
     * Returns true if:
     * - No working hours are configured (allows 24/7 access)
     * - Current day is in working_days AND current time is between start and end
     */
    public function isWithinWorkingHours(): bool
    {
        // If no working hours defined, allow access 24/7
        if (empty($this->working_days) || !$this->work_start_time || !$this->work_end_time) {
            return true;
        }
        
        $now = now();
        $currentDay = (int) $now->format('N'); // 1 = Monday, 7 = Sunday
        
        // Normalize working_days to integers for comparison (JSON may store as strings)
        $workingDays = array_map('intval', $this->working_days);
        
        // Check if today is a working day
        if (!in_array($currentDay, $workingDays, true)) {
            return false;
        }
        
        // Normalize time format to H:i for comparison
        $currentTime = $now->format('H:i');
        
        // Handle both H:i:s and H:i formats from database
        $startTime = substr((string) $this->work_start_time, 0, 5);
        $endTime = substr((string) $this->work_end_time, 0, 5);
        
        // Check if current time is within working hours
        $isWithinTime = $currentTime >= $startTime && $currentTime <= $endTime;
        
        return $isWithinTime;
    }

    /**
     * Get human-readable working days.
     */
    public function getWorkingDaysLabelAttribute(): string
    {
        if (empty($this->working_days)) {
            return 'Tidak diatur (24/7)';
        }
        
        $dayNames = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];
        
        $days = array_map(fn($d) => $dayNames[$d] ?? '', $this->working_days);
        return implode(', ', array_filter($days));
    }

    /**
     * Get human-readable working hours.
     */
    public function getWorkingHoursLabelAttribute(): string
    {
        if (!$this->work_start_time || !$this->work_end_time) {
            return 'Tidak diatur';
        }
        
        return substr($this->work_start_time, 0, 5) . ' - ' . substr($this->work_end_time, 0, 5);
    }

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * Get admin's roles.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'admin_role')
            ->withPivot('assigned_at')
            ->using(\App\Models\Pivots\AdminRole::class);
    }

    // ==========================================
    // Permission Helpers
    // ==========================================

    /**
     * Check if admin has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        return $this->roles()
            ->whereHas('permissions', fn($q) => $q->where('name', $permission))
            ->exists();
    }

    /**
     * Check if admin has a specific role.
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles->contains('name', $roleName);
    }

    /**
     * Check if admin has any of the given permissions.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return $this->roles()
            ->whereHas('permissions', fn($q) => $q->whereIn('name', $permissions))
            ->exists();
    }

    /**
     * Get all permissions.
     */
    public function allPermissions(): array
    {
        return $this->roles
            ->flatMap(fn($role) => $role->permissions->pluck('name'))
            ->unique()
            ->values()
            ->all();
    }

    // ==========================================
    // Status Helpers
    // ==========================================

    /**
     * Check if admin is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    // ==========================================
    // Static Helpers
    // ==========================================

    /**
     * Find admin by email using hash lookup.
     */
    public static function findByEmail(string $email): ?self
    {
        $emailHash = hash('sha256', strtolower($email));
        return self::where('email_hash', $emailHash)->first();
    }
}

