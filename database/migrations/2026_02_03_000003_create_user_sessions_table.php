<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * User sessions with configurable timeouts for security compliance.
     * Implements idle timeout (15 min) and absolute timeout (24 hours).
     */
    public function up(): void
    {
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->char('user_id', 10);
            $table->char('token_hash', 64)->unique()->comment('SHA-256 hash of session token');
            $table->binary('ip_address')->nullable();
            $table->string('user_agent', 255)->nullable();
            
            // Activity Tracking
            $table->dateTime('last_activity_at');
            $table->dateTime('created_at');
            
            // Timeout Configuration (PCI-DSS & OJK Compliance)
            $table->unsignedInteger('idle_timeout')->default(900)
                  ->comment('Idle timeout in seconds (15 min)');
            $table->unsignedInteger('absolute_timeout')->default(86400)
                  ->comment('Absolute timeout (24 hours)');
            
            // Security & Rotation
            $table->dateTime('last_rotated_at')->nullable()
                  ->comment('Last token rotation');
            $table->dateTime('revoked_at')->nullable()->index();
            $table->string('revoke_reason', 255)->nullable()
                  ->comment('Why session was revoked');
            
            // Indexes
            $table->index('user_id');
            $table->index('last_activity_at');
            
            // Foreign Key
            $table->foreign('user_id')->references('id')->on('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};
