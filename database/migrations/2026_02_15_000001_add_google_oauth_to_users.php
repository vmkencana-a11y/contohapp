<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add Google OAuth columns to users table.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Google's unique user identifier (sub claim from id_token)
            $table->string('google_sub', 255)->nullable()->unique()->after('email_hash');
            // When Google account was linked
            $table->dateTime('google_linked_at')->nullable()->after('google_sub');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_sub', 'google_linked_at']);
        });
    }
};
