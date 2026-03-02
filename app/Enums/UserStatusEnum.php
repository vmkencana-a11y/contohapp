<?php

namespace App\Enums;

/**
 * User status enum with state machine transitions.
 * 
 * Allowed transitions:
 * - active → inactive (system only, automatic)
 * - active → suspended (admin/system)
 * - active → banned (admin only)
 * - inactive → active (system, login with OTP)
 * - inactive → banned (admin only)
 * - suspended → active (admin only, reactivation)
 * - suspended → banned (admin only, escalation)
 * - banned → NONE (terminal state)
 */
enum UserStatusEnum: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';
    case BANNED = 'banned';

    /**
     * Get display label in Indonesian.
     */
    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Aktif',
            self::INACTIVE => 'Tidak Aktif',
            self::SUSPENDED => 'Ditangguhkan',
            self::BANNED => 'Diblokir',
        };
    }

    /**
     * Get CSS class for status badge.
     */
    public function badgeClass(): string
    {
        return match($this) {
            self::ACTIVE => 'badge-active',
            self::INACTIVE => 'badge-inactive',
            self::SUSPENDED => 'badge-suspended',
            self::BANNED => 'badge-banned',
        };
    }

    /**
     * Get color hex code for status.
     */
    public function color(): string
    {
        return match($this) {
            self::ACTIVE => '#10B981',    // Green
            self::INACTIVE => '#6B7280',  // Gray
            self::SUSPENDED => '#F59E0B', // Amber
            self::BANNED => '#EF4444',    // Red
        };
    }

    /**
     * Check if user can login.
     */
    public function canLogin(): bool
    {
        return match($this) {
            self::ACTIVE, self::INACTIVE => true,
            self::SUSPENDED, self::BANNED => false,
        };
    }

    /**
     * Check if user requires OTP verification on login.
     */
    public function requiresOtpOnLogin(): bool
    {
        return $this === self::INACTIVE;
    }

    /**
     * Check if status is terminal (cannot be changed).
     */
    public function isTerminal(): bool
    {
        return $this === self::BANNED;
    }

    /**
     * Get allowed transitions from this status.
     */
    public function allowedTransitions(): array
    {
        return match($this) {
            self::ACTIVE => [self::INACTIVE, self::SUSPENDED, self::BANNED],
            self::INACTIVE => [self::ACTIVE, self::BANNED],
            self::SUSPENDED => [self::ACTIVE, self::BANNED],
            self::BANNED => [], // Terminal state
        };
    }

    /**
     * Check if transition to target status is allowed.
     */
    public function canTransitionTo(UserStatusEnum $target): bool
    {
        return in_array($target, $this->allowedTransitions());
    }
}
