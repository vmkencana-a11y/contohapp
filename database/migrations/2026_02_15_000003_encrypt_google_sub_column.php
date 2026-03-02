<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Encrypt google_sub: convert to binary, drop old unique index,
     * add google_sub_hash with unique index for lookups.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop old unique index on google_sub
            $table->dropUnique(['google_sub']);
            // Change google_sub to binary for encrypted storage
            $table->binary('google_sub')->nullable()->change();
            // Add hash column for lookups (same pattern as email_hash)
            $table->char('google_sub_hash', 64)->nullable()->unique()->after('google_sub');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['google_sub_hash']);
            $table->dropColumn('google_sub_hash');
            $table->string('google_sub', 255)->nullable()->unique()->change();
        });
    }
};
