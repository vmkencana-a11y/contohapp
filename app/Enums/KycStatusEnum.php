<?php

namespace App\Enums;

/**
 * KYC status enum with verification flow.
 * 
 * Flow: processing → pending → under_review → approved/rejected
 */
enum KycStatusEnum: string
{
    case PROCESSING = 'processing';  // Async image processing in progress
    case PENDING = 'pending';
    case UNDER_REVIEW = 'under_review';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    /**
     * Get display label in Indonesian.
     */
    public function label(): string
    {
        return match($this) {
            self::PROCESSING => 'Memproses',
            self::PENDING => 'Menunggu',
            self::UNDER_REVIEW => 'Sedang Direview',
            self::APPROVED => 'Disetujui',
            self::REJECTED => 'Ditolak',
        };
    }

    /**
     * Get CSS class for status badge.
     */
    public function badgeClass(): string
    {
        return match($this) {
            self::PROCESSING => 'bg-yellow-100 text-yellow-800',
            self::PENDING => 'bg-gray-100 text-gray-800',
            self::UNDER_REVIEW => 'bg-blue-100 text-blue-800',
            self::APPROVED => 'bg-success-100 text-success-800',
            self::REJECTED => 'bg-danger-100 text-danger-800',
        };
    }

    /**
     * Check if KYC is verified.
     */
    public function isVerified(): bool
    {
        return $this === self::APPROVED;
    }

    /**
     * Check if user can re-submit KYC.
     */
    public function canResubmit(): bool
    {
        return $this === self::REJECTED;
    }

    /**
     * Check if KYC is in terminal state (no more changes unless rejected).
     */
    public function isTerminal(): bool
    {
        return $this === self::APPROVED;
    }

    /**
     * Check if KYC is being processed asynchronously.
     */
    public function isProcessing(): bool
    {
        return $this === self::PROCESSING;
    }
}

