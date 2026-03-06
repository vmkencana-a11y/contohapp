<?php

namespace App\Services;

use App\Models\Logs\AdminActivityLog;
use App\Models\Logs\SecurityEventLog;
use App\Models\Logs\UserLoginLog;
use App\Models\Logs\UserStatusLog;

/**
 * Domain-based logging service with metadata sanitization.
 * 
 * SECURITY: Implements strict whitelist for metadata fields
 * to prevent PII leakage into logs.
 */
class LoggingService
{

    /**
     * Log user login activity.
     */
    public function logUserLogin(
        string $userId,
        string $action,
        array $metadata = []
    ): void {
        UserLoginLog::create([
            'user_id' => $userId,
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $this->sanitizeMetadata('user_login', $metadata),
        ]);
    }

    /**
     * Log user status change.
     */
    public function logUserStatusChange(
        string $userId,
        string $action,
        string $changedByType,
        ?string $changedById,
        ?string $oldStatus,
        string $newStatus,
        ?string $reason = null,
        array $metadata = []
    ): void {
        UserStatusLog::create([
            'user_id' => $userId,
            'action' => $action,
            'changed_by_type' => $changedByType,
            'changed_by_id' => $changedById,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'reason' => $reason,
            'metadata' => $this->sanitizeMetadata('user_status', $metadata),
        ]);
    }

    /**
     * Log admin activity.
     */
    public function logAdminActivity(
        int $adminId,
        string $action,
        string $subjectType,
        ?string $subjectId = null,
        ?string $reason = null,
        array $metadata = []
    ): void {
        AdminActivityLog::create([
            'admin_id' => $adminId,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'reason' => $reason,
            'metadata' => $this->sanitizeMetadata('admin_activity', $metadata),
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Log security event.
     */
    public function logSecurityEvent(
        string $eventType,
        ?string $actorIdentifier = null,
        string $severity = 'medium',
        array $metadata = []
    ): void {
        SecurityEventLog::create([
            'event_type' => $eventType,
            'actor_identifier' => $actorIdentifier,
            'ip_address' => request()->ip(),
            'metadata' => $this->sanitizeMetadata('security_event', $metadata),
            'severity' => $severity,
        ]);
    }

    /**
     * Sanitize metadata using strict whitelist.
     * 
     * SECURITY: Only allows pre-approved keys to prevent PII leakage.
     */
    private function sanitizeMetadata(string $domain, array $metadata): ?array
    {
        if (empty($metadata)) {
            return null;
        }

        $sanitized = [];

        foreach ($metadata as $key => $value) {
            if (!is_string($key) || $key === '') {
                continue;
            }

            $sanitizedValue = $this->sanitizeMetadataValue($key, $value);
            if ($sanitizedValue !== null) {
                $sanitized[$key] = $sanitizedValue;
            }
        }

        return !empty($sanitized) ? $sanitized : null;
    }

    private function sanitizeMetadataValue(string $key, mixed $value): mixed
    {
        if (is_array($value)) {
            if (!$this->isSimpleArray($value)) {
                return null;
            }

            $sanitized = [];
            foreach ($value as $item) {
                $sanitizedItem = $this->sanitizeScalarMetadata($key, $item);
                if ($sanitizedItem !== null) {
                    $sanitized[] = $sanitizedItem;
                }
            }

            return $sanitized !== [] ? $sanitized : null;
        }

        if (!is_scalar($value) && $value !== null) {
            return null;
        }

        return $this->sanitizeScalarMetadata($key, $value);
    }

    private function sanitizeScalarMetadata(string $key, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $normalizedKey = strtolower($key);
        $stringValue = trim((string) $value);

        if ($stringValue === '' && !is_numeric($value) && !is_bool($value)) {
            return null;
        }

        if (in_array($normalizedKey, ['admin_name', 'name', 'google_name'], true)) {
            return null;
        }

        if (in_array($normalizedKey, ['ip', 'ip_address', 'original_ip', 'current_ip'], true)) {
            return $this->maskIp($stringValue);
        }

        if ($normalizedKey === 'user_agent') {
            return $this->hashValue($stringValue);
        }

        if (in_array($normalizedKey, ['email', 'google_email', 'google_sub'], true)) {
            return $this->hashValue($stringValue);
        }

        if (
            (str_contains($normalizedKey, 'token') || str_contains($normalizedKey, 'state') || str_contains($normalizedKey, 'verifier') || str_contains($normalizedKey, 'secret'))
            && !str_contains($normalizedKey, 'hash')
        ) {
            return $this->hashValue($stringValue);
        }

        if (is_bool($value) || is_int($value) || is_float($value)) {
            return $value;
        }

        return mb_substr($stringValue, 0, 255);
    }

    /**
     * Check if array contains only scalar values (no nested objects).
     */
    private function isSimpleArray(array $arr): bool
    {
        foreach ($arr as $value) {
            if (!is_scalar($value)) {
                return false;
            }
        }
        return true;
    }

    private function hashValue(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        return substr(hash('sha256', $value), 0, 16);
    }

    private function maskIp(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        if (str_contains($value, '*')) {
            return mb_substr($value, 0, 64);
        }

        if (filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $value);
            $parts[3] = 'x';

            return implode('.', $parts);
        }

        if (filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $value);

            return implode(':', array_slice($parts, 0, 4)) . ':*';
        }

        return $this->hashValue($value);
    }
}
