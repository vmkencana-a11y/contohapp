<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('system_settings')) {
            return;
        }

        $exists = DB::table('system_settings')
            ->where('key', 'general.maintenance_end_time')
            ->exists();

        if (!$exists) {
            DB::table('system_settings')->insert([
                'key' => 'general.maintenance_end_time',
                'value' => null,
                'type' => 'datetime',
                'group' => 'general',
                'label' => 'Maintenance End Time',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('system_settings')) {
            return;
        }

        DB::table('system_settings')
            ->where('key', 'general.maintenance_end_time')
            ->delete();
    }
};
