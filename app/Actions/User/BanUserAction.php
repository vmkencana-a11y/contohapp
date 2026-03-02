<?php

namespace App\Actions\User;

use App\Enums\UserStatusEnum;
use App\Models\User;
use App\Models\UserReferral;
use App\Services\LoggingService;
use App\Services\SessionService;
use Illuminate\Support\Facades\DB;

/**
 * Action to ban a user permanently (by admin).
 * 
 * Ban is terminal - cannot be reversed.
 * Automatically cancels all referrals for this user.
 */
class BanUserAction
{
    public function __construct(
        private LoggingService $logger,
        private SessionService $sessionService
    ) {
    }

    /**
     * Ban a user permanently.
     * 
     * @param User $user User to ban
     * @param int $adminId Admin performing the action
     * @param string $reason Reason for ban
     */
    public function execute(User $user, int $adminId, string $reason): User
    {
        // Validate transition
        if (!$user->status->canTransitionTo(UserStatusEnum::BANNED)) {
            throw new \Exception("Cannot ban user with status: {$user->status->label()}");
        }

        return DB::transaction(function () use ($user, $adminId, $reason) {
            $oldStatus = $user->status->value;
            
            $user->update([
                'status' => UserStatusEnum::BANNED,
                'status_changed_at' => now(),
                'status_changed_by' => (string) $adminId,
                'status_reason' => $reason,
                'banned_at' => now(),
                'banned_by' => (string) $adminId,
                'banned_reason' => $reason,
            ]);
            
            // Revoke all sessions
            $this->sessionService->revokeAllUserSessions($user->id, 'user_banned');
            
            // Cancel all referrals (as per revised spec)
            UserReferral::cancelForUser($user->id);
            
            // Log
            $this->logger->logUserStatusChange(
                $user->id,
                'banned',
                'admin',
                (string) $adminId,
                $oldStatus,
                'banned',
                $reason,
                ['trigger_type' => 'admin_action']
            );
            
            $this->logger->logAdminActivity(
                $adminId,
                'user.ban',
                'User',
                $user->id,
                $reason
            );
            
            // Log security event
            $this->logger->logSecurityEvent(
                'user_banned',
                $user->id,
                'high',
                ['event_category' => 'user_management']
            );
            
            return $user->fresh();
        });
    }
}
