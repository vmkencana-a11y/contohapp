<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to authenticate admin users.
 * 
 * Expects admin to be logged in via Laravel session (admin guard).
 * Checks admin status and optionally required permissions.
 */
class AuthenticateAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        // Check if admin is authenticated
        if (!Auth::guard('admin')->check()) {
            return $this->unauthorized('Admin tidak terautentikasi');
        }
        
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();
        
        // Check if admin is active
        if (!$admin->isActive()) {
            Auth::guard('admin')->logout();
            return $this->unauthorized('Akun admin Anda ditangguhkan');
        }
        
        // Check permission if specified
        if ($permission && !$admin->hasPermission($permission)) {
            return $this->forbidden('Anda tidak memiliki izin untuk akses ini');
        }
        
        return $next($request);
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
        
        return redirect()->route('admin.login')
            ->with('error', $message);
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
}
