<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('system_settings')->insert([
            'key' => 'kyc_storage.driver',
            'value' => 'local',
            'type' => 'string',
            'group' => 'kyc_storage',
            'label' => 'KYC Storage Driver',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('system_settings')
            ->where('key', 'kyc_storage.driver')
            ->delete();
    }
};
