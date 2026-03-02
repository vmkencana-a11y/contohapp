<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create KYC file access logs table for audit trail.
     */
    public function up(): void
    {
        Schema::create('kyc_file_access_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_kyc_id');
            $table->unsignedBigInteger('accessed_by')
                  ->comment('Admin ID who accessed the file');
            $table->string('role', 32)->nullable()
                  ->comment('Role of the accessor');
            $table->enum('file_type', ['selfie', 'id_card']);
            $table->enum('action', ['view', 'decrypt', 'download']);
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at');
            
            // Indexes
            $table->index('user_kyc_id');
            $table->index('accessed_by');
            $table->index('created_at');
            
            // Foreign keys
            $table->foreign('user_kyc_id')
                  ->references('id')->on('user_kyc')
                  ->onDelete('cascade');
            $table->foreign('accessed_by')
                  ->references('id')->on('admins')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kyc_file_access_logs');
    }
};
