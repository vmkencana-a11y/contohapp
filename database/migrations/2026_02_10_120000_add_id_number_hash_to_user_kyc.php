<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add id_number_hash column for duplicate prevention.
     * HMAC-SHA256 hash of ID number — allows UNIQUE INDEX on encrypted data.
     */
    public function up(): void
    {
        Schema::table('user_kyc', function (Blueprint $table) {
            $table->string('id_number_hash', 64)->nullable()->after('id_number');
            $table->unique('id_number_hash', 'uq_user_kyc_id_number_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_kyc', function (Blueprint $table) {
            $table->dropUnique('uq_user_kyc_id_number_hash');
            $table->dropColumn('id_number_hash');
        });
    }
};
