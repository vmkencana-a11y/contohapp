<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add session tracking columns for camera-based KYC.
     */
    public function up(): void
    {
        Schema::table('user_kyc', function (Blueprint $table) {
            // Session tracking
            $table->string('session_id', 64)->nullable()->after('id')
                  ->comment('Redis session ID for camera capture');
            
            // Liveness result
            $table->json('liveness_result')->nullable()->after('status')
                  ->comment('Challenges completed and validation info');
            
            // Frame count captured
            $table->unsignedTinyInteger('frame_count')->default(0)->after('liveness_result')
                  ->comment('Number of frames captured');
            
            // Capture method
            $table->enum('capture_method', ['camera', 'upload'])->default('upload')->after('frame_count')
                  ->comment('How documents were captured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_kyc', function (Blueprint $table) {
            $table->dropColumn(['session_id', 'liveness_result', 'frame_count', 'capture_method']);
        });
    }
};
