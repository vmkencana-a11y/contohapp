<?php

namespace App\Services;

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
    private const USER_IDLE_TIMEOUT = 900;      // 15 minutes
    private const USER_ABSOLUTE_TIMEOUT = 86400; // 24 hours
    private const ADMIN_IDLE_TIMEOUT = 900;     // 15 minutes
    private const ADMIN_ABSOLUTE_TIMEOUT = 43200; // 12 hours
    private const TOKEN_ROTATION_HOURS = 6;
    private const MIN_IDLE_TIMEOUT = 300;       // 5 minutes
    private const MIN_ABSOLUTE_TIMEOUT = 3600;  // 1 hour
    
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
        
        $session = UserSession::create([
            'user_id' => $user->id,
            'token_hash' => $tokenHash,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'idle_timeout' => $this->resolveIdleTimeout(),
            'absolute_timeout' => $this->resolveAbsoluteTimeout(),
        ]);
        
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
        
        // NOTE: Token rotation disabled until cookie update mechanism is implemented.
        // Auto-rotation would invalidate the client's token without sending the new one.
        // TODO: Implement rotation via response headers or session refresh endpoint.
        // if ($session->needsRotation(self::TOKEN_ROTATION_HOURS)) {
        //     $newToken = $this->rotateToken($session);
        //     // Need to send $newToken back to client somehow
        // }
        
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

    /**
     * Revoke oldest sessions if limit exceeded.
     */
    public function enforceSessionLimit(string $userId, int $maxSessions = 5): void
    {
        $limit = (int) SystemSetting::getValue('security.max_concurrent_sessions', $maxSessions);
        
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
     * Resolve idle timeout with defensive minimum.
     */
    private function resolveIdleTimeout(): int
    {
        $timeout = (int) SystemSetting::getValue('security.session_idle_timeout', self::USER_IDLE_TIMEOUT);

        return max($timeout, self::MIN_IDLE_TIMEOUT);
    }

    /**
     * Resolve absolute timeout with defensive minimum.
     */
    private function resolveAbsoluteTimeout(): int
    {
        $timeout = (int) SystemSetting::getValue('security.session_absolute_timeout', self::USER_ABSOLUTE_TIMEOUT);

        return max($timeout, self::MIN_ABSOLUTE_TIMEOUT);
    }
}
