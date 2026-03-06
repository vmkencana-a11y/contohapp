<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Admin sessions with configurable timeouts.
     * Mirrors user_sessions table but with stricter defaults (12h absolute timeout).
     */
    public function up(): void
    {
        Schema::create('admin_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->onDelete('cascade');
            $table->char('token_hash', 64)->unique()->comment('SHA-256 hash of session token');
            $table->binary('ip_address')->nullable();
            $table->string('user_agent', 255)->nullable();

            // Activity Tracking
            $table->dateTime('last_activity_at');
            $table->dateTime('created_at');

            // Timeout Configuration (PCI-DSS & OJK Compliance)
            $table->unsignedInteger('idle_timeout')->default(900)
                  ->comment('Idle timeout in seconds (15 min)');
            $table->unsignedInteger('absolute_timeout')->default(43200)
                  ->comment('Absolute timeout in seconds (12 hours for admins)');

            // Security & Rotation
            $table->dateTime('last_rotated_at')->nullable()
                  ->comment('Last token rotation');
            $table->dateTime('revoked_at')->nullable()->index();
            $table->string('revoke_reason', 255)->nullable()
                  ->comment('Why session was revoked');

            // Indexes
            $table->index('last_activity_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_sessions');
    }
};
