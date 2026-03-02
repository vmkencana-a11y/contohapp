<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Admin accounts with Argon2id password and optional Google 2FA.
     */
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 150)->unique();
            $table->string('password', 255)->comment('Argon2id hash');
            $table->binary('google_2fa_secret')->nullable()
                  ->comment('Encrypted TOTP secret');
            $table->enum('status', ['active', 'suspended'])->default('active');
            $table->dateTime('last_login_at')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            
            // Indexes
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
