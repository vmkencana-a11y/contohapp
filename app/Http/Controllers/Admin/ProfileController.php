<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ResolvesCurrentAdmin;
use App\Http\Controllers\Controller;
use App\Services\LoggingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;

class ProfileController extends Controller
{
    use ResolvesCurrentAdmin;

    public function __construct(
        private LoggingService $logger
    ) {}

    /**
     * Show admin profile page.
     */
    public function index(): View
    {
        $admin = $this->currentAdmin();
        
        return view('admin.profile.index', [
            'admin' => $admin,
        ]);
    }

    /**
     * Show edit profile form.
     */
    public function edit(): View
    {
        $admin = $this->currentAdmin();
        
        return view('admin.profile.edit', [
            'admin' => $admin,
        ]);
    }

    /**
     * Update admin profile.
     */
    public function update(Request $request): RedirectResponse
    {
        $admin = $this->currentAdmin($request);
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100'],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
        ]);

        // Check email uniqueness using email_hash (since email is encrypted)
        $emailHash = hash('sha256', strtolower($validated['email']));
        $existingAdmin = \App\Models\Admin::where('email_hash', $emailHash)
            ->where('id', '!=', $admin->id)
            ->first();
        
        if ($existingAdmin) {
            return back()->withErrors(['email' => 'Email sudah digunakan.'])->withInput();
        }

        $admin->update($validated);

        $this->logger->logAdminActivity(
            (int) $admin->id,
            'profile.updated',
            'Admin',
            (string)$admin->id
        );

        return redirect()->route('admin.profile.edit')
            ->with('success', 'Profil berhasil diperbarui.');
    }

    /**
     * Update admin password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $admin = $this->currentAdmin($request);
        
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:12', 'confirmed',
                \Illuminate\Validation\Rules\Password::min(12)->mixedCase()->numbers()->symbols()],
        ], [
            'current_password.required' => 'Password lama wajib diisi.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        if (!$admin->checkPassword($validated['current_password'])) {
            return back()->withErrors(['current_password' => 'Password lama salah.']);
        }

        $admin->update(['password' => $validated['password']]);

        $this->logger->logAdminActivity(
            (int) $admin->id,
            'password.changed',
            'Admin',
            (string)$admin->id
        );

        return redirect()->route('admin.profile.edit')
            ->with('success', 'Password berhasil diperbarui.');
    }

    /**
     * Show 2FA setup page.
     */
    public function setup2fa(): View|RedirectResponse
    {
        $admin = $this->currentAdmin();
        
        if ($admin->has2faEnabled()) {
            return redirect()->route('admin.profile.edit')
                ->with('info', '2FA sudah aktif. Nonaktifkan terlebih dahulu untuk setup ulang.');
        }

        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();
        
        session(['2fa_secret' => $secret]);
        
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name') . ' Admin',
            $admin->email,
            $secret
        );

        return view('admin.profile.setup-2fa', [
            'secret' => $secret,
            'qrCodeUrl' => $qrCodeUrl,
        ]);
    }

    /**
     * Confirm 2FA setup.
     */
    public function confirm2fa(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $secret = session('2fa_secret');
        
        if (!$secret) {
            return $this->errorResponse('Sesi setup expired. Silakan ulangi.');
        }

        $google2fa = new Google2FA();
        
        if (!$google2fa->verifyKey($secret, $validated['code'])) {
            return $this->errorResponse('Kode verifikasi salah.');
        }

        $admin = $this->currentAdmin($request);
        $admin->update([
            'google_2fa_secret' => $secret, // Model handles encryption
            // 'two_factor_enabled_at' => now(), // Not in fillable/model
        ]);

        session()->forget('2fa_secret');

        $this->logger->logAdminActivity(
            (int) $admin->id,
            'security.2fa_enabled',
            'Admin',
            (string)$admin->id
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => '2FA berhasil diaktifkan!',
                'redirect' => route('admin.profile.edit'),
            ]);
        }

        return redirect()->route('admin.profile.edit')
            ->with('success', 'Google Authenticator berhasil diaktifkan!');
    }

    /**
     * Disable 2FA.
     */
    public function disable2fa(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string'],
        ]);

        $admin = $this->currentAdmin($request);

        if (!$admin->checkPassword($validated['password'])) {
            return $this->errorResponse('Password salah.');
        }

        $admin->update([
            'google_2fa_secret' => null,
            // 'two_factor_enabled_at' => null,
        ]);

        $this->logger->logAdminActivity(
            (int) $admin->id,
            'security.2fa_disabled',
            'Admin',
            (string)$admin->id
        );

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => '2FA berhasil dinonaktifkan.']);
        }

        return redirect()->route('admin.profile.edit')
            ->with('success', '2FA berhasil dinonaktifkan.');
    }

    private function errorResponse(string $message): JsonResponse|RedirectResponse
    {
        if (request()->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }
        return back()->withErrors(['error' => $message]);
    }
}
