<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\User\DashboardController;
use App\Http\Controllers\User\KycController;
use App\Http\Controllers\User\KycCaptureController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\ReferralController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\SecretController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\KycReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Landing
Route::get('/', function () {
    return view('welcome');
})->name('home');

/*
|--------------------------------------------------------------------------
| Authentication Routes (Guest Only)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    // Login
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'requestOtp'])->middleware('throttle.auth:5,1')->name('login.request-otp');
    Route::get('/verify-otp', [LoginController::class, 'showVerifyOtpForm'])->name('verify-otp');
    Route::post('/verify-otp', [LoginController::class, 'verifyOtp'])->middleware('throttle.auth:10,1')->name('verify-otp.submit');
    Route::post('/verify-otp/resend', [LoginController::class, 'resendOtp'])->middleware('throttle.auth:3,1')->name('verify-otp.resend');

    // Register
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'requestOtp'])->middleware('throttle.auth:3,1')->name('register.request-otp');
    Route::get('/register/verify-otp', [RegisterController::class, 'showVerifyOtpForm'])->name('register.verify-otp');
    Route::post('/register/verify-otp', [RegisterController::class, 'verifyOtp'])->middleware('throttle.auth:10,1')->name('register.verify-otp.submit');
    Route::post('/register/verify-otp/resend', [RegisterController::class, 'resendOtp'])->middleware('throttle.auth:3,1')->name('register.verify-otp.resend');

    // Google OAuth 2.0
    Route::middleware('throttle:10,1')->group(function () {
        Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
        Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
        Route::get('/auth/google/link', [GoogleAuthController::class, 'showLinkForm'])->name('auth.google.link-form');
        Route::post('/auth/google/link', [GoogleAuthController::class, 'linkExisting'])->name('auth.google.link');
        Route::post('/auth/google/link/resend', [GoogleAuthController::class, 'resendLinkOtp'])->middleware('throttle.auth:3,1')->name('auth.google.link.resend');
    });
});

/*
|--------------------------------------------------------------------------
| Authenticated User Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth.user', 'verify.status'])->group(function () {
    // Logout
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Referral
    Route::get('/referral', [ReferralController::class, 'index'])->name('referral');

    // KYC (Camera-Only)
    Route::get('/kyc', [KycController::class, 'index'])->name('kyc');
    Route::get('/kyc/status', [KycController::class, 'status'])->name('kyc.status');

    // KYC Camera Capture API
    Route::prefix('kyc/capture')->name('kyc.capture.')->middleware('throttle:20,1')->group(function () {
        Route::post('/start', [KycCaptureController::class, 'startSession'])->name('start');
        Route::get('/nonce', [KycCaptureController::class, 'getNonce'])->name('nonce');
        Route::post('/frame', [KycCaptureController::class, 'submitFrame'])->name('frame');
        Route::post('/complete', [KycCaptureController::class, 'complete'])->name('complete');
    });
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    // Guest (login) - 3-Layer Authentication
    Route::middleware(['guest:admin', 'throttle:5,1'])->group(function () {
        // Layer 1: Email + Password
        Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login'])->name('login.submit');
        
        // Layer 2: Email OTP
        Route::get('/verify-otp', [AdminAuthController::class, 'showOtpForm'])->name('verify-otp');
        Route::post('/verify-otp', [AdminAuthController::class, 'verifyOtp'])->name('verify-otp.submit');
        Route::post('/resend-otp', [AdminAuthController::class, 'resendOtp'])->name('resend-otp');
        
        // Layer 3: Google 2FA
        Route::get('/verify-2fa', [AdminAuthController::class, 'show2faForm'])->name('verify-2fa');
        Route::post('/verify-2fa', [AdminAuthController::class, 'verify2fa'])->name('verify-2fa.submit');
    });

    // Authenticated Admin
    Route::middleware(['auth.admin', 'throttle:60,1'])->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        // User Management - requires 'users.view' or 'users.manage' permission
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserManagementController::class, 'index'])
                ->middleware('auth.admin:users.view')
                ->name('index');
            Route::get('/{user}', [UserManagementController::class, 'show'])
                ->middleware('auth.admin:users.view')
                ->name('show');
            Route::post('/{user}/suspend', [UserManagementController::class, 'suspend'])
                ->middleware('auth.admin:users.manage')
                ->name('suspend');
            Route::post('/{user}/ban', [UserManagementController::class, 'ban'])
                ->middleware('auth.admin:users.manage')
                ->name('ban');
            Route::post('/{user}/reactivate', [UserManagementController::class, 'reactivate'])
                ->middleware('auth.admin:users.manage')
                ->name('reactivate');
        });

        // KYC Review - requires 'kyc.view' or 'kyc.manage' permission
        Route::prefix('kyc')->name('kyc.')->group(function () {
            Route::get('/', [KycReviewController::class, 'index'])
                ->middleware('auth.admin:kyc.view')
                ->name('index');
            Route::get('/{kyc}', [KycReviewController::class, 'show'])
                ->middleware('auth.admin:kyc.view')
                ->name('show');
            Route::post('/{kyc}/approve', [KycReviewController::class, 'approve'])
                ->middleware('auth.admin:kyc.manage')
                ->name('approve');
            Route::post('/{kyc}/reject', [KycReviewController::class, 'reject'])
                ->middleware('auth.admin:kyc.manage')
                ->name('reject');
            Route::get('/{kyc}/image/{type}', [KycReviewController::class, 'serveImage'])
                ->middleware('auth.admin:kyc.view')
                ->name('image')
                ->where('type', 'id_card|selfie|left_side|right_side');
            Route::post('/{kyc}/flag-breach', [KycReviewController::class, 'flagBreach'])
                ->middleware('auth.admin:kyc.manage')
                ->name('flag-breach');
        });

        // Admin Profile - all admins can access their own profile
        Route::prefix('profile')->name('profile.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\ProfileController::class, 'edit'])->name('edit');
            Route::put('/', [App\Http\Controllers\Admin\ProfileController::class, 'update'])->name('update');
            Route::put('/password', [App\Http\Controllers\Admin\ProfileController::class, 'updatePassword'])->name('password');
            
            // 2FA
            Route::get('/2fa/setup', [App\Http\Controllers\Admin\ProfileController::class, 'setup2fa'])->name('2fa.setup');
            Route::post('/2fa/confirm', [App\Http\Controllers\Admin\ProfileController::class, 'confirm2fa'])->name('2fa.confirm');
            Route::delete('/2fa', [App\Http\Controllers\Admin\ProfileController::class, 'disable2fa'])->name('2fa.disable');
        });

        // Role Management - requires 'roles.manage' permission
        Route::resource('roles', App\Http\Controllers\Admin\RoleController::class)
            ->except(['show'])
            ->middleware('auth.admin:roles.manage');

        // Admin Management - requires 'admins.manage' permission
        Route::patch('/admins/{admin}/toggle-status', [App\Http\Controllers\Admin\AdminManagementController::class, 'toggleStatus'])
            ->middleware('auth.admin:admins.manage')
            ->name('admins.toggle-status');
        Route::resource('admins', App\Http\Controllers\Admin\AdminManagementController::class)
            ->except(['show'])
            ->middleware('auth.admin:admins.manage');

        // Monitoring - requires 'logs.view' permission
        Route::get('/logs', [App\Http\Controllers\Admin\LogsController::class, 'index'])
            ->middleware('auth.admin:logs.view')
            ->name('logs.index');
        Route::get('/referrals', [App\Http\Controllers\Admin\ReferralController::class, 'index'])
            ->middleware('auth.admin:referrals.view')
            ->name('referrals.index');
        
        // Registration Blacklist - requires settings.manage permission
        Route::resource('blacklist', App\Http\Controllers\Admin\BlacklistController::class)
            ->except(['show'])
            ->middleware('auth.admin:settings.manage');

        // System Settings - requires 'settings.manage' permission
        Route::get('/settings', [App\Http\Controllers\Admin\SystemSettingsController::class, 'index'])
            ->middleware('auth.admin:settings.view')
            ->name('settings.index');
        Route::put('/settings', [App\Http\Controllers\Admin\SystemSettingsController::class, 'update'])
            ->middleware('auth.admin:settings.manage')
            ->name('settings.update');
        Route::post('/settings/kyc-storage/test', [App\Http\Controllers\Admin\SystemSettingsController::class, 'testKycStorage'])
            ->middleware('auth.admin:settings.manage')
            ->name('settings.kyc-storage.test');

        Route::post('/secrets/refresh-cache', [SecretController::class, 'refreshCache'])
            ->middleware('auth.admin:secrets.manage')
            ->name('secrets.refresh-cache');
        Route::get('/secrets', [SecretController::class, 'index'])
            ->middleware('auth.admin:secrets.view')
            ->name('secrets.index');
        Route::get('/secrets/create', [SecretController::class, 'create'])
            ->middleware('auth.admin:secrets.manage')
            ->name('secrets.create');
        Route::post('/secrets', [SecretController::class, 'store'])
            ->middleware('auth.admin:secrets.manage')
            ->name('secrets.store');
        Route::get('/secrets/{secret}/edit', [SecretController::class, 'edit'])
            ->middleware('auth.admin:secrets.manage')
            ->name('secrets.edit');
        Route::put('/secrets/{secret}', [SecretController::class, 'update'])
            ->middleware('auth.admin:secrets.manage')
            ->name('secrets.update');
        Route::delete('/secrets/{secret}', [SecretController::class, 'destroy'])
            ->middleware('auth.admin:secrets.manage')
            ->name('secrets.destroy');
    });
});
