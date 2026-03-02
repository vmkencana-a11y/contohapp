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
        DB::table('system_settings')->insertOrIgnore([
            'key' => 'kyc.email',
            'value' => '', // Default to empty
            'type' => 'email',
            'group' => 'kyc',
            'label' => 'KYC Notification Email',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('system_settings')->where('key', 'kyc.email')->delete();
    }
};
