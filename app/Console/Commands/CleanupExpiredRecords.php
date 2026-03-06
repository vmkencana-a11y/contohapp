<?php

namespace App\Console\Commands;

use App\Models\AdminOtp;
use App\Models\PreUserOtp;
use App\Models\AdminSession;
use App\Models\UserSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
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

        $expiredSql = $this->expiredSessionSql();

        $userSessionCount = UserSession::where(function ($q) use ($expiredSql) {
            $q->whereRaw($expiredSql)->orWhereNotNull('revoked_at');
        })->delete();
        $this->line("  - Expired / revoked sessions: {$userSessionCount}");
        $totalDeleted += $userSessionCount;

        $this->line('');
        $this->info('Admin Sessions:');
        $adminSessionCount = AdminSession::where(function ($q) use ($expiredSql) {
            $q->whereRaw($expiredSql)->orWhereNotNull('revoked_at');
        })->delete();
        $this->line("  - Expired / revoked sessions: {$adminSessionCount}");
        $totalDeleted += $adminSessionCount;
        
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
                'user_sessions' => $userSessionCount,
                'admin_sessions' => $adminSessionCount,
                'total' => $totalDeleted,
            ]);
        }
        
        return self::SUCCESS;
    }

    /**
     * Build a DB-driver specific SQL expression for "expired custom session".
     *
     * Custom sessions expire when either:
     * - idle timeout is exceeded, or
     * - absolute timeout is exceeded.
     */
    private function expiredSessionSql(): string
    {
        $driver = DB::getDriverName();

        return match ($driver) {
            'pgsql' => "(EXTRACT(EPOCH FROM (NOW() - last_activity_at)) > idle_timeout OR EXTRACT(EPOCH FROM (NOW() - created_at)) > absolute_timeout)",
            'sqlite' => "((strftime('%s','now') - strftime('%s', last_activity_at)) > idle_timeout OR (strftime('%s','now') - strftime('%s', created_at)) > absolute_timeout)",
            default => "(TIMESTAMPDIFF(SECOND, last_activity_at, NOW()) > idle_timeout OR TIMESTAMPDIFF(SECOND, created_at, NOW()) > absolute_timeout)",
        };
    }
}
