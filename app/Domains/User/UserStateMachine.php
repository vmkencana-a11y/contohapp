<?php

namespace App\Domains\User;

use App\Enums\UserStatusEnum;
use App\Exceptions\InvalidStateTransitionException;
use App\Models\User;
use App\Services\LoggingService;
use App\Services\SessionService;
use Illuminate\Support\Facades\DB;

/**
 * User State Machine
 * 
 * Handles all user status transitions with validation,
 * side effects (session revocation, referral cancellation),
 * and comprehensive logging.
 */
class UserStateMachine
{
    private User $user;
    private LoggingService $logger;
    private SessionService $sessionService;

    public function __construct(User $user, LoggingService $logger, SessionService $sessionService)
    {
        $this->user = $user;
        $this->logger = $logger;
        $this->sessionService = $sessionService;
    }

    /**
     * Check if transition to new status is allowed.
     */
    public function canTransitionTo(UserStatusEnum $newStatus): bool
    {
        return UserTransitionRules::canTransition($this->user->status, $newStatus);
    }

    /**
     * Transition user to new status.
     * 
     * @throws InvalidStateTransitionException
     */
    public function transitionTo(
        UserStatusEnum $newStatus,
        string $reason,
        string $changedByType = 'system',
        ?string $changedById = null
    ): void {
        $oldStatus = $this->user->status;

        // Validate transition
        if (!$this->canTransitionTo($newStatus)) {
            throw new InvalidStateTransitionException($oldStatus, $newStatus);
        }

        // Validate actor permissions
        if (UserTransitionRules::requiresAdmin($oldStatus, $newStatus) && $changedByType !== 'admin') {
            throw new InvalidStateTransitionException(
                $oldStatus,
                $newStatus,
                "Transisi dari '{$oldStatus->value}' ke '{$newStatus->value}' memerlukan admin."
            );
        }

        DB::transaction(function () use ($oldStatus, $newStatus, $reason, $changedByType, $changedById) {
            // Update user status
            $this->user->update([
                'status' => $newStatus,
                'status_changed_at' => now(),
                'status_changed_by' => $changedById,
                'status_reason' => $reason,
            ]);

            // Handle status-specific fields
            $this->handleStatusSpecificUpdates($newStatus, $reason, $changedById);

            // Execute side effects
            $this->executeSideEffects($oldStatus, $newStatus, $reason);

            // Log the transition
            $this->logger->logUserStatusChange(
                $this->user->id,
                $this->getActionForTransition($oldStatus, $newStatus),
                $changedByType,
                $changedById,
                $oldStatus->value,
                $newStatus->value,
                $reason
            );
        });
    }

    /**
     * Handle status-specific field updates.
     */
    private function handleStatusSpecificUpdates(
        UserStatusEnum $newStatus,
        string $reason,
        ?string $changedById
    ): void {
        switch ($newStatus) {
            case UserStatusEnum::SUSPENDED:
                $this->user->update([
                    'suspended_at' => now(),
                    'suspended_by' => $changedById,
                    'suspended_reason' => $reason,
                ]);
                break;

            case UserStatusEnum::BANNED:
                $this->user->update([
                    'banned_at' => now(),
                    'banned_by' => $changedById,
                    'banned_reason' => $reason,
                ]);
                break;

            case UserStatusEnum::ACTIVE:
                // Clear suspension/ban fields on reactivation
                $this->user->update([
                    'suspended_at' => null,
                    'suspended_by' => null,
                    'suspended_reason' => null,
                ]);
                break;
        }
    }

    /**
     * Execute side effects based on transition.
     */
    private function executeSideEffects(
        UserStatusEnum $oldStatus,
        UserStatusEnum $newStatus,
        string $reason
    ): void {
        // Revoke all sessions on suspension or ban
        if (in_array($newStatus, [UserStatusEnum::SUSPENDED, UserStatusEnum::BANNED])) {
            $this->sessionService->revokeAllUserSessions(
                $this->user->id,
                'status_changed_to_' . $newStatus->value
            );
        }

        // Cancel referrals on ban
        if ($newStatus === UserStatusEnum::BANNED) {
            $this->user->referrals()->update(['status' => 'cancelled']);
        }
    }

    /**
     * Get action name for logging.
     */
    private function getActionForTransition(UserStatusEnum $from, UserStatusEnum $to): string
    {
        if ($to === UserStatusEnum::ACTIVE) {
            return $from === UserStatusEnum::INACTIVE ? 'activated' : 'reactivated';
        }

        return match ($to) {
            UserStatusEnum::INACTIVE => 'deactivated',
            UserStatusEnum::SUSPENDED => 'suspended',
            UserStatusEnum::BANNED => 'banned',
            default => 'status_changed',
        };
    }

    /**
     * Convenience methods for common transitions.
     */
    public function activate(string $reason = 'User login verified'): void
    {
        $this->transitionTo(UserStatusEnum::ACTIVE, $reason, 'system');
    }

    public function suspend(string $reason, string $adminId): void
    {
        $this->transitionTo(UserStatusEnum::SUSPENDED, $reason, 'admin', $adminId);
    }

    public function ban(string $reason, string $adminId): void
    {
        $this->transitionTo(UserStatusEnum::BANNED, $reason, 'admin', $adminId);
    }

    public function reactivate(string $reason, string $adminId): void
    {
        $this->transitionTo(UserStatusEnum::ACTIVE, $reason, 'admin', $adminId);
    }

    /**
     * Static factory method.
     */
    public static function for(User $user): self
    {
        return new self(
            $user,
            app(LoggingService::class),
            app(SessionService::class)
        );
    }
}
