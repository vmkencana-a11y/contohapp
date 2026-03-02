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
        Schema::table('user_kyc', function (Blueprint $table) {
            $table->string('left_side_path')->nullable()->after('id_card_path');
            $table->text('encrypted_left_side_key')->nullable()->after('encrypted_id_card_key');
            $table->string('right_side_path')->nullable()->after('left_side_path');
            $table->text('encrypted_right_side_key')->nullable()->after('encrypted_left_side_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_kyc', function (Blueprint $table) {
            $table->dropColumn([
                'left_side_path',
                'encrypted_left_side_key',
                'right_side_path',
                'encrypted_right_side_key',
            ]);
        });
    }
};
