<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\AdminSession;
use App\Models\User;
use App\Models\UserSession;
use App\Models\SystemSetting;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * Session Service with configurable timeouts.
 * 
 * Implements PCI-DSS and OJK compliant session management:
 * - 15 min idle timeout
 * - 24 hour absolute timeout (users), 12 hour (admins)
 * - Token rotation every 6 hours
 */
class SessionService
{
    private LoggingService $logger;
    
    /**
     * Default timeout configuration (in seconds).
     */
    private const USER_IDLE_TIMEOUT = 900;        // 15 minutes
    private const USER_ABSOLUTE_TIMEOUT = 86400;  // 24 hours
    private const ADMIN_IDLE_TIMEOUT = 900;        // 15 minutes
    private const ADMIN_ABSOLUTE_TIMEOUT = 43200;  // 12 hours
    private const TOKEN_ROTATION_HOURS = 6;
    private const MIN_IDLE_TIMEOUT = 300;          // 5 minutes
    private const MIN_ABSOLUTE_TIMEOUT = 3600;     // 1 hour
    
    public function __construct(LoggingService $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Create a new session for user.
     */
    public function createUserSession(User $user): array
    {
        // Generate secure token
        $token = Str::random(64);
        $tokenHash = hash('sha256', $token);
        $idleTimeout = $this->resolveIdleTimeout();
        $absoluteTimeout = $this->resolveAbsoluteTimeout();
        
        $session = UserSession::create([
            'user_id' => $user->id,
            'token_hash' => $tokenHash,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'idle_timeout' => $idleTimeout,
            'absolute_timeout' => $absoluteTimeout,
        ]);

        // Enforce concurrent session limit (revoke oldest if exceeded).
        $this->enforceSessionLimit($user->id);
        
        // Log login
        $this->logger->logUserLogin($user->id, 'login', [
            'session_id_hash' => substr($tokenHash, 0, 8),
        ]);
        
        return [
            'token' => $token,
            'session' => $session,
        ];
    }

    /**
     * Validate session by token.
     * 
     * @return UserSession|null
     */
    public function validateSession(string $token): ?UserSession
    {
        $session = UserSession::findByToken($token);
        
        if (!$session) {
            return null;
        }

        // Keep active session timeout values aligned with current system settings.
        $this->syncSessionTimeouts($session);
        
        // Check timeouts
        if (!$session->isValid()) {
            $reason = $session->hasIdleTimeoutExpired() 
                ? 'idle_timeout' 
                : 'absolute_timeout';
                
            $session->revoke($reason);
            Log::warning('User session expired', [
                'user_id' => $session->user_id,
                'reason' => $reason,
            ]);
            
            $this->logger->logUserLogin($session->user_id, 'session_expired', [
                'session_id_hash' => substr($session->token_hash, 0, 8),
            ]);
            
            return null;
        }

        // SECURITY: Validate IP and User-Agent binding to prevent token replay
        $enforceIpBinding = (bool) SystemSetting::getValue('security.enforce_session_ip_binding', false);
        $requestIp = request()->ip();
        if ($enforceIpBinding && $session->ip_address && $requestIp && $session->ip_address !== $requestIp) {
            $session->revoke('ip_mismatch');
            Log::warning('User session IP mismatch', [
                'user_id' => $session->user_id,
                'original_ip' => $session->ip_address,
                'current_ip' => $requestIp,
            ]);
            
            $this->logger->logUserLogin($session->user_id, 'session_ip_mismatch', [
                'session_id_hash' => substr($session->token_hash, 0, 8),
                'original_ip' => substr($session->ip_address, 0, -3) . '***',
                'current_ip' => substr($requestIp, 0, -3) . '***',
            ]);
            
            return null;
        }
        
        // Update last activity
        $session->touch();
        
        return $session;
    }

    /**
     * Rotate session token.
     */
    public function rotateToken(UserSession $session): string
    {
        $newToken = Str::random(64);
        $newTokenHash = hash('sha256', $newToken);
        
        $session->rotateToken($newTokenHash);
        
        return $newToken;
    }

    /**
     * Rotate admin session token.
     */
    public function rotateAdminToken(AdminSession $session): string
    {
        $newToken = Str::random(64);
        $newTokenHash = hash('sha256', $newToken);

        $session->rotateToken($newTokenHash);

        return $newToken;
    }

    /**
     * Revoke session (logout).
     */
    public function revokeSession(UserSession $session, string $reason = 'user_logout'): void
    {
        $session->revoke($reason);
        
        $this->logger->logUserLogin($session->user_id, 'logout', [
            'session_id_hash' => substr($session->token_hash, 0, 8),
        ]);
    }

    /**
     * Revoke all sessions for user (forced logout).
     */
    public function revokeAllUserSessions(string $userId, string $reason = 'forced_logout'): int
    {
        $count = UserSession::revokeAllForUser($userId, $reason);
        
        $this->logger->logUserLogin($userId, 'all_sessions_revoked');
        
        return $count;
    }

    /**
     * Get active session count for user.
     */
    public function getActiveSessionCount(string $userId): int
    {
        return UserSession::where('user_id', $userId)
            ->whereNull('revoked_at')
            ->count();
    }

    // ==========================================
    // Admin Session Methods
    // ==========================================

    /**
     * Create a new session for admin.
     */
    public function createAdminSession(Admin $admin): array
    {
        $token = Str::random(64);
        $tokenHash = hash('sha256', $token);
        $idleTimeout = $this->resolveAdminIdleTimeout();
        $absoluteTimeout = $this->resolveAdminAbsoluteTimeout();

        $session = AdminSession::create([
            'admin_id'         => $admin->id,
            'token_hash'       => $tokenHash,
            'ip_address'       => request()->ip(),
            'user_agent'       => request()->userAgent(),
            'idle_timeout'     => $idleTimeout,
            'absolute_timeout' => $absoluteTimeout,
        ]);

        $this->logger->logAdminActivity(
            (string) $admin->id,
            'admin.session_created',
            'AdminSession',
            (string) $session->id,
            null,
            ['session_id_hash' => substr($tokenHash, 0, 8)]
        );

        return [
            'token'   => $token,
            'session' => $session,
        ];
    }

    /**
     * Validate admin session by token.
     */
    public function validateAdminSession(string $token): ?AdminSession
    {
        $session = AdminSession::findByToken($token);

        if (!$session) {
            return null;
        }

        // Sync idle timeout only (absolute_timeout must not change after creation)
        $this->syncAdminSessionTimeouts($session);

        if (!$session->isValid()) {
            $reason = $session->hasIdleTimeoutExpired()
                ? 'idle_timeout'
                : 'absolute_timeout';

            $session->revoke($reason);
            Log::warning('Admin session expired', [
                'admin_id' => $session->admin_id,
                'reason'   => $reason,
            ]);

            return null;
        }

        // IP binding check
        $enforceIpBinding = (bool) SystemSetting::getValue('security.enforce_session_ip_binding', false);
        $requestIp = request()->ip();
        if ($enforceIpBinding && $session->ip_address && $requestIp && $session->ip_address !== $requestIp) {
            $session->revoke('ip_mismatch');
            Log::warning('Admin session IP mismatch', [
                'admin_id'     => $session->admin_id,
                'original_ip'  => $session->ip_address,
                'current_ip'   => $requestIp,
            ]);
            return null;
        }

        $session->touch();

        return $session;
    }

    /**
     * Revoke admin session (logout).
     */
    public function revokeAdminSession(AdminSession $session, string $reason = 'admin_logout'): void
    {
        $session->revoke($reason);

        $this->logger->logAdminActivity(
            (string) $session->admin_id,
            'admin.logout',
            'AdminSession',
            (string) $session->id,
            null,
            ['session_id_hash' => substr($session->token_hash, 0, 8)]
        );
    }

    /**
     * Revoke all admin sessions (forced logout).
     */
    public function revokeAllAdminSessions(string $adminId, string $reason = 'forced_logout'): int
    {
        return AdminSession::revokeAllForAdmin($adminId, $reason);
    }

    /**
     * Revoke oldest sessions if limit exceeded.
     */
    public function enforceSessionLimit(string $userId, int $maxSessions = 5): void
    {
        $limit = (int) SystemSetting::getValue('security.max_concurrent_sessions', $maxSessions);
        $limit = max(1, $limit);
        
        $sessions = UserSession::where('user_id', $userId)
            ->whereNull('revoked_at')
            ->orderBy('created_at', 'asc')
            ->get();
            
        if ($sessions->count() > $limit) {
            $toRevoke = $sessions->take($sessions->count() - $limit);
            
            foreach ($toRevoke as $session) {
                $session->revoke('session_limit_exceeded');
            }
        }
    }

    /**
     * Convert absolute timeout (seconds) into cookie lifetime (minutes).
     */
    public function resolveCookieLifetimeMinutes(?int $absoluteTimeout = null): int
    {
        $timeout = $absoluteTimeout ?? $this->resolveAbsoluteTimeout();

        return max(1, (int) ceil($timeout / 60));
    }

    /**
     * Resolve user idle timeout with defensive minimum.
     */
    private function resolveIdleTimeout(): int
    {
        $timeout = (int) SystemSetting::getValue('security.session_idle_timeout', self::USER_IDLE_TIMEOUT);

        return max($timeout, self::MIN_IDLE_TIMEOUT);
    }

    /**
     * Resolve user absolute timeout with defensive minimum.
     */
    private function resolveAbsoluteTimeout(): int
    {
        $timeout = (int) SystemSetting::getValue('security.session_absolute_timeout', self::USER_ABSOLUTE_TIMEOUT);

        return max($timeout, self::MIN_ABSOLUTE_TIMEOUT);
    }

    /**
     * Resolve admin idle timeout.
     * Admins share the same idle timeout setting as users.
     */
    private function resolveAdminIdleTimeout(): int
    {
        $timeout = (int) SystemSetting::getValue('security.session_idle_timeout', self::ADMIN_IDLE_TIMEOUT);

        return max($timeout, self::MIN_IDLE_TIMEOUT);
    }

    /**
     * Resolve admin absolute timeout (defaults to 12 hours, stricter than users).
     */
    private function resolveAdminAbsoluteTimeout(): int
    {
        $timeout = (int) SystemSetting::getValue('security.admin_session_absolute_timeout', self::ADMIN_ABSOLUTE_TIMEOUT);

        return max($timeout, self::MIN_ABSOLUTE_TIMEOUT);
    }

    /**
     * Sync idle timeout with current settings for a user session.
     * 
     * NOTE: absolute_timeout is intentionally NOT synced here.
     * The absolute timeout timer starts at session creation (created_at) and
     * must never be extended for an already-active session, otherwise an admin
     * increasing the global setting would silently extend existing live sessions.
     */
    private function syncSessionTimeouts(UserSession $session): void
    {
        $idleTimeout = $this->resolveIdleTimeout();

        if ($session->idle_timeout === $idleTimeout) {
            return;
        }

        $session->update([
            'idle_timeout' => $idleTimeout,
        ]);
    }

    /**
     * Sync idle timeout with current settings for an admin session.
     * Same rule applies: absolute_timeout is NOT synced after creation.
     */
    private function syncAdminSessionTimeouts(AdminSession $session): void
    {
        $idleTimeout = $this->resolveAdminIdleTimeout();

        if ($session->idle_timeout === $idleTimeout) {
            return;
        }

        $session->update([
            'idle_timeout' => $idleTimeout,
        ]);
    }
}
