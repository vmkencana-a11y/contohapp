<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create registration blacklists table.
     */
    public function up(): void
    {
        Schema::create('registration_blacklists', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['email', 'domain', 'phone', 'ip']);
            $table->string('value', 255);
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['type', 'value']);
            $table->index('expires_at');

            // Foreign key
            $table->foreign('created_by')
                  ->references('id')->on('admins')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registration_blacklists');
    }
};
