<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Security event logs for threat detection and forensics.
     */
    public function up(): void
    {
        Schema::create('security_event_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 100)
                  ->comment('e.g., otp_rate_limit, brute_force');
            $table->string('actor_identifier', 100)->nullable()
                  ->comment('email_hash / ip / user_id');
            $table->binary('ip_address')->nullable();
            $table->json('metadata')->nullable();
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])
                  ->default('medium');
            $table->dateTime('created_at');
            
            // Indexes
            $table->index('event_type');
            $table->index('severity');
            $table->index('created_at');
            $table->index('actor_identifier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_event_logs');
    }
};
