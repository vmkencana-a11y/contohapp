<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * User KYC documents and verification status.
     */
    public function up(): void
    {
        Schema::create('user_kyc', function (Blueprint $table) {
            $table->id();
            $table->char('user_id', 10)->unique();
            $table->enum('status', ['pending', 'under_review', 'approved', 'rejected'])
                  ->default('pending');
            $table->enum('id_type', ['ktp', 'sim', 'passport'])->nullable();
            $table->binary('id_number')->nullable()->comment('Encrypted');
            $table->string('selfie_path', 255)->nullable();
            $table->string('id_card_path', 255)->nullable();
            $table->unsignedBigInteger('verified_by')->nullable()
                  ->comment('admin_id');
            $table->dateTime('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            
            // Indexes
            $table->index('status');
            
            // Foreign Keys
            $table->foreign('user_id')->references('id')->on('users')
                  ->onDelete('cascade');
            $table->foreign('verified_by')->references('id')->on('admins')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_kyc');
    }
};
