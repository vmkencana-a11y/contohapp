<?php

namespace App\Http\Controllers\Auth;

use App\Actions\User\CreateUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Models\User;
use App\Services\BlacklistService;
use App\Services\OtpService;
use App\Services\SessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Services\NotificationService;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function __construct(
        private OtpService $otpService,
        private SessionService $sessionService,
        private CreateUserAction $createUserAction,
        private BlacklistService $blacklistService,
        private NotificationService $notificationService
    ) {
    }

    /**
     * Show registration form.
     */
    public function showRegistrationForm(Request $request): View
    {
        return view('auth.register', [
            'referralCode' => $request->query('ref'),
        ]);
    }

    /**
     * Request OTP for registration.
     */
    public function requestOtp(RegisterRequest $request): JsonResponse|RedirectResponse
    {
        try {
            $data = $request->validated();
            
            // Check blacklist
            $blockMessage = $this->blacklistService->checkRegistration(
                $data['email'],
                null, // phone not required for registration
                $request->ip()
            );
            
            if ($blockMessage) {
                return $this->errorResponse($blockMessage);
            }
            
            // Block registration when email already exists.
            if (User::findByEmail($data['email'])) {
                return $this->errorResponse('Email sudah terdaftar. Silakan login.');
            }
            
            // If referral code is invalid or inactive, ignore it (do not block registration flow).
            if (!empty($data['referral_code'])) {
                $code = strtoupper(trim((string) $data['referral_code']));
                $referrer = User::findByReferralCode($code);
                $data['referral_code'] = ($referrer && $referrer->isActive()) ? $code : null;
            }
            
            $otp = $this->otpService->requestRegistrationOtp(
                $data['email'],
                $data['name'],
                $data['referral_code'] ?? null
            );
            
            // Send OTP via email
            $this->notificationService->sendOtp(
                $data['email'],
                $otp,
                $data['name']
            );
            
            // SECURITY: Only show OTP in local development environment
            if (app()->isLocal()) {
                session()->flash('dev_otp', $otp);
            }
            
            // Store registration data in session temporarily
            session()->put('registration_data', [
                'name' => $data['name'],
                'email' => $data['email'],
                'referral_code' => $data['referral_code'] ?? null,
            ]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Kode OTP telah dikirim ke email Anda.',
                    'redirect' => route('register.verify-otp'),
                ]);
            }
            
            return redirect()->route('register.verify-otp')
                ->with('success', 'Kode OTP telah dikirim ke email Anda.');
                
        } catch (\Exception $e) {
            report($e);
            return $this->errorResponse('Terjadi kesalahan. Silakan coba lagi.');
        }
    }

    /**
     * Show OTP verification form for registration.
     */
    public function showVerifyOtpForm(): View|RedirectResponse
    {
        $registrationData = session('registration_data');
        
        if (!$registrationData) {
            return redirect()->route('register')
                ->withErrors(['email' => 'Silakan mulai proses registrasi ulang.']);
        }
        
        return view('auth.register-verify-otp', [
            'email' => $registrationData['email'],
            'devOtp' => session('dev_otp'),
        ]);
    }

    /**
     * Verify OTP and complete registration.
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse|RedirectResponse
    {
        try {
            $data = $request->validated();
            
            // Verify OTP - this returns the OTP record with registration data
            $otpRecord = $this->otpService->verifyOtp($data['email'], $data['otp']);
            
            // Create user
            $user = $this->createUserAction->execute($otpRecord);
            
            // Clear registration session data
            session()->forget('registration_data');

            // Prevent Laravel session fixation after privilege change (user created + auto-login).
            $request->session()->regenerate();
            $request->session()->regenerateToken();
            
            // Create session (auto-login)
            $sessionData = $this->sessionService->createUserSession($user);
            
            $cookieMinutes = $this->sessionService->resolveCookieLifetimeMinutes(
                $sessionData['session']->absolute_timeout
            );

            $cookiePath = (string) config('security.auth_cookie.path', config('session.path', '/'));
            $cookieDomain = config('security.auth_cookie.domain', config('session.domain'));
            $cookieSecure = (bool) config('security.auth_cookie.secure', (bool) config('session.secure', true));
            $cookieHttpOnly = (bool) config('security.auth_cookie.http_only', (bool) config('session.http_only', true));
            $cookieSameSite = (string) config('security.auth_cookie.same_site', 'strict');

            $cookie = cookie(
                'session_token',
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
                    'success' => true,
                    'message' => 'Registrasi berhasil! Selamat datang.',
                    'redirect' => route('dashboard'),
                ])->withCookie($cookie);
            }

            return redirect()->route('dashboard')
                ->withCookie($cookie)
                ->with('success', 'Registrasi berhasil! Selamat datang di Sekuota.');
                
        } catch (\Exception $e) {
            report($e);
            return $this->errorResponse('Terjadi kesalahan. Silakan coba lagi.');
        }
    }

    /**
     * Resend OTP for registration verification flow.
     */
    public function resendOtp(Request $request): JsonResponse|RedirectResponse
    {
        $registrationData = session('registration_data');

        if (!$registrationData) {
            return redirect()->route('register')
                ->withErrors(['email' => 'Sesi pendaftaran tidak ditemukan. Silakan daftar ulang.']);
        }

        try {
            $otp = $this->otpService->requestRegistrationOtp(
                $registrationData['email'],
                $registrationData['name'],
                $registrationData['referral_code'] ?? null
            );

            $this->notificationService->sendOtp(
                $registrationData['email'],
                $otp,
                $registrationData['name']
            );

            if (app()->isLocal()) {
                session()->flash('dev_otp', $otp);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Kode OTP telah dikirim ke email Anda.',
                    'redirect' => route('register.verify-otp'),
                ]);
            }

            return redirect()->route('register.verify-otp')
                ->with('success', 'Kode OTP baru telah dikirim ke email Anda.');
        } catch (\Exception $e) {
            report($e);
            return $this->errorResponse('Terjadi kesalahan. Silakan coba lagi.');
        }
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
