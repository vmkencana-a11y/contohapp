<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\LoggingService;
use App\Services\SessionService;

/**
 * Login User Action
 * 
 * Creates session for authenticated user.
 */
class LoginUserAction
{
    public function __construct(
        private SessionService $sessionService,
        private LoggingService $logger
    ) {}

    /**
     * Execute the action.
     */
    public function execute(User $user): array
    {
        // Create session
        $result = $this->sessionService->createUserSession($user);

        // Update last login
        $user->update(['last_login_at' => now()]);

        return $result;
    }
}
