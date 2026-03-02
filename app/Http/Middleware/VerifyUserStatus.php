<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyUserStatus
{
    /**
     * Verify user is active before allowing access.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return $this->handleUnavailable($request, 'Sesi Anda telah berakhir.');
        }

        if ($user->status->value === 'suspended') {
            return $this->handleUnavailable($request, 'Akun Anda ditangguhkan sementara. Hubungi support untuk informasi lebih lanjut.');
        }

        if ($user->status->value === 'banned') {
            return $this->handleUnavailable($request, 'Akun Anda telah diblokir permanen.');
        }

        if ($user->status->value !== 'active') {
            return $this->handleUnavailable($request, 'Akun Anda tidak aktif.');
        }

        return $next($request);
    }

    protected function handleUnavailable(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 403);
        }

        return redirect()->route('login')
            ->with('error', $message);
    }
}
