<?php

namespace App\Jobs;

use App\Enums\KycStatusEnum;
use App\Models\UserKyc;
use App\Services\KycEncryptionService;
use App\Services\KycImageService;
use App\Services\KycStorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Async KYC Document Processing Job.
 * 
 * Handles CPU/IO heavy operations:
 * - Image resizing and compression
 * - EXIF stripping
 * - Envelope encryption
 * 
 * This runs in the background to prevent request timeout.
 */
class ProcessKycDocuments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 120;
    public array $backoff = [5, 15, 30];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public UserKyc $kyc,
        public string $tempSelfiePath,
        public string $tempIdCardPath,
        public string $tempLeftSidePath,
        public string $tempRightSidePath
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        KycImageService $imageService,
        KycEncryptionService $encryptionService,
        KycStorageService $storageService
    ): void {
        try {
            // Update status to processing
            $this->kyc->update(['status' => KycStatusEnum::PROCESSING]);

            $storagePath = dirname($this->tempSelfiePath);

            // Process images one-by-one to minimize memory usage
            $types = [
                'selfie' => $this->tempSelfiePath,
                'id_card' => $this->tempIdCardPath,
                'left_side' => $this->tempLeftSidePath,
                'right_side' => $this->tempRightSidePath,
            ];

            $results = [];
            foreach ($types as $type => $tempPath) {
                $content = $this->readTempFileWithRetry($storageService, $tempPath, $type);

                // Process (resize, compress, strip EXIF)
                $processed = $imageService->processFromContent($content);
                unset($content); // Free raw memory

                // Encrypt
                $encrypted = $encryptionService->encryptForStorage($processed);
                unset($processed); // Free processed memory

                // Store encrypted file
                $encPath = $storagePath . "/{$type}.enc";
                $storageService->disk()->put($encPath, $encrypted['content']);

                $results[$type] = [
                    'path' => $encPath,
                    'key' => $encrypted['encrypted_key'],
                    'key_version' => $encrypted['key_version'],
                ];
                unset($encrypted); // Free encrypted memory
            }

            // Update KYC record
            $this->kyc->update([
                'status' => KycStatusEnum::PENDING,
                'selfie_path' => $results['selfie']['path'],
                'id_card_path' => $results['id_card']['path'],
                'left_side_path' => $results['left_side']['path'],
                'right_side_path' => $results['right_side']['path'],
                'encrypted_selfie_key' => $results['selfie']['key'],
                'encrypted_id_card_key' => $results['id_card']['key'],
                'encrypted_left_side_key' => $results['left_side']['key'],
                'encrypted_right_side_key' => $results['right_side']['key'],
                'key_version' => $results['selfie']['key_version'],
            ]);

            // Notify Admin
            $adminEmail = app(\App\Models\SystemSetting::class)::getValue('site.email');
            $kycEmail = app(\App\Models\SystemSetting::class)::getValue('kyc.email');
            
            $emails = array_values(array_filter(array_unique([$adminEmail, $kycEmail])));
            
            if (!empty($emails)) {
                \Illuminate\Support\Facades\Mail::to($emails)->send(new \App\Mail\NewKycSubmission($this->kyc));
            }

            // Delete temp files
            foreach ($types as $tempPath) {
                $storageService->tempDisk()->delete($tempPath);
            }

            Log::info('KYC documents processed successfully', [
                'kyc_id' => $this->kyc->id,
                'user_id' => $this->kyc->user_id,
            ]);

        } catch (\Exception $e) {
            Log::error('KYC processing failed', [
                'kyc_id' => $this->kyc->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Read a temp file with small retries to handle transient storage latency.
     */
    private function readTempFileWithRetry(
        KycStorageService $storageService,
        string $tempPath,
        string $type
    ): string {
        $attempts = 3;
        $lastError = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                if (!$storageService->tempDisk()->exists($tempPath)) {
                    $lastError = "Temp file path missing on disk: {$tempPath}";
                } else {
                    $content = $storageService->tempDisk()->get($tempPath);
                    if (is_string($content) && $content !== '') {
                        return $content;
                    }

                    $lastError = "Temp file unreadable/empty at path: {$tempPath}";
                }
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }

            if ($attempt < $attempts) {
                usleep(300000); // 300ms
            }
        }

        throw new \Exception("Temp file not found: {$type}. {$lastError}");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('KYC job failed permanently', [
            'kyc_id' => $this->kyc->id,
            'exception' => $exception->getMessage(),
        ]);

        $this->kyc->update([
            'status' => KycStatusEnum::REJECTED,
            'rejection_reason' => 'Gagal memproses dokumen. Silakan coba lagi.',
        ]);

        // Clean up temp files
        $storageService = app(KycStorageService::class);
        $storageService->tempDisk()->delete($this->tempSelfiePath);
        $storageService->tempDisk()->delete($this->tempIdCardPath);
        $storageService->tempDisk()->delete($this->tempLeftSidePath);
        $storageService->tempDisk()->delete($this->tempRightSidePath);
    }
}
