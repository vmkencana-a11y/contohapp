<?php

namespace App\Actions\Auth;

use App\Exceptions\InvalidOtpException;
use App\Exceptions\OtpExpiredException;
use App\Exceptions\OtpNotFoundException;
use App\Exceptions\RateLimitExceededException;
use App\Models\PreUserOtp;
use App\Services\EncryptionService;
use App\Services\LoggingService;
use App\Services\OtpService;

/**
 * Verify OTP Action
 * 
 * Single-purpose action class for verifying OTP.
 * Handles brute force protection and constant-time comparison.
 */
class VerifyOtpAction
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 60;

    public function __construct(
        private OtpService $otpService,
        private EncryptionService $encryption,
        private LoggingService $logger
    ) {}

    /**
     * Execute the action.
     * 
     * @throws OtpNotFoundException
     * @throws OtpExpiredException
     * @throws InvalidOtpException
     * @throws RateLimitExceededException
     */
    public function execute(string $email, string $otp): PreUserOtp
    {
        $emailHash = $this->encryption->hash($email);

        // Get latest OTP record
        $record = PreUserOtp::where('email_hash', $emailHash)
            ->whereNull('verified_at')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$record) {
            throw new OtpNotFoundException();
        }

        // Check brute force lock
        if ($record->isLocked()) {
            $this->logger->logSecurityEvent('otp_brute_force_locked', $emailHash, [
                'locked_until' => $record->locked_until,
            ], 'high');

            throw new RateLimitExceededException(
                'Akun terkunci sementara karena terlalu banyak percobaan gagal.',
                $record->locked_until->diffInSeconds(now())
            );
        }

        // Check expiry
        if ($record->isExpired()) {
            throw new OtpExpiredException();
        }

        // Increment attempt before verification
        $record->incrementAttempts();

        // Verify OTP with constant-time comparison
        $otpHash = $this->otpService->hashOtp($otp);
        
        if (!$this->encryption->hashEquals($record->otp_hash, $otpHash)) {
            // Check if should lock
            if ($record->hasExceededMaxAttempts(self::MAX_ATTEMPTS)) {
                $record->lock(self::LOCKOUT_MINUTES);
                
                $this->logger->logSecurityEvent('otp_max_attempts_reached', $emailHash, [
                    'attempts' => $record->attempt_count,
                ], 'high');
            }

            $remaining = max(0, self::MAX_ATTEMPTS - $record->attempt_count);
            throw new InvalidOtpException('OTP tidak valid.', $remaining);
        }

        // Success - mark as verified
        $record->markVerified();

        return $record;
    }
}
