<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminOtpMail;
use App\Models\Admin;
use App\Models\AdminOtp;
use App\Services\LoggingService;
use App\Services\NotificationService;
use App\Services\OtpService;
use App\Services\SessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin Authentication Controller
 * 
 * Implements 3-layer authentication:
 * - Layer 1: Email + Password
 * - Layer 2: Email OTP (6-digit)
 * - Layer 3: Google Authenticator (TOTP) - if enabled
 */
class AuthController extends Controller
{
    public function __construct(
        private OtpService $otpService,
        private NotificationService $notificationService,
        private LoggingService $logger,
        private SessionService $sessionService
    ) {}

    /**
     * Show admin login form.
     */
    public function showLoginForm(): View
    {
        return view('admin.auth.login');
    }

    /**
     * Layer 1: Verify email and password.
     */
    public function login(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        // Find admin using email hash lookup (since email is encrypted)
        $admin = Admin::findByEmail($validated['email']);
        
        if (!$admin || !$admin->checkPassword($validated['password'])) {
            $this->logger->logSecurityEvent('admin_login_failed', substr(hash('sha256', $validated['email']), 0, 16), 'medium', [
                'reason' => 'invalid_credentials',
            ]);
            
            return $this->errorResponse('Email atau password salah.');
        }
        
        if (!$admin->isActive()) {
            return $this->errorResponse('Akun admin Anda ditangguhkan.');
        }

        // Check working hours restriction
        if (!$admin->isWithinWorkingHours()) {
            $now = now();
            $this->logger->logSecurityEvent('admin_login_blocked_hours', substr(hash('sha256', $validated['email']), 0, 16), 'high', [
                'event_category' => 'working_hours_restriction',
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'attempted_day' => $now->format('l'), // e.g., "Friday"
                'attempted_time' => $now->format('H:i:s'),
                'allowed_days' => $admin->working_days_label,
                'allowed_hours' => $admin->working_hours_label,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return $this->errorResponse('Login di luar jam kerja tidak diizinkan. Silakan hubungi Super Admin.');
        }

        // Layer 1 passed - generate OTP for Layer 2
        $otp = $this->otpService->generateOtp();
        $otpHash = $this->otpService->hashOtp($otp);

        // Store admin OTP
        AdminOtp::create([
            'admin_id' => $admin->id,
            'otp_hash' => $otpHash,
            'expired_at' => now()->addMinutes(5),
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 255),
            'created_at' => now(),
        ]);

        // Send OTP email
        $this->notificationService->sendAdminOtp($admin->email, $otp, $admin->name);

        // Store admin ID in session for layer 2
        session(['admin_auth_id' => $admin->id, 'admin_auth_step' => 2]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'requires_otp' => true,
                'message' => 'Kode OTP telah dikirim ke email Anda.',
            ]);
        }

