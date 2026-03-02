<?php

namespace App\Http\Middleware;

use App\Services\CspNonceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function __construct(
        private CspNonceService $cspNonceService
    ) {}

    /**
     * Security headers for bank-grade protection.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');
        
        // XSS protection
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        
        // Referrer policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Permissions policy - allow camera for KYC verification
        $response->headers->set('Permissions-Policy', 'camera=(self), microphone=(), geolocation=()');
        
        // Content Security Policy with nonces
        // Enable in production and local to catch CSP issues early.
        if (app()->isProduction() || app()->isLocal()) {
            $nonce = $this->cspNonceService->getNonce();

            $scriptSrc = ["'self'", "'nonce-{$nonce}'", 'cdn.jsdelivr.net'];
            $styleSrc = ["'self'", "'nonce-{$nonce}'", 'fonts.googleapis.com'];
            $connectSrc = ["'self'", 'cdn.jsdelivr.net', 'storage.googleapis.com'];
            $imgSrc = ["'self'", 'data:', 'blob:', 'https://api.qrserver.com'];

            // Local dev: allow Vite dev server + HMR websocket origin.
            if (app()->isLocal()) {
                $defaultViteHttpOrigins = ['http://127.0.0.1:5173', 'http://localhost:5173'];
                $defaultViteWsOrigins = ['ws://127.0.0.1:5173', 'ws://localhost:5173'];

                $scriptSrc = array_merge($scriptSrc, $defaultViteHttpOrigins);
                $styleSrc = array_merge($styleSrc, $defaultViteHttpOrigins);
                $connectSrc = array_merge($connectSrc, $defaultViteHttpOrigins, $defaultViteWsOrigins);
                $imgSrc = array_merge($imgSrc, $defaultViteHttpOrigins);

                [$viteHttpOrigin, $viteWsOrigin] = $this->getViteDevOrigins();
                if ($viteHttpOrigin !== null) {
                    $scriptSrc[] = $viteHttpOrigin;
                    $styleSrc[] = $viteHttpOrigin;
                    $connectSrc[] = $viteHttpOrigin;
                    $imgSrc[] = $viteHttpOrigin;
                }
                if ($viteWsOrigin !== null) {
                    $connectSrc[] = $viteWsOrigin;
                }
            }

            $csp = implode('; ', [
                "default-src 'self'",
                'script-src ' . implode(' ', array_unique($scriptSrc)),
                'style-src ' . implode(' ', array_unique($styleSrc)),
                "font-src 'self' fonts.gstatic.com",
                'img-src ' . implode(' ', array_unique($imgSrc)),
                'connect-src ' . implode(' ', array_unique($connectSrc)),
                "worker-src 'self' blob:",
                "frame-ancestors 'none'",
                "base-uri 'self'",
                "form-action 'self'",
            ]);

            $response->headers->set('Content-Security-Policy', $csp);
        }
        
        // Strict Transport Security (HTTPS only)
        if ($request->secure() || app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    /**
     * Return Vite dev server HTTP and WS origins from public/hot if available.
     *
     * @return array{0: string|null, 1: string|null}
     */
    private function getViteDevOrigins(): array
    {
        $hotPath = public_path('hot');
        if (!is_file($hotPath)) {
            return [null, null];
        }

        $url = trim((string) @file_get_contents($hotPath));
        if ($url === '') {
            return [null, null];
        }

        $parts = parse_url($url);
        if (!is_array($parts) || !isset($parts['host'])) {
            return [null, null];
        }

        $scheme = $parts['scheme'] ?? 'http';
        $host = $parts['host'];
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $httpOrigin = $scheme . '://' . $host . $port;
        $wsScheme = $scheme === 'https' ? 'wss' : 'ws';
        $wsOrigin = $wsScheme . '://' . $host . $port;

        return [$httpOrigin, $wsOrigin];
    }
}
