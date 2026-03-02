<?php

namespace App\Actions\Auth;

use App\Models\UserSession;
use App\Services\LoggingService;
use App\Services\SessionService;

/**
 * Logout User Action
 * 
 * Revokes user session.
 */
class LogoutUserAction
{
    public function __construct(
        private SessionService $sessionService,
        private LoggingService $logger
    ) {}

    /**
     * Execute the action.
     */
    public function execute(UserSession $session): void
    {
        $this->sessionService->revokeSession($session, 'user_logout');
    }

    /**
     * Logout from all devices.
     */
    public function executeAll(string $userId): int
    {
        return $this->sessionService->revokeAllUserSessions($userId, 'logout_all_devices');
    }
}
