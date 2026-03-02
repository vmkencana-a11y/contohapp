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
        DB::statement("ALTER TABLE `kyc_file_access_logs` MODIFY COLUMN `file_type` ENUM('selfie', 'id_card', 'left_side', 'right_side') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting enum changes can be tricky if data exists that doesn't fit the old enum.
        // Assuming we want to revert, we would do:
        // DB::statement("ALTER TABLE `kyc_file_access_logs` MODIFY COLUMN `file_type` ENUM('selfie', 'id_card') NOT NULL");
        // However, this will fail if 'left_side' or 'right_side' data exists.
    }
};
