<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Add 'processing' status to user_kyc ENUM for async document processing.
     */
    public function up(): void
    {
        // Modify ENUM to include 'processing'
        DB::statement("ALTER TABLE `user_kyc` MODIFY COLUMN `status` ENUM('processing', 'pending', 'under_review', 'approved', 'rejected') NOT NULL DEFAULT 'processing'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original ENUM (without processing)
        DB::statement("ALTER TABLE `user_kyc` MODIFY COLUMN `status` ENUM('pending', 'under_review', 'approved', 'rejected') NOT NULL DEFAULT 'pending'");
    }
};
