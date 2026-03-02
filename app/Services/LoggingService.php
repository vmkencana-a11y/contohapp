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
     * Whitelist of allowed metadata keys per domain.
     */
    private const ALLOWED_METADATA = [
        'user_login' => [
            'device_type',
            'browser',
            'login_method',
            'session_id_hash',
        ],
        'user_status' => [
            'previous_status',
            'trigger_type',
            'related_entity_type',
            'related_entity_id',
        ],
        'admin_activity' => [
            'action_category',
            'affected_count',
            'changes_summary',
        ],
        'security_event' => [
            'event_category',
            'threshold_reached',
            'detection_method',
            'endpoint',
        ],
    ];

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

        $allowedKeys = self::ALLOWED_METADATA[$domain] ?? [];
        $sanitized = [];

        foreach ($allowedKeys as $key) {
            if (isset($metadata[$key])) {
                // Ensure value is scalar or simple array
                $value = $metadata[$key];
                if (is_scalar($value) || (is_array($value) && $this->isSimpleArray($value))) {
                    $sanitized[$key] = $value;
                }
            }
        }

        return !empty($sanitized) ? $sanitized : null;
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
}