        return redirect()->route('admin.verify-otp')
            ->with('info', 'Kode OTP telah dikirim ke email Anda.');
    }

    /**
     * Show OTP verification form (Layer 2).
     */
    public function showOtpForm(): View|RedirectResponse
    {
        if (!session('admin_auth_id') || session('admin_auth_step') !== 2) {
            return redirect()->route('admin.login');
        }

        return view('admin.auth.verify-otp');
    }

    /**
     * Layer 2: Verify email OTP.
     */
    public function verifyOtp(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'otp' => ['required', 'string', 'size:6'],
        ], [
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.size' => 'Kode OTP harus 6 digit.',
        ]);

        $adminId = session('admin_auth_id');
        if (!$adminId || session('admin_auth_step') !== 2) {
            return $this->errorResponse('Sesi tidak valid. Silakan login ulang.');
        }

        $admin = Admin::find($adminId);
        if (!$admin) {
            session()->forget(['admin_auth_id', 'admin_auth_step']);
            return $this->errorResponse('Admin tidak ditemukan.');
        }

        // Get latest OTP
        $otpRecord = AdminOtp::where('admin_id', $admin->id)
            ->whereNull('verified_at')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$otpRecord) {
            return $this->errorResponse('OTP tidak ditemukan. Silakan login ulang.', 'otp');
        }

        // Check lock
        if ($otpRecord->isLocked()) {
            return $this->errorResponse('Terlalu banyak percobaan gagal. Silakan coba lagi nanti.', 'otp');
        }

        // Check expiry
        if ($otpRecord->isExpired()) {
            return $this->errorResponse('Kode OTP sudah kadaluarsa. Silakan login ulang.', 'otp');
        }

        // Verify OTP
        $otpHash = $this->otpService->hashOtp($validated['otp']);
        
        if (!hash_equals($otpRecord->otp_hash, $otpHash)) {
            $otpRecord->incrementAttempts();
            
            if ($otpRecord->hasExceededMaxAttempts(5)) {
                $otpRecord->lock(60);
                $this->logger->logSecurityEvent('admin_otp_max_attempts', (string)$admin->id, 'high', []);
            }

            $remaining = max(0, 5 - $otpRecord->attempt_count);
            return $this->errorResponse("OTP tidak valid. Sisa percobaan: {$remaining}", 'otp');
        }

        // Mark verified
        $otpRecord->markVerified();

        // Check if 2FA is enabled (Layer 3)
        if ($admin->has2faEnabled()) {
            session(['admin_auth_step' => 3]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'requires_2fa' => true,
                    'message' => 'Masukkan kode Google Authenticator.',
                ]);
            }

            return redirect()->route('admin.verify-2fa');
        }

        // No 2FA - complete login
        return $this->completeLogin($admin, $request);
    }

    /**
     * Show 2FA verification form (Layer 3).
     */
    public function show2faForm(): View|RedirectResponse
    {
        if (!session('admin_auth_id') || session('admin_auth_step') !== 3) {
            return redirect()->route('admin.login');
        }

        return view('admin.auth.verify-2fa');
    }

    /**
     * Layer 3: Verify Google Authenticator.
     */
    public function verify2fa(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ], [
            'code.required' => 'Kode 2FA wajib diisi.',
            'code.size' => 'Kode 2FA harus 6 digit.',
        ]);

        $adminId = session('admin_auth_id');
        if (!$adminId || session('admin_auth_step') !== 3) {
            return $this->errorResponse('Sesi tidak valid. Silakan login ulang.');
        }

        $admin = Admin::find($adminId);
        if (!$admin) {
            session()->forget(['admin_auth_id', 'admin_auth_step']);
            return $this->errorResponse('Admin tidak ditemukan.');
        }

        // Verify 2FA code
        $google2fa = app('pragmarx.google2fa');
        $valid = $google2fa->verifyKey($admin->decrypted_2fa_secret, $validated['code']);
        
        if (!$valid) {
            $this->logger->logSecurityEvent('admin_2fa_failed', (string)$admin->id, 'medium', []);
            return $this->errorResponse('Kode 2FA tidak valid.', 'code');
        }

        return $this->completeLogin($admin, $request);
    }

    /**
     * Complete login after all layers verified.
     */
    private function completeLogin(Admin $admin, Request $request): JsonResponse|RedirectResponse
    {
        // Re-check working hours (admin may have passed Layer 1 during hours and completed later)
        if (!$admin->isWithinWorkingHours()) {
            session()->forget(['admin_auth_id', 'admin_auth_step']);
            $this->logger->logSecurityEvent('admin_login_blocked_hours_late', (string)$admin->id, 'high', [
                'event_category' => 'working_hours_restriction',
                'stage' => 'complete_login',
            ]);
            return $this->errorResponse('Login di luar jam kerja tidak diizinkan.');
        }

        // Clear intermediate auth session data
        session()->forget(['admin_auth_id', 'admin_auth_step']);

        // SECURITY: Regenerate session ID to prevent session fixation attacks
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        // Create custom token-based session for admin
        $sessionData = $this->sessionService->createAdminSession($admin);
        $admin->update(['last_login_at' => now()]);

        $this->logger->logAdminActivity(
            (string)$admin->id,
            'admin.login',
            'Admin',
            (string)$admin->id,
            null,
            ['layers_passed' => $admin->has2faEnabled() ? 3 : 2]
        );

        // Build admin_token cookie aligned with session absolute_timeout
        $cookieMinutes = $this->sessionService->resolveCookieLifetimeMinutes(
            $sessionData['session']->absolute_timeout
        );

        $cookiePath = (string) config('security.auth_cookie.path', config('session.path', '/'));
        $cookieDomain = config('security.auth_cookie.domain', config('session.domain'));
        $cookieSecure = (bool) config('security.auth_cookie.secure', (bool) config('session.secure', true));
        $cookieHttpOnly = (bool) config('security.auth_cookie.http_only', (bool) config('session.http_only', true));
        $cookieSameSite = (string) config('security.auth_cookie.same_site', 'strict');

        $cookie = cookie(
            'admin_token',
            $sessionData['token'],
            $cookieMinutes,
            $cookiePath,
            $cookieDomain,
            $cookieSecure,
            $cookieHttpOnly,
            false,
            $cookieSameSite
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Login berhasil!',
                'redirect' => route('admin.dashboard'),
                'token' => $sessionData['token'],
                'token_type' => 'Bearer',
                'expires_in' => (int) $sessionData['session']->absolute_timeout,
            ])->withCookie($cookie);
        }

        return redirect()->route('admin.dashboard')
            ->withCookie($cookie)
            ->with('success', 'Selamat datang, ' . $admin->name);
    }

    /**
     * Resend OTP (Layer 2).
     * 
     * SECURITY: Rate limited to prevent spam attacks.
     */
    public function resendOtp(Request $request): JsonResponse|RedirectResponse
    {
        $adminId = session('admin_auth_id');
        if (!$adminId || session('admin_auth_step') !== 2) {
            return $this->errorResponse('Sesi tidak valid.');
        }

        $admin = Admin::find($adminId);
        if (!$admin) {
            return $this->errorResponse('Admin tidak ditemukan.');
        }

        // Rate limiting: max 3 resends per 5 minutes
        $cacheKey = "admin_otp_resend:{$admin->id}";
        $resendCount = \Illuminate\Support\Facades\Cache::get($cacheKey, 0);
        
        if ($resendCount >= 3) {
            $this->logger->logSecurityEvent('admin_otp_resend_rate_limit', (string)$admin->id, 'medium', [
                'event_category' => 'rate_limit',
            ]);
            return $this->errorResponse('Terlalu banyak permintaan OTP. Silakan tunggu 5 menit.');
        }
        
        \Illuminate\Support\Facades\Cache::put($cacheKey, $resendCount + 1, now()->addMinutes(5));

        // Generate new OTP
        $otp = $this->otpService->generateOtp();
        $otpHash = $this->otpService->hashOtp($otp);

        AdminOtp::create([
            'admin_id' => $admin->id,
            'otp_hash' => $otpHash,
            'expired_at' => now()->addMinutes(5),
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 255),
            'created_at' => now(),
        ]);

        $this->notificationService->sendAdminOtp($admin->email, $otp, $admin->name);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Kode OTP baru telah dikirim.',
            ]);
        }

        return back()->with('success', 'Kode OTP baru telah dikirim ke email Anda.');
    }

    /**
     * Logout admin.
     */
    public function logout(Request $request): RedirectResponse
    {
        $session = $request->attributes->get('admin_session');

        if ($session) {
            $this->sessionService->revokeAdminSession($session, 'admin_logout');
        }

        $forgetCookie = cookie()->forget(
            'admin_token',
            (string) config('security.auth_cookie.path', config('session.path', '/')),
            config('security.auth_cookie.domain', config('session.domain'))
        );

        return redirect()->route('admin.login')
            ->withCookie($forgetCookie)
            ->with('success', 'Anda telah logout.');
    }

    private function errorResponse(string $message, string $field = 'email'): JsonResponse|RedirectResponse
    {
        if (request()->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message, 'errors' => [$field => [$message]]], 422);
        }
        return back()->withErrors([$field => $message])->withInput();
    }
}
