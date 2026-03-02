<?php

namespace App\Actions\User;

use App\Enums\UserStatusEnum;
use App\Models\User;
use App\Services\LoggingService;
use Illuminate\Support\Facades\DB;

/**
 * Action to reactivate a suspended user (by admin).
 */
class ReactivateUserAction
{
    public function __construct(private LoggingService $logger)
    {
    }

    /**
     * Reactivate a suspended user.
     */
    public function execute(User $user, int $adminId, ?string $reason = null): User
    {
        if ($user->status !== UserStatusEnum::SUSPENDED) {
            throw new \Exception('Only suspended users can be reactivated');
        }

        return DB::transaction(function () use ($user, $adminId, $reason) {
            $oldStatus = $user->status->value;
            
            $user->update([
                'status' => UserStatusEnum::ACTIVE,
                'status_changed_at' => now(),
                'status_changed_by' => (string) $adminId,
                'status_reason' => $reason,
                // Clear suspension data
                'suspended_at' => null,
                'suspended_by' => null,
                'suspended_reason' => null,
            ]);
            
            $this->logger->logUserStatusChange(
                $user->id,
                'reactivated',
                'admin',
                (string) $adminId,
                $oldStatus,
                'active',
                $reason,
                ['trigger_type' => 'admin_action']
            );
            
            $this->logger->logAdminActivity(
                $adminId,
                'user.reactivate',
                'User',
                $user->id,
                $reason
            );
            
            return $user->fresh();
        });
    }
}
