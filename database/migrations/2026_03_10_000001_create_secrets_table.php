<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secrets', function (Blueprint $table) {
            $table->id();
            $table->string('service', 100);
            $table->string('secret_key', 100);
            $table->text('encrypted_value');
            $table->binary('iv');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['service', 'secret_key']);
            $table->index(['service', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secrets');
    }
};
