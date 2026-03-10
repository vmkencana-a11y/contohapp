<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\KycStorageService;
use App\Services\LoggingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SystemSettingsController extends Controller
{
    public function __construct(
        private LoggingService $logger,
        private KycStorageService $kycStorage,
    ) {}

    public function index(): View
    {
        $this->ensureCoreGeneralSettings();

        $settings = SystemSetting::all()->groupBy('group');

        return view('admin.settings.index', [
            'groupedSettings' => $settings,
            'kycStorageInfo' => $this->kycStorage->getDriverInfo(),
        ]);
    }

    /**
     * Ensure required general settings rows exist for settings UI rendering.
     */
    private function ensureCoreGeneralSettings(): void
    {
        SystemSetting::firstOrCreate(
            ['key' => 'general.maintenance_mode'],
            [
                'value' => '0',
                'type' => 'boolean',
                'group' => 'general',
                'label' => 'Maintenance Mode',
            ]
        );

        SystemSetting::firstOrCreate(
            ['key' => 'general.maintenance_end_time'],
            [
                'value' => null,
                'type' => 'datetime',
                'group' => 'general',
                'label' => 'Maintenance End Time',
            ]
        );

        SystemSetting::firstOrCreate(
            ['key' => 'security.enforce_session_ip_binding'],
            [
                'value' => '0',
                'type' => 'boolean',
                'group' => 'security',
                'label' => 'Enforce Session IP Binding',
            ]
        );

        // Ensure core security settings exist so they can be managed via the UI.
        SystemSetting::firstOrCreate(
            ['key' => 'security.session_idle_timeout'],
            [
                'value' => '900',
                'type' => 'integer',
                'group' => 'security',
                'label' => 'Session Idle Timeout (Seconds)',
            ]
        );

        SystemSetting::firstOrCreate(
            ['key' => 'security.session_absolute_timeout'],
            [
                'value' => '86400',
                'type' => 'integer',
                'group' => 'security',
                'label' => 'Session Absolute Timeout (Seconds)',
            ]
        );

        SystemSetting::firstOrCreate(
            ['key' => 'security.admin_session_absolute_timeout'],
            [
                'value' => '43200',
                'type' => 'integer',
                'group' => 'security',
                'label' => 'Admin Session Absolute Timeout (Seconds)',
            ]
        );

        SystemSetting::firstOrCreate(
            ['key' => 'security.max_concurrent_sessions'],
            [
                'value' => '5',
                'type' => 'integer',
                'group' => 'security',
                'label' => 'Max Concurrent Sessions',
            ]
        );
    }

    public function update(Request $request): RedirectResponse
    {
        $inputs = $request->input('settings', []);

        // Define expected types for settings validation
        $settingTypes = [
            // Security settings (integers in seconds or counts)
            'security.session_idle_timeout' => 'integer',
            'security.session_absolute_timeout' => 'integer',
            'security.admin_session_absolute_timeout' => 'integer',
            'security.max_concurrent_sessions' => 'integer',
            'security.max_login_attempts' => 'integer',
            'security.lockout_duration' => 'integer',
            'security.enforce_session_ip_binding' => 'boolean',
            // Boolean settings
            'general.maintenance_mode' => 'boolean',
            'general.maintenance_end_time' => 'datetime',
            'email.enabled' => 'boolean',
            'whatsapp.enabled' => 'boolean',
            'notification.registration_enabled' => 'boolean',
            // String settings (URLs, emails, etc.)
            'site.name' => 'string',
            'site.url' => 'url',
            'site.email' => 'email',
            'kyc.email' => 'email',
            // Google OAuth
            'google.oauth_enabled' => 'boolean',
            // KYC Storage
            'kyc_storage.driver' => 'kyc_driver',
        ];

        // Validate and sanitize inputs
        $integerMinimums = [
            'security.session_idle_timeout' => 300,      // 5 minutes
            'security.session_absolute_timeout' => 3600, // 1 hour
            'security.admin_session_absolute_timeout' => 3600, // 1 hour
            'security.max_concurrent_sessions' => 1,
        ];

        $sanitized = [];
        foreach ($inputs as $key => $value) {
            if (!array_key_exists($key, $settingTypes)) {
                continue; // Reject unknown settings keys
            }
            $type = $settingTypes[$key];
            
            switch ($type) {
                case 'integer':
                    if (!is_numeric($value) || (int)$value < 0) {
                        return back()->withErrors(["settings.{$key}" => "Setting {$key} harus berupa angka positif."]);
                    }
                    $intValue = (int) $value;
                    $minimum = $integerMinimums[$key] ?? 0;
                    if ($intValue < $minimum) {
                        return back()->withErrors([
                            "settings.{$key}" => "Setting {$key} minimal {$minimum} detik.",
                        ]);
                    }
                    $sanitized[$key] = $intValue;
                    break;
                case 'boolean':
                    // Checkboxes send "on", "1", or nothing
                    $sanitized[$key] = in_array($value, ['1', 'on', 'true', true], true) ? '1' : '0';
                    break;
                case 'url':
                    if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                        return back()->withErrors(["settings.{$key}" => "Setting {$key} harus berupa URL valid."]);
                    }
                    $sanitized[$key] = $value;
                    break;
                case 'datetime':
                    if (!empty($value) && !strtotime($value)) {
                        return back()->withErrors(["settings.{$key}" => "Setting {$key} harus berupa tanggal & waktu valid."]);
                    }
                    $sanitized[$key] = empty($value) ? null : date('Y-m-d H:i:s', strtotime($value));
                    break;
                case 'email':
                    if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        return back()->withErrors(["settings.{$key}" => "Setting {$key} harus berupa email valid."]);
                    }
                    $sanitized[$key] = $value;
                    break;
                case 'kyc_driver':
                    if (!in_array($value, ['local', 's3'], true)) {
                        return back()->withErrors(["settings.{$key}" => "Pilihan driver tidak valid."]);
                    }
                    if ($value === 's3' && !$this->kycStorage->isS3Configured()) {
                        return back()->withErrors(["settings.{$key}" => "Konfigurasi secret S3 belum lengkap. Lengkapi dulu data `s3_kyc` di Secret Manager."]);
                    }
                    $sanitized[$key] = $value;
                    break;
                default:
                    $sanitized[$key] = (string) $value;
            }
        }

        $adminId = (int) optional($request->attributes->get('admin'))->id;
        if ($adminId <= 0) {
            abort(401, 'Admin tidak terautentikasi.');
        }

        DB::transaction(function () use ($sanitized, $adminId) {
            foreach ($sanitized as $key => $value) {
                SystemSetting::setValue($key, $value);
            }

            $this->logger->logAdminActivity(
                $adminId,
                'settings.update',
                'SystemSetting',
                'global',
                'Updated system settings'
            );
        });

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }

    /**
     * Test KYC S3 connection (AJAX).
     */
    public function testKycStorage(): JsonResponse
    {
        $result = $this->kycStorage->testConnection();
        $adminId = (int) optional(request()->attributes->get('admin'))->id;
        if ($adminId <= 0) {
            abort(401, 'Admin tidak terautentikasi.');
        }

        $this->logger->logAdminActivity(
            $adminId,
            'settings.kyc_storage.test',
            'SystemSetting',
            'kyc_storage',
            'Tested KYC S3 connection: ' . ($result['success'] ? 'success' : 'failed')
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
