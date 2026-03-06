<?php

namespace App\Http\Middleware;

use App\Services\SessionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to authenticate admin users via session token.
 *
 * Expects 'admin_token' cookie (set after completing 3-layer login).
 * Checks admin status and optionally required permissions.
 */
class AuthenticateAdmin
{
    public function __construct(private SessionService $sessionService)
    {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        $token = $request->cookie('admin_token');

        if (!$token) {
            return $this->unauthorized('Admin tidak terautentikasi');
        }

        $session = $this->sessionService->validateAdminSession($token);

        if (!$session) {
            return $this->unauthorized('Sesi admin tidak valid atau sudah berakhir', true);
        }

        $admin = $session->admin;

        // Check if admin is active
        if (!$admin || !$admin->isActive()) {
            $this->sessionService->revokeAdminSession($session, 'admin_status_invalid');
            return $this->unauthorized('Akun admin Anda ditangguhkan', true);
        }

        // Check permission if specified
        if ($permission && !$admin->hasPermission($permission)) {
            return $this->forbidden('Anda tidak memiliki izin untuk akses ini');
        }

        // Bind admin and session to request
        $request->attributes->set('admin', $admin);
        $request->attributes->set('admin_session', $session);

        $rotationEnabled = (bool) config('security.token_rotation_enabled', true);
        $rotationHours = (int) config('security.token_rotation_hours', 6);

        if ($rotationEnabled && $session->needsRotation($rotationHours)) {
            $token = $this->sessionService->rotateAdminToken($session);
        }

        // Refresh cookie with remaining absolute timeout
        $elapsed          = now()->diffInSeconds($session->created_at);
        $remainingSeconds = max(0, $session->absolute_timeout - $elapsed);
        $remainingMinutes = max(1, (int) ceil($remainingSeconds / 60));

        $cookiePath = (string) config('security.auth_cookie.path', config('session.path', '/'));
        $cookieDomain = config('security.auth_cookie.domain', config('session.domain'));
        $cookieSecure = (bool) config('security.auth_cookie.secure', (bool) config('session.secure', true));
        $cookieHttpOnly = (bool) config('security.auth_cookie.http_only', (bool) config('session.http_only', true));
        $cookieSameSite = (string) config('security.auth_cookie.same_site', 'strict');

        $refreshedCookie = cookie(
            'admin_token',
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
        if ($this->responseHasCookie($response, 'admin_token')) {
            return $response;
        }

        return $response->withCookie($refreshedCookie);
    }

    /**
     * Return unauthorized response.
     */
    private function unauthorized(string $message, bool $forgetCookie = false): Response
    {
        $forgetAdminCookie = cookie()->forget(
            'admin_token',
            (string) config('security.auth_cookie.path', config('session.path', '/')),
            config('security.auth_cookie.domain', config('session.domain'))
        );

        if (request()->expectsJson()) {
            $response = response()->json([
                'success' => false,
                'message' => $message,
            ], 401);

            return $forgetCookie
                ? $response->withCookie($forgetAdminCookie)
                : $response;
        }

        $response = redirect()->route('admin.login')
            ->with('error', $message);

        return $forgetCookie
            ? $response->withCookie($forgetAdminCookie)
            : $response;
    }

    /**
     * Return forbidden response.
     */
    private function forbidden(string $message): Response
    {
        if (request()->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 403);
        }

        return back()->with('error', $message);
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
