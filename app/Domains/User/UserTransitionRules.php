<?php

namespace App\Domains\User;

use App\Enums\UserStatusEnum;

/**
 * User Transition Rules
 * 
 * Defines allowed state transitions for users.
 * Based on bank-grade security requirements.
 */
class UserTransitionRules
{
    /**
     * Allowed transitions map.
     * Key: current status, Value: array of allowed target statuses
     */
    private const TRANSITIONS = [
        'active' => ['inactive', 'suspended', 'banned'],
        'inactive' => ['active', 'banned'],
        'suspended' => ['active', 'banned'],
        'banned' => [], // Terminal state - no escape
    ];

    /**
     * Transitions that require admin.
     */
    private const ADMIN_ONLY_TRANSITIONS = [
        'active' => ['suspended', 'banned'],
        'inactive' => ['banned'],
        'suspended' => ['active', 'banned'],
    ];

    /**
     * Transitions that can be done by system.
     */
    private const SYSTEM_TRANSITIONS = [
        'active' => ['inactive'],
        'inactive' => ['active'],
    ];

    /**
     * Check if transition is allowed.
     */
    public static function canTransition(UserStatusEnum $from, UserStatusEnum $to): bool
    {
        $allowed = self::TRANSITIONS[$from->value] ?? [];
        return in_array($to->value, $allowed);
    }

    /**
     * Check if transition requires admin.
     */
    public static function requiresAdmin(UserStatusEnum $from, UserStatusEnum $to): bool
    {
        $adminOnly = self::ADMIN_ONLY_TRANSITIONS[$from->value] ?? [];
        return in_array($to->value, $adminOnly);
    }

    /**
     * Check if transition can be done by system.
     */
    public static function isSystemTransition(UserStatusEnum $from, UserStatusEnum $to): bool
    {
        $systemAllowed = self::SYSTEM_TRANSITIONS[$from->value] ?? [];
        return in_array($to->value, $systemAllowed);
    }

    /**
     * Get allowed transitions for a status.
     */
    public static function getAllowedTransitions(UserStatusEnum $from): array
    {
        $statuses = self::TRANSITIONS[$from->value] ?? [];
        return array_map(fn($s) => UserStatusEnum::from($s), $statuses);
    }

    /**
     * Check if status is terminal.
     */
    public static function isTerminal(UserStatusEnum $status): bool
    {
        return empty(self::TRANSITIONS[$status->value] ?? []);
    }
}
