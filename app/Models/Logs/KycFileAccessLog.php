<?php

namespace App\Models\Logs;

use App\Models\Admin;
use App\Models\UserKyc;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable audit log for KYC file access.
 * 
 * This model should NEVER have update or delete operations.
 */
class KycFileAccessLog extends Model
{
    /**
     * The table name.
     */
    protected $table = 'kyc_file_access_logs';

    /**
     * Disable timestamps - we only use created_at.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_kyc_id',
        'accessed_by',
        'role',
        'file_type',
        'action',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-set created_at
        static::creating(function (KycFileAccessLog $log) {
            $log->created_at = now();
        });

        // Prevent updates and deletes (immutable log)
        static::updating(function () {
            throw new \RuntimeException('KYC access logs are immutable and cannot be updated.');
        });

        static::deleting(function () {
            throw new \RuntimeException('KYC access logs are immutable and cannot be deleted.');
        });
    }

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * Get the KYC record.
     */
    public function kyc(): BelongsTo
    {
        return $this->belongsTo(UserKyc::class, 'user_kyc_id');
    }

    /**
     * Get the admin who accessed.
     */
    public function accessor(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'accessed_by');
    }

    // ==========================================
    // Static Helper
    // ==========================================

    /**
     * Log a file access event.
     */
    public static function logAccess(
        int $kycId,
        int $adminId,
        string $fileType,
        string $action,
        ?string $role = null
    ): self {
        return self::create([
            'user_kyc_id' => $kycId,
            'accessed_by' => $adminId,
            'role' => $role,
            'file_type' => $fileType,
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
