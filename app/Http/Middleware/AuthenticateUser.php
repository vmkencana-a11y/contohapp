<?php

namespace App\Http\Middleware;

use App\Services\SessionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to authenticate users via session token.
 * 
 * Expects Bearer token in Authorization header or 'session_token' cookie.
 * Validates session timeouts and handles token rotation.
 */
class AuthenticateUser
{
    public function __construct(private SessionService $sessionService)
    {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->getToken($request);
        
        if (!$token) {
            return $this->unauthorized('Token tidak ditemukan');
        }
        
        $session = $this->sessionService->validateSession($token);
        
        if (!$session) {
            return $this->unauthorized('Sesi tidak valid atau sudah berakhir');
        }
        
        // Load user and check status
        $user = $session->user;
        
        if (!$user || !$user->canLogin()) {
            $this->sessionService->revokeSession($session, 'user_status_invalid');
            return $this->unauthorized('Akun Anda tidak dapat mengakses layanan ini');
        }
        
        // Bind user and session to request
        $request->setUserResolver(fn() => $user);
        $request->attributes->set('session', $session);
        
        return $next($request);
    }

    /**
     * Extract token from request.
     */
    private function getToken(Request $request): ?string
    {
        // Try Authorization header first
        $bearer = $request->bearerToken();
        if ($bearer) {
            return $bearer;
        }
        
        // Try cookie
        return $request->cookie('session_token');
    }

    /**
     * Return unauthorized response.
     */
    private function unauthorized(string $message): Response
    {
        if (request()->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 401);
        }
        
        return redirect()->route('login')
            ->with('error', $message);
    }
}
