<?php

namespace App\Models;

use App\Enums\KycStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class UserKyc extends Model
{
    use HasFactory;

    /**
     * The table name.
     */
    protected $table = 'user_kyc';

    /**
     * Disable timestamps, we use custom datetime fields.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'session_id',
        'status',
        'id_type',
        'id_number',
        'id_number_hash',
        'selfie_path',
        'id_card_path',
        'left_side_path',
        'right_side_path',
        'encrypted_selfie_key',
        'encrypted_id_card_key',
        'encrypted_left_side_key',
        'encrypted_right_side_key',
        'key_version',
        'liveness_result',
        'frame_count',
        'capture_method',
        'breach_flag',
        'verified_by',
        'verified_at',
        'rejection_reason',
        'metadata',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'id_number',
        'id_number_hash',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'status' => KycStatusEnum::class,
            'metadata' => 'array',
            'liveness_result' => 'array',
            'frame_count' => 'integer',
            'key_version' => 'integer',
            'breach_flag' => 'boolean',
            'verified_at' => 'datetime',
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

        static::creating(function (UserKyc $kyc) {
            $kyc->created_at = $kyc->created_at ?? now();
            $kyc->updated_at = now();
        });

        static::updating(function (UserKyc $kyc) {
            $kyc->updated_at = now();
        });
    }

    // ==========================================
    // Encrypted Attribute Accessors
    // ==========================================

    /**
     * Get decrypted ID number.
     */
    public function getDecryptedIdNumberAttribute(): ?string
    {
        if (empty($this->attributes['id_number'])) {
            return null;
        }
        return Crypt::decryptString($this->attributes['id_number']);
    }

    /**
     * Set encrypted ID number and compute HMAC hash.
     */
    public function setIdNumberAttribute(?string $value): void
    {
        $this->attributes['id_number'] = $value 
            ? Crypt::encryptString($value) 
            : null;

        // Compute HMAC-SHA256 hash for duplicate detection
        $this->attributes['id_number_hash'] = $value
            ? hash_hmac('sha256', $value, config('app.key'))
            : null;
    }

    /**
     * Generate HMAC hash for an ID number (static helper).
     */
    public static function hashIdNumber(string $idNumber): string
    {
        return hash_hmac('sha256', $idNumber, config('app.key'));
    }

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the verifier admin.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'verified_by');
    }

    // ==========================================
    // Status Helpers
    // ==========================================

    /**
     * Check if KYC is verified.
     */
    public function isVerified(): bool
    {
        return $this->status === KycStatusEnum::APPROVED;
    }

    /**
     * Check if KYC is pending.
     */
    public function isPending(): bool
    {
        return $this->status === KycStatusEnum::PENDING;
    }

    /**
     * Check if KYC is under review.
     */
    public function isUnderReview(): bool
    {
        return $this->status === KycStatusEnum::UNDER_REVIEW;
    }

    /**
     * Check if KYC is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === KycStatusEnum::REJECTED;
    }

    /**
     * Check if KYC can be resubmitted.
     */
    public function canResubmit(): bool
    {
        return $this->status === KycStatusEnum::REJECTED;
    }

    /**
     * Submit for review.
     */
    public function submitForReview(): void
    {
        $this->update(['status' => KycStatusEnum::UNDER_REVIEW]);
    }

    /**
     * Approve KYC.
     */
    public function approve(int $adminId): void
    {
        $this->update([
            'status' => KycStatusEnum::APPROVED,
            'verified_by' => $adminId,
            'verified_at' => now(),
        ]);
    }

    /**
     * Reject KYC.
     */
    public function reject(int $adminId, string $reason): void
    {
        $this->update([
            'status' => KycStatusEnum::REJECTED,
            'verified_by' => $adminId,
            'verified_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }
}
