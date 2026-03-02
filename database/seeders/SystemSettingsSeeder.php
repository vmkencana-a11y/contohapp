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
                'key' => 'security.max_concurrent_sessions',
                'value' => '5',
                'type' => 'integer',
                'group' => 'security',
                'label' => 'Max Concurrent Sessions',
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
