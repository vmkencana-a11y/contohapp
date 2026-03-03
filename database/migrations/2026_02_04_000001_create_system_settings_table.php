<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, integer, boolean, json
            $table->string('group')->default('general'); // security, general, referral
            $table->string('label')->nullable();
            $table->timestamps();
        });

        // Seed initial settings
        $settings = [
            // Security Group
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
                'key' => 'security.otp_cooldown',
                'value' => '60',
                'type' => 'integer',
                'group' => 'security',
                'label' => 'OTP Resend Cooldown (Seconds)',
            ],
            [
                'key' => 'security.max_login_attempts',
                'value' => '5',
                'type' => 'integer',
                'group' => 'security',
                'label' => 'Max Login Attempts',
            ],
            
            // General Group
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
        ];

        DB::table('system_settings')->insert($settings);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
