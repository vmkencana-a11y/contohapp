<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add encryption and security columns to user_kyc table.
     */
    public function up(): void
    {
        Schema::table('user_kyc', function (Blueprint $table) {
            // Encrypted file keys (envelope encryption)
            $table->text('encrypted_selfie_key')->nullable()
                  ->after('id_card_path')
                  ->comment('AES-256 encrypted file key for selfie');
            $table->text('encrypted_id_card_key')->nullable()
                  ->after('encrypted_selfie_key')
                  ->comment('AES-256 encrypted file key for ID card');
            
            // Key version for rotation support
            $table->unsignedTinyInteger('key_version')->default(1)
                  ->after('encrypted_id_card_key')
                  ->comment('Master key version used for encryption');
            
            // Security flags
            $table->boolean('breach_flag')->default(false)
                  ->after('key_version')
                  ->comment('Flag for breach/incident response');
            
            // Add revoked status if not exists
            // Note: This requires raw SQL for ENUM modification
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_kyc', function (Blueprint $table) {
            $table->dropColumn([
                'encrypted_selfie_key',
                'encrypted_id_card_key',
                'key_version',
                'breach_flag',
            ]);
        });
    }
};
