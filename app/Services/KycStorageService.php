<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * KYC Storage Service.
 *
 * Central resolver for KYC file storage disk.
 * Reads driver preference from SystemSetting (local or s3).
 * S3 credentials are configured in .env, NOT in the database.
 */
class KycStorageService
{
    /**
     * Get the active storage disk for KYC final files (.enc).
     *
     * Returns 'private' disk (local) or 's3_kyc' disk based on system setting.
     */
    public function disk(): Filesystem
    {
        return Storage::disk($this->getDiskName());
    }

    /**
     * Get active disk name for final encrypted KYC files.
     */
    public function getDiskName(): string
    {
        $driver = SystemSetting::getValue('kyc_storage.driver', 'local');

        if ($driver === 's3') {
            return 's3_kyc';
        }

        return 'private';
    }

    /**
     * Get the temporary storage disk.
     *
     * Temp files are always stored on local private disk.
     */
    public function tempDisk(): Filesystem
    {
        return Storage::disk($this->getTempDiskName());
    }

    /**
     * Get temp disk name for raw capture files.
     */
    public function getTempDiskName(): string
    {
        return 'private';
    }

    /**
     * Get the current active driver name.
     */
    public function getDriver(): string
    {
        return SystemSetting::getValue('kyc_storage.driver', 'local');
    }

    /**
     * Get information about the current storage configuration.
     *
     * @return array{driver: string, s3_configured: bool, s3_endpoint: string|null, s3_bucket: string|null}
     */
    public function getDriverInfo(): array
    {
        return [
            'driver' => $this->getDriver(),
            's3_configured' => $this->isS3Configured(),
            's3_endpoint' => config('filesystems.disks.s3_kyc.endpoint'),
            's3_bucket' => config('filesystems.disks.s3_kyc.bucket'),
            's3_region' => config('filesystems.disks.s3_kyc.region'),
        ];
    }

    /**
     * Check if S3 credentials are configured in .env.
     */
    public function isS3Configured(): bool
    {
        return !empty(config('filesystems.disks.s3_kyc.key'))
            && !empty(config('filesystems.disks.s3_kyc.secret'))
            && !empty(config('filesystems.disks.s3_kyc.bucket'))
            && !empty(config('filesystems.disks.s3_kyc.endpoint'));
    }

    /**
     * Test S3 connection by writing, reading, and deleting a test file.
     *
     * @return array{success: bool, message: string, latency_ms: int|null}
     */
    public function testConnection(): array
    {
        if (!$this->isS3Configured()) {
            return [
                'success' => false,
                'message' => 'Konfigurasi S3 belum lengkap. Pastikan S3_KYC_ENDPOINT, S3_KYC_BUCKET, S3_KYC_KEY, dan S3_KYC_SECRET sudah diisi di file .env.',
                'latency_ms' => null,
            ];
        }

        $testFile = '.connection-test-' . uniqid();
        $testContent = 'sekuota-kyc-connection-test-' . now()->toIso8601String();

        try {
            $start = microtime(true);

            // Write test file
            Storage::disk('s3_kyc')->put($testFile, $testContent);

            // Read test file
            $readContent = Storage::disk('s3_kyc')->get($testFile);

            if ($readContent !== $testContent) {
                throw new \RuntimeException('Read content does not match written content.');
            }

            // Delete test file
            Storage::disk('s3_kyc')->delete($testFile);

            $latency = (int) round((microtime(true) - $start) * 1000);

            Log::info('KYC S3 connection test succeeded', [
                'endpoint' => config('filesystems.disks.s3_kyc.endpoint'),
                'bucket' => config('filesystems.disks.s3_kyc.bucket'),
                'latency_ms' => $latency,
            ]);

            return [
                'success' => true,
                'message' => "Koneksi S3 berhasil. Latency: {$latency}ms.",
                'latency_ms' => $latency,
            ];

        } catch (\Exception $e) {
            // Clean up test file if it was created
            try {
                Storage::disk('s3_kyc')->delete($testFile);
            } catch (\Exception) {
                // Ignore cleanup errors
            }

            Log::warning('KYC S3 connection test failed', [
                'endpoint' => config('filesystems.disks.s3_kyc.endpoint'),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Koneksi S3 gagal: ' . $e->getMessage(),
                'latency_ms' => null,
            ];
        }
    }
}
