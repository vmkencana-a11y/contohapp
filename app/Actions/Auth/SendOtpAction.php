<?php

namespace App\Actions\Auth;

use App\Exceptions\CooldownActiveException;
use App\Exceptions\RateLimitExceededException;
use App\Models\PreUserOtp;
use App\Services\EncryptionService;
use App\Services\LoggingService;
use App\Services\NotificationService;
use App\Services\OtpService;

/**
 * Send OTP Action
 * 
 * Single-purpose action class for sending OTP.
 * Handles rate limiting, OTP generation, storage, and notification.
 */
class SendOtpAction
{
    public function __construct(
        private OtpService $otpService,
        private EncryptionService $encryption,
        private NotificationService $notification,
        private LoggingService $logger
    ) {}

    /**
     * Execute the action.
     * 
     * @throws CooldownActiveException
     * @throws RateLimitExceededException
     */
    public function execute(
        string $email,
        ?string $name = null,
        ?string $referralCode = null,
        string $type = 'login'
    ): bool {
        $emailHash = $this->encryption->hash($email);

        // Check cooldown
        $cooldownRemaining = $this->otpService->getCooldownRemaining($emailHash);
        if ($cooldownRemaining > 0) {
            throw new CooldownActiveException($cooldownRemaining);
        }

        // Check rate limit
        if ($this->otpService->isRateLimited($emailHash)) {
            $this->logger->logSecurityEvent('otp_rate_limit_exceeded', $emailHash, [
                'type' => $type,
            ], 'high');

            throw new RateLimitExceededException();
        }

        // Generate OTP
        $otp = $this->otpService->generateOtp();
        $otpHash = $this->otpService->hashOtp($otp);

        // Store OTP record
        PreUserOtp::create([
            'email' => $email,
            'email_hash' => $emailHash,
            'name' => $name,
            'referral_code' => $referralCode,
            'otp_hash' => $otpHash,
            'expired_at' => now()->addMinutes(5),
            'ip_address' => request()->ip(),
            'user_agent' => substr(request()->userAgent() ?? '', 0, 255),
            'created_at' => now(),
        ]);

        // Send notification
        $this->notification->sendOtp($email, $otp, $name);

        // Track request for rate limiting
        $this->otpService->trackRequest($emailHash);

        // Set cooldown
        $this->otpService->setCooldown($emailHash);

        return true;
    }
}
