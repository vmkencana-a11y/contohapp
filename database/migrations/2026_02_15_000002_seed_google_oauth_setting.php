<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\SystemSetting;

return new class extends Migration
{
    /**
     * Seed the Google OAuth system setting.
     */
    public function up(): void
    {
        SystemSetting::firstOrCreate(
            ['key' => 'google.oauth_enabled'],
            [
                'value' => '0',
                'type' => 'boolean',
                'group' => 'google',
                'label' => 'Enable Google OAuth Login',
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        SystemSetting::where('key', 'google.oauth_enabled')->delete();
    }
};
