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
 * Validates session timeouts and refreshes cookie on every authenticated request.
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
        $bearer = $request->bearerToken();
        $token = $bearer ?: $request->cookie('session_token');
        $tokenSource = $bearer ? 'bearer' : 'cookie';

        if (!$token) {
            return $this->unauthorized('Token tidak ditemukan', true);
        }

        $session = $this->sessionService->validateSession($token);

        if (!$session) {
            return $this->unauthorized('Sesi tidak valid atau sudah berakhir', true);
        }

        // Load user and check status
        $user = $session->user;

        if (!$user || !$user->canLogin()) {
            $this->sessionService->revokeSession($session, 'user_status_invalid');
            return $this->unauthorized('Akun Anda tidak dapat mengakses layanan ini', true);
        }

        // Bind user and session to request
        $request->setUserResolver(fn() => $user);
        $request->attributes->set('session', $session);

        $rotationEnabled = (bool) config('security.token_rotation_enabled', true);
        $rotationHours = (int) config('security.token_rotation_hours', 6);

        // Rotate only for cookie-based auth. For Bearer tokens we can't safely deliver
        // a rotated token without breaking clients that don't handle the update.
        if ($rotationEnabled && $tokenSource === 'cookie' && $session->needsRotation($rotationHours)) {
            $token = $this->sessionService->rotateToken($session);
        }

        // Refresh cookie with remaining absolute timeout so the browser expiry
        // always reflects the actual server-side deadline.
        $elapsed          = now()->diffInSeconds($session->created_at);
        $remainingSeconds = max(0, $session->absolute_timeout - $elapsed);
        $remainingMinutes = max(1, (int) ceil($remainingSeconds / 60));

        $cookiePath = (string) config('security.auth_cookie.path', config('session.path', '/'));
        $cookieDomain = config('security.auth_cookie.domain', config('session.domain'));
        $cookieSecure = (bool) config('security.auth_cookie.secure', (bool) config('session.secure', true));
        $cookieHttpOnly = (bool) config('security.auth_cookie.http_only', (bool) config('session.http_only', true));
        $cookieSameSite = (string) config('security.auth_cookie.same_site', 'strict');

        $refreshedCookie = cookie(
            'session_token',
            $token,
            $remainingMinutes,
            $cookiePath,
            $cookieDomain,
            $cookieSecure,
            $cookieHttpOnly,
            false,
            $cookieSameSite
        );

        $response = $next($request);

        // Do not override cookies intentionally set by the downstream handler
        // (e.g. logout forget-cookie response).
        if ($this->responseHasCookie($response, 'session_token')) {
            return $response;
        }

        return $response->withCookie($refreshedCookie);
    }

    /**
     * Return unauthorized response.
     */
    private function unauthorized(string $message, bool $forgetSessionCookie = false): Response
    {
        $forgetCookie = cookie()->forget(
            'session_token',
            (string) config('security.auth_cookie.path', config('session.path', '/')),
            config('security.auth_cookie.domain', config('session.domain'))
        );

        if (request()->expectsJson()) {
            $response = response()->json([
                'success' => false,
                'message' => $message,
            ], 401);

            return $forgetSessionCookie
                ? $response->withCookie($forgetCookie)
                : $response;
        }

        $response = redirect()->route('login')
            ->with('error', $message);

        return $forgetSessionCookie
            ? $response->withCookie($forgetCookie)
            : $response;
    }

    private function responseHasCookie(Response $response, string $name): bool
    {
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === $name) {
                return true;
            }
        }

        return false;
    }
}
