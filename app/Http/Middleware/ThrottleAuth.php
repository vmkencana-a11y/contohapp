<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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

        // Only count toward rate limit on failure (4xx/5xx)
        if ($response->getStatusCode() >= 400) {
            RateLimiter::hit($key, (int) $decayMinutes * 60);
        } else {
            // Clear limiter on success
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
