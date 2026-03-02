<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Admin activity logs for complete audit trail.
     */
    public function up(): void
    {
        Schema::create('admin_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id');
            $table->string('action', 100)->comment('e.g., user.suspend, kyc.approve');
            $table->string('subject_type', 50)->comment('e.g., User, KYC, Settings');
            $table->string('subject_id', 50)->nullable()
                  ->comment('ID of affected entity');
            $table->string('reason', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->binary('ip_address')->nullable();
            $table->dateTime('created_at');
            
            // Indexes
            $table->index(['admin_id', 'action']);
            $table->index(['subject_type', 'subject_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_activity_logs');
    }
};
