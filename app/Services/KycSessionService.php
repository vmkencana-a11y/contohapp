<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

/**
 * KYC Session & Nonce Management Service.
 * 
 * Handles:
 * - KYC capture session lifecycle (TTL: 10 min)
 * - Frame nonce generation & validation (TTL: 10 sec, single-use)
 * - Anti-replay protection via Redis
 */
class KycSessionService
{
    /**
     * Session TTL in seconds (10 minutes).
     */
    private const SESSION_TTL = 600;

    /**
     * Nonce TTL in seconds (10 seconds).
     */
    private const NONCE_TTL = 10;

    /**
     * Max frames per session.
     */
    private const MAX_FRAMES = 5;

    /**
     * Redis key prefixes.
     */
    private const SESSION_PREFIX = 'kyc:session:';
    private const NONCE_PREFIX = 'kyc:nonce:';

    /**
     * Start a new KYC capture session.
     * 
     * @return array{session_id: string, expires_at: int}
     */
    public function startSession(string $userId): array
    {
        // Generate unique session ID
        $sessionId = Str::uuid()->toString();
        $expiresAt = now()->addSeconds(self::SESSION_TTL)->timestamp;

        // Store session data in Redis
        Redis::setex(
            $this->sessionKey($sessionId),
            self::SESSION_TTL,
            json_encode([
                'user_id' => $userId,
                'started_at' => now()->timestamp,
                'expires_at' => $expiresAt,
                'frame_count' => 0,
                'challenges_completed' => [],
                'status' => 'active',
            ])
        );

        return [
            'session_id' => $sessionId,
            'expires_at' => $expiresAt,
            'max_frames' => self::MAX_FRAMES,
        ];
    }

    /**
     * Validate that session exists and belongs to user.
     */
    public function validateSession(string $sessionId, string $userId): bool
    {
        $data = $this->getSessionData($sessionId);
        
        if (!$data) {
            return false;
        }

        return $data['user_id'] === $userId && $data['status'] === 'active';
    }

    /**
     * Get session data.
     */
    public function getSessionData(string $sessionId): ?array
    {
        $data = Redis::get($this->sessionKey($sessionId));
        
        if (!$data) {
            return null;
        }

        return json_decode($data, true);
    }

    /**
     * Generate a single-use frame nonce.
     * 
     * @return array{nonce: string, expires_at: int}
     */
    public function generateNonce(string $sessionId): array
    {
        $session = $this->getSessionData($sessionId);
        
        if (!$session) {
            throw new \RuntimeException('Session not found');
        }

        if ($session['frame_count'] >= self::MAX_FRAMES) {
            throw new \RuntimeException('Max frames reached');
        }

        // Generate cryptographically secure nonce (256-bit)
        $nonce = bin2hex(random_bytes(32));
        $expiresAt = now()->addSeconds(self::NONCE_TTL)->timestamp;

        // Store nonce in Redis (bound to session)
        Redis::setex(
            $this->nonceKey($sessionId, $nonce),
            self::NONCE_TTL,
            json_encode([
                'session_id' => $sessionId,
                'created_at' => now()->timestamp,
                'expires_at' => $expiresAt,
                'consumed' => false,
            ])
        );

        return [
            'nonce' => $nonce,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Validate and consume a nonce (single-use).
     * 
     * @return bool True if valid and consumed
     */
    public function consumeNonce(string $sessionId, string $nonce): bool
    {
        $key = $this->nonceKey($sessionId, $nonce);
        $sessionKey = $this->sessionKey($sessionId);

        // Atomic: validate nonce + delete + increment frame count in one round-trip
        $luaScript = <<<'LUA'
            local nonceData = redis.call('GET', KEYS[1])
            if not nonceData then return 0 end
            
            local data = cjson.decode(nonceData)
            if data['consumed'] then return 0 end
            if data['session_id'] ~= ARGV[1] then return 0 end
            
            -- Delete nonce immediately (single-use)
            redis.call('DEL', KEYS[1])
            
            -- Atomic increment frame count in session
            local sessionData = redis.call('GET', KEYS[2])
            if sessionData then
                local session = cjson.decode(sessionData)
                session['frame_count'] = (session['frame_count'] or 0) + 1
                local ttl = redis.call('TTL', KEYS[2])
                if ttl > 0 then
                    redis.call('SETEX', KEYS[2], ttl, cjson.encode(session))
                end
            end
            
            return 1
        LUA;

        return (bool) Redis::eval($luaScript, 2, $key, $sessionKey, $sessionId);
    }

    /**
     * Complete session (mark as ready for processing).
     */
    public function completeSession(string $sessionId): bool
    {
        $session = $this->getSessionData($sessionId);
        
        if (!$session) {
            return false;
        }

        $session['status'] = 'completed';
        $session['completed_at'] = now()->timestamp;

        // Keep session data for a while for reference
        Redis::setex(
            $this->sessionKey($sessionId),
            300, // 5 minutes after completion
            json_encode($session)
        );

        return true;
    }

    /**
     * Store a captured frame path in session (server-side only).
     */
    public function storeFramePath(string $sessionId, string $type, string $filePath): void
    {
        $key = $this->sessionKey($sessionId);

        $luaScript = <<<'LUA'
            local data = redis.call('GET', KEYS[1])
            if not data then return 0 end
            
            local session = cjson.decode(data)
            if not session['frame_paths'] then
                session['frame_paths'] = {}
            end
            session['frame_paths'][ARGV[1]] = ARGV[2]
            
            local ttl = redis.call('TTL', KEYS[1])
            if ttl > 0 then
                redis.call('SETEX', KEYS[1], ttl, cjson.encode(session))
            end
            return 1
        LUA;

        Redis::eval($luaScript, 1, $key, $type, $filePath);
    }

    /**
     * Get all stored frame paths from session.
     *
     * @return array<string, string> ['selfie' => 'path', 'id_card' => 'path', ...]
     */
    public function getFramePaths(string $sessionId): array
    {
        $session = $this->getSessionData($sessionId);

        if (!$session || empty($session['frame_paths'])) {
            return [];
        }

        return $session['frame_paths'];
    }

    /**
     * End/invalidate session.
     */
    public function endSession(string $sessionId): void
    {
        Redis::del($this->sessionKey($sessionId));
    }

    /**
     * Get session key.
     */
    private function sessionKey(string $sessionId): string
    {
        return self::SESSION_PREFIX . $sessionId;
    }

    /**
     * Get nonce key.
     */
    private function nonceKey(string $sessionId, string $nonce): string
    {
        return self::NONCE_PREFIX . $sessionId . ':' . $nonce;
    }
}
