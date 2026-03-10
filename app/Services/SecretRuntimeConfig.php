<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SecretRuntimeConfig
{
    private static bool $bootstrapped = false;

    public function __construct(private SecretManager $secrets)
    {
    }

    public function bootstrap(): void
    {
        if (self::$bootstrapped || !$this->canReadSecrets()) {
            return;
        }

        try {
            $this->applyMailConfig();
            $this->applyGoogleConfig();
            $this->applyS3KycConfig();
            self::$bootstrapped = true;
        } catch (Throwable $e) {
            Log::warning('Failed to bootstrap runtime secrets configuration.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function reload(): void
    {
        self::$bootstrapped = false;
        $this->secrets->forgetAllConfiguredServices();
        $this->bootstrap();
    }

    private function canReadSecrets(): bool
    {
        try {
            return Schema::hasTable('secrets');
        } catch (Throwable) {
            return false;
        }
    }

    private function applyMailConfig(): void
    {
        $mail = $this->secrets->all('mail');

        if ($mail === []) {
            return;
        }

        config([
            'mail.default' => $mail['mailer'] ?? config('mail.default'),
            'mail.mailers.smtp.scheme' => $mail['scheme'] ?? config('mail.mailers.smtp.scheme'),
            'mail.mailers.smtp.host' => $mail['host'] ?? config('mail.mailers.smtp.host'),
            'mail.mailers.smtp.port' => $mail['port'] ?? config('mail.mailers.smtp.port'),
            'mail.mailers.smtp.username' => $mail['username'] ?? config('mail.mailers.smtp.username'),
            'mail.mailers.smtp.password' => $mail['password'] ?? config('mail.mailers.smtp.password'),
            'mail.from.address' => $mail['from_address'] ?? config('mail.from.address'),
            'mail.from.name' => $mail['from_name'] ?? config('mail.from.name'),
        ]);

        if (app()->bound('mail.manager')) {
            app('mail.manager')->purge();
        }
    }

    private function applyGoogleConfig(): void
    {
        $google = $this->secrets->all('google');

        if ($google === []) {
            return;
        }

        config([
            'services.google.client_id' => $google['client_id'] ?? config('services.google.client_id'),
            'services.google.client_secret' => $google['client_secret'] ?? config('services.google.client_secret'),
            'services.google.redirect_uri' => $google['redirect_uri'] ?? config('services.google.redirect_uri'),
        ]);
    }

    private function applyS3KycConfig(): void
    {
        $s3 = $this->secrets->all('s3_kyc');

        if ($s3 === []) {
            return;
        }

        config([
            'filesystems.disks.s3_kyc.endpoint' => $s3['endpoint'] ?? config('filesystems.disks.s3_kyc.endpoint'),
            'filesystems.disks.s3_kyc.bucket' => $s3['bucket'] ?? config('filesystems.disks.s3_kyc.bucket'),
            'filesystems.disks.s3_kyc.region' => $s3['region'] ?? config('filesystems.disks.s3_kyc.region'),
            'filesystems.disks.s3_kyc.key' => $s3['key'] ?? config('filesystems.disks.s3_kyc.key'),
            'filesystems.disks.s3_kyc.secret' => $s3['secret'] ?? config('filesystems.disks.s3_kyc.secret'),
            'filesystems.disks.s3_kyc.use_path_style_endpoint' => $s3['path_style'] ?? config('filesystems.disks.s3_kyc.use_path_style_endpoint'),
        ]);

        if (app()->bound('filesystem')) {
            app('filesystem')->forgetDisk('s3_kyc');
            app('filesystem')->purge('s3_kyc');
        }
    }
}
