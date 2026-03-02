<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * ID Generator for User IDs.
 * 
 * Format: IDxxxxxxxx (10 characters total)
 * - Prefix: "ID"
 * - Suffix: 8 random digits (0-9)
 * 
 * Example: ID91203678
 * 
 * Benefits:
 * - Anti-enumeration (can't guess next ID)
 * - Human-readable
 * - Audit-friendly
 */
class IdGenerator
{
    private const PREFIX = 'ID';
    private const SUFFIX_LENGTH = 8;
    private const TOTAL_LENGTH = 10;

    /**
     * Generate a new user ID.
     */
    public static function generate(): string
    {
        $suffix = '';
        for ($i = 0; $i < self::SUFFIX_LENGTH; $i++) {
            $suffix .= random_int(0, 9);
        }
        return self::PREFIX . $suffix;
    }

    /**
     * Validate ID format.
     */
    public static function isValid(string $id): bool
    {
        if (strlen($id) !== self::TOTAL_LENGTH) {
            return false;
        }

        if (!str_starts_with($id, self::PREFIX)) {
            return false;
        }

        $suffix = substr($id, 2);
        return ctype_digit($suffix);
    }

    /**
     * Generate unique ID by checking database.
     * 
     * @param callable $existsChecker Function that returns true if ID exists
     * @param int $maxAttempts Maximum generation attempts
     */
    public static function generateUnique(callable $existsChecker, int $maxAttempts = 10): string
    {
        for ($i = 0; $i < $maxAttempts; $i++) {
            $id = self::generate();
            if (!$existsChecker($id)) {
                return $id;
            }
        }

        throw new \RuntimeException('Failed to generate unique ID after ' . $maxAttempts . ' attempts');
    }
}
