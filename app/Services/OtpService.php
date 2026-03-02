<?php

namespace App\Services;

use App\Models\PreUserOtp;
use App\Models\User;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * OTP Service with dual-layer rate limiting.
 * 
 * SECURITY: Implements both Redis (primary) and DB (fallback) rate limiting
 * to prevent OTP abuse and brute force attacks.
 */
class OtpService
{
    private LoggingService $logger;
    
    /**
     * Rate limit configuration.
     */
    private const OTP_TTL_MINUTES = 5;
    private const MAX_REQUESTS_PER_HOUR = 5;
    private const MAX_OTP_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 60;
    
    public function __construct(LoggingService $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Generate a 6-digit OTP.
     */
    public function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Hash OTP using SHA-256.
     */
    public function hashOtp(string $otp): string
    {
        return hash('sha256', $otp);
    }

    /**
     * Request OTP for login.
     * 
     * @throws \Exception If rate limited
     */
    public function requestLoginOtp(string $email): string
    {
        $emailHash = hash('sha256', strtolower($email));
        
        // Check rate limit (dual layer)
        if ($this->isRateLimited($emailHash)) {
            $this->logger->logSecurityEvent(
                'otp_rate_limit',
                $emailHash,
                'medium',
                ['event_category' => 'rate_limit', 'endpoint' => 'login']
            );
            throw new \Exception('Terlalu banyak permintaan OTP. Silakan coba lagi nanti.');
        }
        
        // Generate and store OTP
        $otp = $this->generateOtp();
        $otpHash = $this->hashOtp($otp);
        
        PreUserOtp::create([
            'email' => $email,
            'otp_hash' => $otpHash,
            'expired_at' => now()->addMinutes((int) SystemSetting::getValue('security.otp_ttl_minutes', self::OTP_TTL_MINUTES)),
        ]);
        
        // Track request for rate limiting
        $this->trackRequest($emailHash);
        
        return $otp;
    }

    /**
     * Request OTP for registration.
     * 
     * @throws \Exception If rate limited
     */
    public function requestRegistrationOtp(
        string $email,
        string $name,
        ?string $referralCode = null
    ): string {
        $emailHash = hash('sha256', strtolower($email));
        
        // Check if email already registered
        if (User::findByEmail($email)) {
            throw new \Exception('Email sudah terdaftar. Silakan login.');
        }
        
        // Check rate limit
        if ($this->isRateLimited($emailHash)) {
            $this->logger->logSecurityEvent(
                'otp_rate_limit',
                $emailHash,
                'medium',
                ['event_category' => 'rate_limit', 'endpoint' => 'register']
            );
            throw new \Exception('Terlalu banyak permintaan OTP. Silakan coba lagi nanti.');
        }
        
        // Generate and store OTP
        $otp = $this->generateOtp();
        $otpHash = $this->hashOtp($otp);
        
        PreUserOtp::create([
            'email' => $email,
            'name' => $name,
            'referral_code' => $referralCode,
            'otp_hash' => $otpHash,
            'expired_at' => now()->addMinutes((int) SystemSetting::getValue('security.otp_ttl_minutes', self::OTP_TTL_MINUTES)),
        ]);
        
        $this->trackRequest($emailHash);
        
        return $otp;
    }

    /**
     * Verify OTP.
     * 
     * Uses atomic DB operations to prevent race conditions.
     * 
     * @throws \Exception If OTP invalid, expired, or locked
     */
    public function verifyOtp(string $email, string $otp): PreUserOtp
    {
        $emailHash = hash('sha256', strtolower($email));
        $otpHash = $this->hashOtp($otp);
        
        return DB::transaction(function () use ($emailHash, $otpHash) {
            // Lock record for update (prevents race condition)
            $record = PreUserOtp::where('email_hash', $emailHash)
                ->whereNull('verified_at')
                ->orderBy('created_at', 'desc')
                ->lockForUpdate()
                ->first();
            
            if (!$record) {
                throw new \Exception('OTP tidak ditemukan. Silakan minta OTP baru.');
            }
            
            // Check if locked
            if ($record->isLocked()) {
                $this->logger->logSecurityEvent(
                    'otp_brute_force_blocked',
                    $emailHash,
                    'high',
                    ['event_category' => 'brute_force']
                );
                throw new \Exception('Akun terkunci sementara. Silakan coba lagi nanti.');
            }
            
            // Check expiry
            if ($record->isExpired()) {
                throw new \Exception('OTP sudah kadaluarsa. Silakan minta OTP baru.');
            }
            
            // Verify OTP hash
            if ($record->otp_hash !== $otpHash) {
                $record->incrementAttempts();
                
                // Lock if exceeded attempts
                if ($record->hasExceededMaxAttempts((int) SystemSetting::getValue('security.max_otp_attempts', self::MAX_OTP_ATTEMPTS))) {
                    $record->lock(self::LOCKOUT_MINUTES);
                    
                    $this->logger->logSecurityEvent(
                        'otp_lockout',
                        $emailHash,
                        'high',
                        ['event_category' => 'brute_force', 'threshold_reached' => true]
                    );
                }
                
                $remaining = self::MAX_OTP_ATTEMPTS - $record->attempt_count;
                throw new \Exception("OTP tidak valid. Sisa percobaan: {$remaining}");
            }
            
            // Mark as verified
            $record->markVerified();
            
            return $record;
        });
    }

    /**
     * Check if rate limited (dual layer: Redis + DB).
     */
    public function isRateLimited(string $emailHash): bool
    {
        $cacheKey = "otp_rate:{$emailHash}";
        
        // Try Redis first
        try {
            $count = Cache::get($cacheKey, 0);
            return $count >= (int) SystemSetting::getValue('security.max_otp_requests_per_hour', self::MAX_REQUESTS_PER_HOUR);
        } catch (\Exception $e) {
            // Fallback to DB counting
            $hourAgo = now()->subHour();
            $count = PreUserOtp::where('email_hash', $emailHash)
                ->where('created_at', '>=', $hourAgo)
                ->count();
                
            return $count >= (int) SystemSetting::getValue('security.max_otp_requests_per_hour', self::MAX_REQUESTS_PER_HOUR);
        }
    }

    /**
     * Track OTP request for rate limiting.
     */
    public function trackRequest(string $emailHash): void
    {
        $cacheKey = "otp_rate:{$emailHash}";
        
        try {
            $count = Cache::get($cacheKey, 0);
            Cache::put($cacheKey, $count + 1, now()->addHour());
        } catch (\Exception $e) {
            // Silently fail - DB count will be used as fallback
        }
    }

    /**
     * Get cooldown remaining in seconds.
     */
    public function getCooldownRemaining(string $emailHash): int
    {
        $cacheKey = "otp_cooldown:{$emailHash}";
        
        try {
            $expiry = Cache::get($cacheKey);
            if ($expiry && now()->lt($expiry)) {
                return now()->diffInSeconds($expiry);
            }
        } catch (\Exception $e) {
            // Ignore cache errors
        }
        
        return 0;
    }

    /**
     * Set cooldown for OTP requests (60 seconds).
     */
    public function setCooldown(string $emailHash, ?int $seconds = null): void
    {
        $timeout = $seconds ?? (int) SystemSetting::getValue('security.otp_cooldown', 60);
        $cacheKey = "otp_cooldown:{$emailHash}";
        
        try {
            Cache::put($cacheKey, now()->addSeconds($timeout), $timeout);
        } catch (\Exception $e) {
            // Ignore cache errors
        }
    }
}
