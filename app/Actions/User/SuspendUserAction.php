<?php

namespace App\Actions\User;

use App\Enums\UserStatusEnum;
use App\Models\User;
use App\Services\LoggingService;
use App\Services\SessionService;
use Illuminate\Support\Facades\DB;

/**
 * Action to suspend a user (by admin).
 * 
 * Suspension is temporary and can be reversed.
 * User cannot login while suspended.
 */
class SuspendUserAction
{
    public function __construct(
        private LoggingService $logger,
        private SessionService $sessionService
    ) {
    }

    /**
     * Suspend a user.
     * 
     * @param User $user User to suspend
     * @param int $adminId Admin performing the action
     * @param string $reason Reason for suspension
     */
    public function execute(User $user, int $adminId, string $reason): User
    {
        // Validate transition
        if (!$user->status->canTransitionTo(UserStatusEnum::SUSPENDED)) {
            throw new \Exception("Cannot suspend user with status: {$user->status->label()}");
        }

        return DB::transaction(function () use ($user, $adminId, $reason) {
            $oldStatus = $user->status->value;
            
            $user->update([
                'status' => UserStatusEnum::SUSPENDED,
                'status_changed_at' => now(),
                'status_changed_by' => (string) $adminId,
                'status_reason' => $reason,
                'suspended_at' => now(),
                'suspended_by' => (string) $adminId,
                'suspended_reason' => $reason,
            ]);
            
            // Revoke all sessions
            $this->sessionService->revokeAllUserSessions($user->id, 'user_suspended');
            
            // Log
            $this->logger->logUserStatusChange(
                $user->id,
                'suspended',
                'admin',
                (string) $adminId,
                $oldStatus,
                'suspended',
                $reason,
                ['trigger_type' => 'admin_action']
            );
            
            $this->logger->logAdminActivity(
                $adminId,
                'user.suspend',
                'User',
                $user->id,
                $reason
            );
            
            return $user->fresh();
        });
    }
}
