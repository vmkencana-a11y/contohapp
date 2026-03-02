<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;

/**
 * Encryption Service
 * 
 * Wrapper for Laravel Crypt with consistent interface.
 * Uses AES-256-GCM encryption.
 */
class EncryptionService
{
    /**
     * Encrypt a string value.
     */
    public function encrypt(string $value): string
    {
        return Crypt::encryptString($value);
    }

    /**
     * Decrypt an encrypted string.
     */
    public function decrypt(string $encrypted): string
    {
        return Crypt::decryptString($encrypted);
    }

    /**
     * Create SHA-256 hash for indexing/lookup.
     */
    public function hash(string $value): string
    {
        return hash('sha256', strtolower($value));
    }

    /**
     * Create salted hash (for OTP storage).
     */
    public function hashWithSalt(string $value, string $salt): string
    {
        return hash('sha256', $value . $salt);
    }

    /**
     * Constant-time string comparison.
     */
    public function hashEquals(string $known, string $user): bool
    {
        return hash_equals($known, $user);
    }

    /**
     * Safely decrypt with null fallback.
     */
    public function decryptOrNull(?string $encrypted): ?string
    {
        if (empty($encrypted)) {
            return null;
        }

        try {
            return $this->decrypt($encrypted);
        } catch (\Exception $e) {
            return null;
        }
    }
}
