<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add composite indexes for performance optimization.
     * 
     * These indexes optimize:
     * - OTP lookups by email_hash + created_at
     * - Admin OTP lookups by admin_id + created_at  
     * - KYC status filtering with created_at ordering
     * - User session lookups and cleanup
     */
    public function up(): void
    {
        // Pre-user OTPs: optimize email_hash + created_at queries
        $this->addIndexIfNotExists('pre_user_otps', ['email_hash', 'created_at'], 'pre_user_otps_email_hash_created_at_index');
        $this->addIndexIfNotExists('pre_user_otps', ['email_hash', 'verified_at'], 'pre_user_otps_email_hash_verified_at_index');

        // Admin OTPs: optimize admin_id + created_at queries
        $this->addIndexIfNotExists('admin_otps', ['admin_id', 'created_at'], 'admin_otps_admin_id_created_at_index');
        $this->addIndexIfNotExists('admin_otps', ['admin_id', 'verified_at'], 'admin_otps_admin_id_verified_at_index');

        // User KYC: optimize status + created_at for listing/filtering
        $this->addIndexIfNotExists('user_kyc', ['status', 'created_at'], 'user_kyc_status_created_at_index');
        $this->addIndexIfNotExists('user_kyc', ['user_id', 'status'], 'user_kyc_user_id_status_index');

        // User sessions: optimize token lookups and cleanup queries
        $this->addIndexIfNotExists('user_sessions', ['user_id', 'revoked_at'], 'user_sessions_user_id_revoked_at_index');
        $this->addIndexIfNotExists('user_sessions', ['last_activity_at'], 'user_sessions_last_activity_at_index');
    }

    /**
     * Add index if it doesn't already exist.
     */
    private function addIndexIfNotExists(string $table, array $columns, string $indexName): void
    {
        if (!$this->indexExists($table, $indexName)) {
            Schema::table($table, function (Blueprint $table) use ($columns, $indexName) {
                $table->index($columns, $indexName);
            });
        }
    }

    /**
     * Check if an index exists on a table.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIndexIfExists('pre_user_otps', 'pre_user_otps_email_hash_created_at_index');
        $this->dropIndexIfExists('pre_user_otps', 'pre_user_otps_email_hash_verified_at_index');

        $this->dropIndexIfExists('admin_otps', 'admin_otps_admin_id_created_at_index');
        $this->dropIndexIfExists('admin_otps', 'admin_otps_admin_id_verified_at_index');

        $this->dropIndexIfExists('user_kyc', 'user_kyc_status_created_at_index');
        $this->dropIndexIfExists('user_kyc', 'user_kyc_user_id_status_index');

        $this->dropIndexIfExists('user_sessions', 'user_sessions_user_id_revoked_at_index');
        $this->dropIndexIfExists('user_sessions', 'user_sessions_last_activity_at_index');
    }

    /**
     * Drop index if it exists.
     */
    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if ($this->indexExists($table, $indexName)) {
            Schema::table($table, function (Blueprint $tbl) use ($indexName) {
                $tbl->dropIndex($indexName);
            });
        }
    }
};


