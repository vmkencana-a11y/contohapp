<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\BlacklistService;
use App\Services\GoogleOAuthService;
use App\Services\LoggingService;
use App\Services\NotificationService;
use App\Services\OtpService;
use App\Services\SessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GoogleAuthController extends Controller
{
    public function __construct(
        private GoogleOAuthService $googleService,
        private SessionService $sessionService,
        private OtpService $otpService,
        private NotificationService $notificationService,
        private BlacklistService $blacklistService,
        private LoggingService $logger,
    ) {
    }

    /**
     * Redirect to Google's authorization endpoint.
     * Generates PKCE code_verifier + state, stores in session.
     */
    public function redirect(Request $request): RedirectResponse
    {
        $this->logOAuthDebug('redirect_start', $request, [
            'oauth_enabled' => $this->googleService->isEnabled(),
            'session_cookie_present' => $request->hasCookie((string) config('session.cookie')),
            'session_cookie_name' => (string) config('session.cookie'),
        ]);

        // Check if Google OAuth is enabled
        if (!$this->googleService->isEnabled()) {
            return redirect()->route('login')
                ->with('error', 'Login dengan Google tidak tersedia saat ini.');
        }

        // Generate auth URL with PKCE + state
        $authData = $this->googleService->getAuthUrl();

        // Store PKCE verifier and state in session (server-side only)
        session()->put('google_oauth', [
            'state' => $authData['state'],
            'code_verifier' => $authData['code_verifier'],
        ]);

        $this->logOAuthDebug('redirect_session_saved', $request, [
            'state_hash' => $this->hashValue($authData['state']),
            'code_verifier_hash' => $this->hashValue($authData['code_verifier']),
            'redirect_uri' => (string) config('services.google.redirect_uri'),
            'google_url_host' => parse_url($authData['url'], PHP_URL_HOST),
        ]);

        return redirect()->away($authData['url']);
    }

    /**
     * Handle Google's callback.
     * 
     * Flow:
     * 1. Validate state (anti-CSRF)
     * 2. Exchange code + code_verifier for tokens
     * 3. Verify id_token strictly
     * 4. Match user by google_sub or handle new/existing email
     */
    public function callback(Request $request): RedirectResponse
    {
        $this->logOAuthDebug('callback_start', $request, [
            'has_code' => $request->has('code'),
            'has_state' => $request->has('state'),
            'has_error' => $request->has('error'),
            'google_error' => (string) $request->get('error', ''),
            'session_cookie_present' => $request->hasCookie((string) config('session.cookie')),
        ]);

        // Check if Google OAuth is enabled
        if (!$this->googleService->isEnabled()) {
            return redirect()->route('login')
                ->with('error', 'Login dengan Google tidak tersedia saat ini.');
        }

        // Reject if missing code or state
        if (!$request->has('code') || !$request->has('state')) {
            $this->logger->logSecurityEvent('google_oauth_missing_params', 'anonymous', 'medium', [
                'has_code' => $request->has('code'),
                'has_state' => $request->has('state'),
                'has_error' => $request->has('error'),
                'error' => $request->get('error', ''),
            ]);
            return redirect()->route('login')
                ->with('error', 'Autentikasi Google gagal. Silakan coba lagi.');
        }

        // Validate state (anti-CSRF)
        $oauthSession = session()->pull('google_oauth');
        $expectedState = (string) ($oauthSession['state'] ?? '');
        $incomingState = (string) $request->get('state', '');
        $stateMatch = !empty($expectedState) && !empty($incomingState) && hash_equals($expectedState, $incomingState);

        $this->logOAuthDebug('callback_state_check', $request, [
            'has_oauth_session' => !empty($oauthSession),
            'has_oauth_state' => !empty($expectedState),
            'state_match' => $stateMatch,
            'expected_state_hash' => $this->hashValue($expectedState),
            'incoming_state_hash' => $this->hashValue($incomingState),
        ]);

        if (!$oauthSession || !$stateMatch) {
            $this->logger->logSecurityEvent('google_oauth_state_mismatch', 'anonymous', 'high', [
                'event_category' => 'csrf_attempt',
            ]);
            return redirect()->route('login')
                ->with('error', 'Sesi tidak valid. Silakan coba lagi.');
        }

        try {
            // Exchange code for tokens and verify id_token
            $googleUser = $this->googleService->handleCallback(
                $request->get('code'),
                $oauthSession['code_verifier']
            );
        } catch (\RuntimeException $e) {
            report($e);
            $this->logOAuthDebug('callback_token_error', $request, [
                'error_type' => class_basename($e),
                'error_message' => $e->getMessage(),
            ]);
            $this->logger->logSecurityEvent('google_oauth_token_error', 'anonymous', 'high', [
                'error_type' => class_basename($e),
            ]);
            return redirect()->route('login')
                ->with('error', 'Verifikasi Google gagal. Silakan coba lagi.');
        }

        $this->logOAuthDebug('callback_token_verified', $request, [
            'google_sub_hash' => $this->hashValue((string) ($googleUser['sub'] ?? '')),
            'google_email_hash' => $this->hashValue((string) ($googleUser['email'] ?? '')),
        ]);

        // === User Matching Logic ===

        // 1. Try to find user by google_sub (already linked)
        $user = User::findByGoogleSub($googleUser['sub']);
        if ($user) {
            return $this->loginUser($user, 'google_login', $googleUser);
        }

        // 2. Check if email matches an existing user (not yet linked)
        $existingUser = User::findByEmail($googleUser['email']);
        if ($existingUser) {
            // Store Google data in session for OTP linking flow
            session()->put('google_link_data', [
                'google_sub' => $googleUser['sub'],
                'google_name' => $googleUser['name'],
                'google_email' => $googleUser['email'],
                'user_id' => $existingUser->id,
            ]);

            // Send OTP for account linking verification
            try {
                $otp = $this->otpService->requestLoginOtp($googleUser['email']);
                $this->notificationService->sendOtp(
                    $googleUser['email'],
                    $otp,
                    $existingUser->name
                );

                if (app()->isLocal()) {
                    session()->flash('dev_otp', $otp);
                }
            } catch (\Exception $e) {
                report($e);

                return redirect()->route('auth.google.link-form')
                    ->withErrors(['otp' => 'Gagal mengirim kode OTP. Silakan coba kirim ulang.']);
            }

            return redirect()->route('auth.google.link-form')
                ->with('info', 'Akun dengan email ini sudah terdaftar. Verifikasi OTP diperlukan untuk menghubungkan akun Google Anda.');
        }

        // 3. New user — check blacklist first, then create account
        $blockMessage = $this->blacklistService->checkRegistration(
            $googleUser['email'],
            null,
            request()->ip()
        );

        if ($blockMessage) {
            $this->logger->logSecurityEvent('google_register_blocked', 'anonymous', 'high', [
                'reason' => 'blacklist',
                'event_category' => 'registration_blocked',
            ]);
            return redirect()->route('login')
                ->with('error', 'Registrasi tidak dapat dilakukan saat ini.');
        }

        $user = $this->createGoogleUser($googleUser);
        return $this->loginUser($user, 'google_register', $googleUser);
    }

    /**
     * Show the account linking form (OTP verification).
     */
    public function showLinkForm(): View|RedirectResponse
    {
        $linkData = session('google_link_data');

        if (!$linkData) {
            return redirect()->route('login')
                ->with('error', 'Sesi linking sudah kedaluwarsa. Silakan coba lagi.');
        }

        return view('auth.google-link', [
            'email' => $linkData['google_email'],
            'googleName' => $linkData['google_name'],
            'devOtp' => session('dev_otp'),
        ]);
    }

    /**
     * Resend OTP for Google account linking flow.
     */
    public function resendLinkOtp(Request $request): JsonResponse|RedirectResponse
    {
        $linkData = session('google_link_data');

        if (!$linkData) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Sesi linking sudah kedaluwarsa. Silakan login ulang.']);
        }

        try {
            $user = User::find($linkData['user_id']);

            if (!$user || !$user->canLogin()) {
                return $this->errorResponse('Akun tidak valid.');
            }

            $otp = $this->otpService->requestLoginOtp($linkData['google_email']);
            $this->notificationService->sendOtp(
                $linkData['google_email'],
                $otp,
                $user->name
            );

            if (app()->isLocal()) {
                session()->flash('dev_otp', $otp);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Kode OTP telah dikirim ke email Anda.',
                    'redirect' => route('auth.google.link-form'),
                ]);
            }

            return redirect()->route('auth.google.link-form')
                ->with('success', 'Kode OTP baru telah dikirim ke email Anda.');
        } catch (\Exception $e) {
            report($e);
            return $this->errorResponse('Terjadi kesalahan. Silakan coba lagi.');
        }
    }

    /**
     * Verify OTP and link Google account to existing user.
     * This prevents account takeover via email-matching.
     */
    public function linkExisting(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $linkData = session('google_link_data');
        if (!$linkData) {
            return $this->errorResponse('Sesi linking sudah kedaluwarsa. Silakan coba lagi.');
        }

        try {
            // Verify OTP
            $this->otpService->verifyOtp($linkData['google_email'], $request->otp);

            // Find the existing user
            $user = User::find($linkData['user_id']);
            if (!$user || !$user->canLogin()) {
                return $this->errorResponse('Akun tidak valid.');
            }

            // Link Google account
            $user->update([
                'google_sub' => $linkData['google_sub'],
                'google_linked_at' => now(),
            ]);

            // Clear linking session
            session()->forget('google_link_data');

            // Log the linking event
            $this->logger->logSecurityEvent('google_account_linked', (string) $user->id, 'medium', [
                'google_sub' => substr($linkData['google_sub'], 0, 8) . '***',
                'event_category' => 'account_link',
            ]);

            // Login the user
            return $this->loginUser($user, 'google_link_complete', [
                'sub' => $linkData['google_sub'],
                'email' => $linkData['google_email'],
                'name' => $linkData['google_name'],
            ]);

        } catch (\Exception $e) {
            report($e);
            return $this->errorResponse('Kode OTP tidak valid atau sudah kedaluwarsa.');
        }
    }

    /**
     * Create a new user from Google profile data.
     */
    private function createGoogleUser(array $googleUser): User
    {
        $user = User::create([
            'name' => $googleUser['name'],
            'email' => $googleUser['email'],
            'google_sub' => $googleUser['sub'],
            'google_linked_at' => now(),
            'status' => 'active',
            'level' => 'ritel',
            'referral_code' => User::generateReferralCode(),
        ]);

        $this->logger->logSecurityEvent('google_user_created', (string) $user->id, 'low', [
            'google_sub' => substr($googleUser['sub'], 0, 8) . '***',
            'event_category' => 'registration',
            'method' => 'google_oauth',
        ]);

        return $user;
    }

    /**
     * Login user, create session, set secure cookie, and redirect.
     */
    private function loginUser(User $user, string $eventType, array $googleUser): RedirectResponse
    {
        if (!$user->canLogin()) {
            return redirect()->route('login')
                ->with('error', 'Akun Anda tidak aktif. Hubungi customer service.');
        }

        // Update last login
        $user->update(['last_login_at' => now()]);

        // Regenerate session (prevent fixation)
        session()->regenerate();
        session()->regenerateToken();

        // Create session token
        $sessionData = $this->sessionService->createUserSession($user);

        // Log the event
        $this->logger->logSecurityEvent($eventType, (string) $user->id, 'low', [
            'google_sub' => substr($googleUser['sub'], 0, 8) . '***',
            'ip' => request()->ip(),
        ]);

        // Set session cookie aligned with app session cookie configuration.
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

        return redirect()->route('dashboard')
            ->withCookie($cookie)
            ->with('success', 'Selamat datang!');
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

        return back()->withErrors(['otp' => $message]);
    }

    private function logOAuthDebug(string $step, Request $request, array $context = []): void
    {
        if (!(bool) config('services.google.debug', false)) {
            return;
        }

        logger()->info('google_oauth_debug', array_merge([
            'step' => $step,
            'host' => $request->getHost(),
            'scheme' => $request->getScheme(),
            'path' => $request->path(),
            'method' => $request->method(),
            'session_driver' => (string) config('session.driver'),
            'session_secure' => (bool) config('session.secure', false),
            'session_same_site' => (string) config('session.same_site', 'lax'),
            'session_domain' => config('session.domain'),
            'session_id_hash' => $this->hashValue($request->session()->getId()),
            'app_url' => (string) config('app.url'),
            'google_redirect_uri' => (string) config('services.google.redirect_uri'),
            'x_forwarded_proto' => (string) $request->headers->get('x-forwarded-proto', ''),
            'x_forwarded_host' => (string) $request->headers->get('x-forwarded-host', ''),
        ], $context));
    }

    private function hashValue(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        return substr(hash('sha256', $value), 0, 16);
    }
}
