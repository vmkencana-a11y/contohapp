<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * User login activity logs for security auditing.
     */
    public function up(): void
    {
        Schema::create('user_login_logs', function (Blueprint $table) {
            $table->id();
            $table->char('user_id', 10);
            $table->string('action', 50)->comment('login, logout, otp_verified');
            $table->binary('ip_address')->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->json('metadata')->nullable()
                  ->comment('Additional context, NO sensitive data');
            $table->dateTime('created_at');
            
            // Indexes
            $table->index(['user_id', 'action']);
            $table->index('created_at');
            // Note: ip_address is BINARY, cannot be indexed directly
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_login_logs');
    }
};
