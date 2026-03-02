<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Main users table - only contains verified users (after OTP).
     * Uses CHAR(10) ID format: IDxxxxxxxx for anti-enumeration.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->char('id', 10)->primary()->comment('Format: IDxxxxxxxx');
            $table->binary('name')->comment('Encrypted name');
            $table->binary('email')->comment('Encrypted email');
            $table->binary('phone')->nullable()->comment('Encrypted phone');
            $table->char('email_hash', 64)->unique()->comment('SHA-256 for lookup');
            
            // Status Management
            $table->enum('status', ['active', 'inactive', 'suspended', 'banned'])
                  ->default('active');
            
            // Level & Referral
            $table->enum('level', ['ritel', 'reseller'])->default('ritel');
            $table->char('referral_code', 8)->unique();
            $table->char('referred_by', 10)->nullable()->index();
            $table->dateTime('referred_at')->nullable();
            
            // Status History (Explicit Fields)
            $table->dateTime('status_changed_at')->nullable();
            $table->char('status_changed_by', 10)->nullable()
                  ->comment('admin_id or system');
            $table->text('status_reason')->nullable();
            
            $table->dateTime('suspended_at')->nullable();
            $table->char('suspended_by', 10)->nullable();
            $table->text('suspended_reason')->nullable();
            
            $table->dateTime('banned_at')->nullable();
            $table->char('banned_by', 10)->nullable();
            $table->text('banned_reason')->nullable();
            
            // Activity
            $table->dateTime('last_login_at')->nullable();
            
            // Timestamps
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            
            // Indexes
            $table->index('status');
            $table->index('level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
