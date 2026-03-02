<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * User referrals - auto-activated on registration.
     */
    public function up(): void
    {
        Schema::create('user_referrals', function (Blueprint $table) {
            $table->id();
            $table->char('user_id', 10)->comment('Referred user');
            $table->char('referrer_id', 10)->comment('User who referred');
            $table->dateTime('referred_at');
            $table->enum('status', ['active', 'cancelled'])
                  ->default('active')->comment('Auto-active on registration');
            $table->dateTime('created_at');
            
            // Indexes
            $table->index('user_id');
            $table->index('referrer_id');
            $table->index('status');
            
            // Foreign Keys
            $table->foreign('user_id')->references('id')->on('users')
                  ->onDelete('cascade');
            $table->foreign('referrer_id')->references('id')->on('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_referrals');
    }
};
