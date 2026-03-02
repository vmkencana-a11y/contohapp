<?php

namespace App\Console\Commands;

use App\Models\AdminOtp;
use App\Models\PreUserOtp;
use App\Models\UserSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupExpiredRecords extends Command
{
    protected $signature = 'sekuota:cleanup';
    protected $description = 'Clean up expired OTPs, admin OTPs, and inactive sessions';

    public function handle(): int
    {
        $this->info('Cleaning up expired records...');
        
        $totalDeleted = 0;
        
        // =============================================
        // Pre-User OTPs Cleanup
        // =============================================
        $this->line('');
        $this->info('Pre-User OTPs:');
        
        // Clean expired OTPs (older than 1 hour)
        $otpCount = PreUserOtp::where('expired_at', '<', now()->subHour())
            ->whereNull('verified_at')
            ->delete();
        $this->line("  - Expired OTPs (>1h): {$otpCount}");
        $totalDeleted += $otpCount;
        
        // Clean verified OTPs (older than 24 hours) 
        $verifiedCount = PreUserOtp::whereNotNull('verified_at')
            ->where('verified_at', '<', now()->subDay())
            ->delete();
        $this->line("  - Verified OTPs (>24h): {$verifiedCount}");
        $totalDeleted += $verifiedCount;
        
        // Clean locked OTPs past lockout period
        $lockedCount = PreUserOtp::whereNotNull('locked_until')
            ->where('locked_until', '<', now()->subHour())
            ->delete();
        $this->line("  - Locked OTPs (past lockout): {$lockedCount}");
        $totalDeleted += $lockedCount;
        
        // =============================================
        // Admin OTPs Cleanup
        // =============================================
        $this->line('');
        $this->info('Admin OTPs:');
        
        // Clean expired admin OTPs (older than 1 hour)
        $adminExpiredCount = AdminOtp::where('expired_at', '<', now()->subHour())
            ->whereNull('verified_at')
            ->delete();
        $this->line("  - Expired OTPs (>1h): {$adminExpiredCount}");
        $totalDeleted += $adminExpiredCount;
        
        // Clean verified admin OTPs (older than 24 hours)
        $adminVerifiedCount = AdminOtp::whereNotNull('verified_at')
            ->where('verified_at', '<', now()->subDay())
            ->delete();
        $this->line("  - Verified OTPs (>24h): {$adminVerifiedCount}");
        $totalDeleted += $adminVerifiedCount;
        
        // Clean locked admin OTPs past lockout period
        $adminLockedCount = AdminOtp::whereNotNull('locked_until')
            ->where('locked_until', '<', now()->subHour())
            ->delete();
        $this->line("  - Locked OTPs (past lockout): {$adminLockedCount}");
        $totalDeleted += $adminLockedCount;
        
        // =============================================
        // Sessions Cleanup
        // =============================================
        $this->line('');
        $this->info('User Sessions:');
        
        // Clean inactive sessions (older than configured lifetime * 2)
        $sessionLifetime = config('session.lifetime', 120);
        $sessionCount = UserSession::where('last_activity_at', '<', now()->subMinutes($sessionLifetime * 2))
            ->delete();
        $this->line("  - Inactive sessions: {$sessionCount}");
        $totalDeleted += $sessionCount;
        
        // =============================================
        // Summary
        // =============================================
        $this->line('');
        $this->info("Cleanup completed! Total records deleted: {$totalDeleted}");
        
        // Log if any records were deleted
        if ($totalDeleted > 0) {
            Log::channel('daily')->info('Sekuota cleanup completed', [
                'pre_user_otps' => $otpCount + $verifiedCount + $lockedCount,
                'admin_otps' => $adminExpiredCount + $adminVerifiedCount + $adminLockedCount,
                'sessions' => $sessionCount,
                'total' => $totalDeleted,
            ]);
        }
        
        return self::SUCCESS;
    }
}
