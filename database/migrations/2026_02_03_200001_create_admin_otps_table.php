<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admin_otps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id');
            $table->string('otp_hash', 64)->comment('SHA-256 hash of OTP');
            $table->dateTime('expired_at');
            $table->dateTime('verified_at')->nullable();
            $table->tinyInteger('attempt_count')->unsigned()->default(0);
            $table->dateTime('last_attempt_at')->nullable();
            $table->dateTime('locked_until')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->dateTime('created_at');

            $table->foreign('admin_id')
                ->references('id')
                ->on('admins')
                ->onDelete('cascade');

            $table->index('admin_id');
            $table->index('expired_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_otps');
    }
};
