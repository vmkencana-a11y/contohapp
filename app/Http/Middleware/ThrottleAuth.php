<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottleAuth
{
    /**
     * Rate limit authentication attempts.
     * 
     * 5 attempts per minute per IP + email combination
     */
    public function handle(Request $request, Closure $next, string $maxAttempts = '5', string $decayMinutes = '1'): Response
    {
        $key = $this->resolveKey($request);
        
        if (RateLimiter::tooManyAttempts($key, (int) $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.",
                    'retry_after' => $seconds,
                ], 429);
            }
            
            return back()->withErrors([
                'email' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik."
            ]);
        }

        $response = $next($request);

        // Treat "redirect back with validation errors" as a failure for web flows (302).
        // Many Laravel controllers return redirects on failure rather than 4xx codes.
        $failed = $response->getStatusCode() >= 400;

        if (!$failed && !$request->expectsJson() && $response instanceof RedirectResponse) {
            $errors = $request->session()->get('errors');
            if ($errors && (method_exists($errors, 'any') ? $errors->any() : count($errors->all()) > 0)) {
                $failed = true;
            }
        }

        if ($failed) {
            RateLimiter::hit($key, (int) $decayMinutes * 60);
        } else {
            RateLimiter::clear($key);
        }

        return $response;
    }

    /**
     * Generate rate limit key.
     */
    protected function resolveKey(Request $request): string
    {
        $email = strtolower($request->input('email', ''));
        $ip = $request->ip();
        
        return 'auth:' . sha1($email . '|' . $ip);
    }
}
