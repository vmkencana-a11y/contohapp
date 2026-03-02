<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Repair admins table - add missing columns and fix column types.
     * 
     * This migration handles the case where a previous rollback
     * partially completed, leaving the table in an inconsistent state.
     */
    public function up(): void
    {
        // First, fix column types for encrypted data
        Schema::table('admins', function (Blueprint $table) {
            $table->text('name')->change();
            $table->text('email')->change();
            $table->text('google_2fa_secret')->nullable()->change();
        });

        // Drop unique index on email if exists (TEXT can't have unique index)
        $this->dropIndexIfExists('admins', 'admins_email_unique');

        // Add missing columns if they don't exist
        Schema::table('admins', function (Blueprint $table) {
            if (!Schema::hasColumn('admins', 'email_hash')) {
                $table->string('email_hash', 64)->nullable()->after('email');
            }
            
            if (!Schema::hasColumn('admins', 'working_days')) {
                $table->json('working_days')->nullable()->after('status')
                      ->comment('Array of allowed weekdays: [1,2,3,4,5] where 1=Monday, 7=Sunday');
            }
            
            if (!Schema::hasColumn('admins', 'work_start_time')) {
                $table->time('work_start_time')->nullable()->after('working_days')
                      ->comment('Start of working hours, e.g. 08:00:00');
            }
            
            if (!Schema::hasColumn('admins', 'work_end_time')) {
                $table->time('work_end_time')->nullable()->after('work_start_time')
                      ->comment('End of working hours, e.g. 17:00:00');
            }
        });

        // Add unique index on email_hash if not exists
        $this->addIndexIfNotExists('admins', 'email_hash', 'admins_email_hash_unique', true);

        // Populate email_hash for existing admins if empty
        $admins = DB::table('admins')->whereNull('email_hash')->get();
        foreach ($admins as $admin) {
            // For encrypted emails, we need to decrypt first, then hash
            // But since we can't easily decrypt here, we'll leave it null
            // The application should handle populating this on next login/update
        }
    }

    /**
     * Check if an index exists.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
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

    /**
     * Add index if it doesn't exist.
     */
    private function addIndexIfNotExists(string $table, string $column, string $indexName, bool $unique = false): void
    {
        if (!$this->indexExists($table, $indexName)) {
            Schema::table($table, function (Blueprint $tbl) use ($column, $indexName, $unique) {
                if ($unique) {
                    $tbl->unique($column, $indexName);
                } else {
                    $tbl->index($column, $indexName);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a repair migration, no reversal needed
    }
};
