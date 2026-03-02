<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Models\User;
use App\Services\OtpService;
use App\Services\SessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(
        private OtpService $otpService,
        private SessionService $sessionService
    ) {
    }

    /**
     * Show login form.
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Request OTP for login.
     */
    public function requestOtp(LoginRequest $request): JsonResponse|RedirectResponse
    {
        try {
            $email = $request->validated('email');
            $user = User::findByEmail($email);
            
            if (!$user) {
                // SECURITY: Use generic message to prevent user enumeration
                return $this->errorResponse('Email atau password salah.');
            }
            
            if (!$user->canLogin()) {
                // SECURITY: Use generic message to prevent account status enumeration
                return $this->errorResponse('Akun tidak dapat diakses. Silakan hubungi dukungan.');
            }
            
            $otp = $this->otpService->requestLoginOtp($email);
            
            // Send OTP via email
            app(\App\Services\NotificationService::class)->sendOtp($email, $otp, $user->name);
            
            // SECURITY: Only show OTP in development environment
            if (app()->isLocal()) {
                session()->flash('dev_otp', $otp);
            }

            session()->put('otp_login_email', $email);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Kode OTP telah dikirim ke email Anda.',
                    'redirect' => route('verify-otp'),
                ]);
            }
            
            return redirect()->route('verify-otp')
                ->with('success', 'Kode OTP telah dikirim ke email Anda.');
                
        } catch (\Exception $e) {
            report($e);
            return $this->errorResponse('Terjadi kesalahan. Silakan coba lagi.');
        }
    }

    /**
     * Show OTP verification form.
     */
    public function showVerifyOtpForm(): View|RedirectResponse
    {
        $email = session('otp_login_email');

        if (!$email) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Sesi OTP tidak ditemukan. Silakan login ulang.']);
        }

        return view('auth.verify-otp', [
            'email' => $email,
            'devOtp' => session('dev_otp'),
        ]);
    }

    /**
     * Verify OTP and login.
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse|RedirectResponse
    {
        try {
            $data = $request->validated();
            $email = session('otp_login_email', $data['email']);
            $otp = $data['otp'];
            
            // Verify OTP
            $this->otpService->verifyOtp($email, $otp);
            
            // Find user
            $user = User::findByEmail($email);
            
            if (!$user || !$user->canLogin()) {
                return $this->errorResponse('Akun tidak valid.');
            }
            
            // Update last login
            $user->update(['last_login_at' => now()]);
            
            // Create session
            $sessionData = $this->sessionService->createUserSession($user);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Login berhasil!',
                    'redirect' => route('dashboard'),
                ]);
            }
            
            // Set session cookie with security flags
            $cookie = cookie('session_token', $sessionData['token'], 60 * 24, '/', null, true, true, false, 'Strict');

            session()->forget('otp_login_email');
            
            return redirect()->route('dashboard')
                ->withCookie($cookie)
                ->with('success', 'Selamat datang kembali!');
                
        } catch (\Exception $e) {
            report($e);
            return $this->errorResponse('Terjadi kesalahan. Silakan coba lagi.');
        }
    }

    /**
     * Resend OTP for login verification flow.
     */
    public function resendOtp(Request $request): JsonResponse|RedirectResponse
    {
        $email = session('otp_login_email');

        if (!$email) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Sesi OTP tidak ditemukan. Silakan login ulang.']);
        }

        try {
            $user = User::findByEmail($email);

            // Keep response generic to avoid exposing account existence.
            if ($user && $user->canLogin()) {
                $otp = $this->otpService->requestLoginOtp($email);
                app(\App\Services\NotificationService::class)->sendOtp($email, $otp, $user->name);

                if (app()->isLocal()) {
                    session()->flash('dev_otp', $otp);
                }
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Kode OTP telah dikirim ke email Anda.',
                    'redirect' => route('verify-otp'),
                ]);
            }

            return redirect()->route('verify-otp')
                ->with('success', 'Kode OTP baru telah dikirim ke email Anda.');
        } catch (\Exception $e) {
            report($e);
            return $this->errorResponse('Terjadi kesalahan. Silakan coba lagi.');
        }
    }

    /**
     * Logout.
     */
    public function logout(Request $request): RedirectResponse
    {
        $session = $request->attributes->get('session');
        
        if ($session) {
            $this->sessionService->revokeSession($session);
        }
        
        return redirect()->route('login')
            ->withCookie(cookie()->forget('session_token'))
            ->with('success', 'Anda telah logout.');
    }

    /**
     * Error response helper.
     */
    private function errorResponse(string $message): JsonResponse|RedirectResponse
    {
        if (request()->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 422);
        }
        
        return back()->withErrors(['email' => $message])->withInput();
    }
}
