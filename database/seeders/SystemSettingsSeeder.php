<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key' => 'general.maintenance_mode',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'general',
                'label' => 'Maintenance Mode',
            ],
            [
                'key' => 'general.maintenance_end_time',
                'value' => null,
                'type' => 'datetime',
                'group' => 'general',
                'label' => 'Maintenance End Time',
            ],
            [
                'key' => 'security.session_idle_timeout',
                'value' => '900',
                'type' => 'integer',
                'group' => 'security',
                'label' => 'Session Idle Timeout (Seconds)',
            ],
            [
                'key' => 'security.session_absolute_timeout',
                'value' => '86400',
                'type' => 'integer',
                'group' => 'security',
                'label' => 'Session Absolute Timeout (Seconds)',
            ],
            [
                'key' => 'security.admin_session_absolute_timeout',
                'value' => '43200',
                'type' => 'integer',
                'group' => 'security',
                'label' => 'Admin Session Absolute Timeout (Seconds)',
            ],
            [
                'key' => 'security.max_concurrent_sessions',
                'value' => '5',
                'type' => 'integer',
                'group' => 'security',
                'label' => 'Max Concurrent Sessions',
            ],
            [
                'key' => 'security.enforce_session_ip_binding',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'security',
                'label' => 'Enforce Session IP Binding',
            ],
            [
                'key' => 'security.otp_ttl_minutes',
                'value' => '5',
                'type' => 'integer',
                'group' => 'security',
                'label' => 'OTP Time-To-Live (Minutes)',
            ],
            [
                'key' => 'security.max_otp_attempts',
                'value' => '5',
                'type' => 'integer',
                'group' => 'security',
                'label' => 'Max OTP Attempts (Before Lockout)',
            ],
            [
                'key' => 'security.max_otp_requests_per_hour',
                'value' => '5',
                'type' => 'integer',
                'group' => 'security',
                'label' => 'Max OTP Requests Per Hour',
            ],
        ];

        foreach ($settings as $setting) {
            SystemSetting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
