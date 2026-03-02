<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * User status change logs for compliance and audit.
     */
    public function up(): void
    {
        Schema::create('user_status_logs', function (Blueprint $table) {
            $table->id();
            $table->char('user_id', 10);
            $table->enum('action', ['activated', 'suspended', 'banned', 'reactivated']);
            $table->enum('changed_by_type', ['admin', 'system']);
            $table->char('changed_by_id', 10)->nullable()
                  ->comment('admin_id if by admin');
            $table->enum('old_status', ['active', 'inactive', 'suspended', 'banned'])->nullable();
            $table->enum('new_status', ['active', 'inactive', 'suspended', 'banned']);
            $table->string('reason', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('created_at');
            
            // Indexes
            $table->index(['user_id', 'action']);
            $table->index('created_at');
            $table->index(['changed_by_type', 'changed_by_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_status_logs');
    }
};
