<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds working hours columns and prepares for name/email encryption.
     */
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            // Drop the unique index on email first (MySQL can't have TEXT with unique index)
            $table->dropUnique(['email']);
        });

        Schema::table('admins', function (Blueprint $table) {
            // Change name and email to TEXT for encrypted data storage
            $table->text('name')->change();
            $table->text('email')->change();
            
            // Add email hash for lookup (since encrypted email can't be searched)
            // Make this unique instead of the email column
            $table->string('email_hash', 64)->nullable()->unique()->after('email');
            
            // Working hours configuration
            $table->json('working_days')->nullable()->after('status')
                  ->comment('Array of allowed weekdays: [1,2,3,4,5] where 1=Monday, 7=Sunday');
            $table->time('work_start_time')->nullable()->after('working_days')
                  ->comment('Start of working hours, e.g. 08:00:00');
            $table->time('work_end_time')->nullable()->after('work_start_time')
                  ->comment('End of working hours, e.g. 17:00:00');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropUnique(['email_hash']);
            $table->dropColumn(['email_hash', 'working_days', 'work_start_time', 'work_end_time']);
        });
        
        Schema::table('admins', function (Blueprint $table) {
            // Revert to original column types
            $table->string('name', 100)->change();
            $table->string('email', 150)->change();
            
            // Re-add unique index on email
            $table->unique('email');
        });
    }
};
