<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Pre-user OTP storage before user creation.
     * Prevents account spam by requiring OTP verification before creating user.
     */
    public function up(): void
    {
        Schema::create('pre_user_otps', function (Blueprint $table) {
            $table->id();
            $table->binary('email')->comment('Encrypted email');
            $table->char('email_hash', 64)->index()->comment('SHA-256 hash for lookup');
            $table->binary('name')->nullable()->comment('Encrypted name (for registration)');
            $table->char('referral_code', 8)->nullable();
            $table->char('otp_hash', 64)->comment('SHA-256 hash of OTP');
            $table->dateTime('expired_at');
            $table->dateTime('verified_at')->nullable();
            
            // Brute Force Protection
            $table->tinyInteger('attempt_count')->unsigned()->default(0)
                  ->comment('Failed verification attempts');
            $table->dateTime('last_attempt_at')->nullable()
                  ->comment('Last verification attempt');
            $table->dateTime('locked_until')->nullable()->index()
                  ->comment('Brute force lock expiry');
            
            $table->binary('ip_address')->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->dateTime('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pre_user_otps');
    }
};
