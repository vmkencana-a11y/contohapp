<?php

namespace App\Actions\User;

use App\Models\PreUserOtp;
use App\Models\User;
use App\Models\UserReferral;
use App\Services\LoggingService;
use Illuminate\Support\Facades\DB;

/**
 * Action to create a new user after OTP verification.
 * 
 * Handles:
 * - User creation with encrypted data
 * - Referral code generation
 * - Referral relationship creation (auto-active)
 * - Status logging
 */
class CreateUserAction
{
    public function __construct(private LoggingService $logger)
    {
    }

    /**
     * Create user from verified OTP record.
     */
    public function execute(PreUserOtp $otpRecord): User
    {
        return DB::transaction(function () use ($otpRecord) {
            // Create user
            $user = User::create([
                'name' => $otpRecord->decrypted_name,
                'email' => $otpRecord->decrypted_email,
                'referral_code' => User::generateReferralCode(),
                'status' => 'active',
            ]);
            
            // Handle referral if code provided
            if ($otpRecord->referral_code) {
                $referrer = User::findByReferralCode($otpRecord->referral_code);
                
                if ($referrer && $referrer->isActive()) {
                    $user->update([
                        'referred_by' => $referrer->id,
                        'referred_at' => now(),
                    ]);
                    
                    // Create referral record (auto-active)
                    UserReferral::createActive($user->id, $referrer->id);
                }
            }
            
            // Log
            $this->logger->logUserStatusChange(
                $user->id,
                'activated',
                'system',
                null,
                null,
                'active',
                'User registration completed',
                ['trigger_type' => 'registration']
            );
            
            return $user;
        });
    }
}
