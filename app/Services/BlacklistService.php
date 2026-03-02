<?php

namespace App\Services;

use App\Enums\BlacklistTypeEnum;
use App\Models\RegistrationBlacklist;

/**
 * Service to check registration blacklist.
 */
class BlacklistService
{
    /**
     * Check if an email or its domain is blocked.
     */
    public function isEmailBlocked(string $email): ?RegistrationBlacklist
    {
        $email = strtolower(trim($email));
        $domain = substr(strrchr($email, '@'), 1);

        // Check exact email match
        $blocked = RegistrationBlacklist::active()
            ->byType(BlacklistTypeEnum::EMAIL)
            ->byValue($email)
            ->first();

        if ($blocked) {
            return $blocked;
        }

        // Check domain match
        if ($domain) {
            return RegistrationBlacklist::active()
                ->byType(BlacklistTypeEnum::DOMAIN)
                ->byValue($domain)
                ->first();
        }

        return null;
    }

    /**
     * Check if a phone number is blocked.
     */
    public function isPhoneBlocked(string $phone): ?RegistrationBlacklist
    {
        // Normalize phone number (remove spaces, dashes, etc.)
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        return RegistrationBlacklist::active()
            ->byType(BlacklistTypeEnum::PHONE)
            ->byValue($phone)
            ->first();
    }

    /**
     * Check if an IP address is blocked.
     */
    public function isIpBlocked(string $ip): ?RegistrationBlacklist
    {
        return RegistrationBlacklist::active()
            ->byType(BlacklistTypeEnum::IP)
            ->byValue($ip)
            ->first();
    }

    /**
     * Check all registration parameters.
     * Returns error message if blocked, null if allowed.
     */
    public function checkRegistration(
        string $email,
        ?string $phone = null,
        ?string $ip = null
    ): ?string {
        // Check email/domain
        $blocked = $this->isEmailBlocked($email);
        if ($blocked) {
            return $this->formatBlockMessage($blocked, 'Email');
        }

        // Check phone if provided
        if ($phone) {
            $blocked = $this->isPhoneBlocked($phone);
            if ($blocked) {
                return $this->formatBlockMessage($blocked, 'Nomor HP');
            }
        }

        // Check IP if provided
        if ($ip) {
            $blocked = $this->isIpBlocked($ip);
            if ($blocked) {
                return $this->formatBlockMessage($blocked, 'Alamat IP');
            }
        }

        return null;
    }

    /**
     * Format block message for user.
     */
    private function formatBlockMessage(RegistrationBlacklist $blocked, string $field): string
    {
        $message = "{$field} Anda tidak dapat digunakan untuk pendaftaran.";
        
        if ($blocked->reason) {
            $message .= " Alasan: {$blocked->reason}";
        }

        return $message;
    }

    /**
     * Add entry to blacklist.
     */
    public function addToBlacklist(
        BlacklistTypeEnum $type,
        string $value,
        ?string $reason = null,
        ?int $adminId = null,
        ?\DateTime $expiresAt = null
    ): RegistrationBlacklist {
        return RegistrationBlacklist::create([
            'type' => $type,
            'value' => strtolower(trim($value)),
            'reason' => $reason,
            'created_by' => $adminId,
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Remove entry from blacklist.
     */
    public function removeFromBlacklist(int $id): bool
    {
        return RegistrationBlacklist::destroy($id) > 0;
    }
}
