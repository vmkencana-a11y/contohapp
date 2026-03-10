<?php

namespace App\Console\Commands;

use App\Services\SecretManager;
use Illuminate\Console\Command;

class ImportSecretsFromEnv extends Command
{
    protected $signature = 'secrets:import-env {--force : Overwrite secrets that already exist}';

    protected $description = 'Import active service credentials from current env/config into the secrets table.';

    public function handle(SecretManager $secretManager): int
    {
        $records = [
            ['service' => 'mail', 'secret_key' => 'mailer', 'value' => config('mail.default')],
            ['service' => 'mail', 'secret_key' => 'scheme', 'value' => config('mail.mailers.smtp.scheme')],
            ['service' => 'mail', 'secret_key' => 'host', 'value' => config('mail.mailers.smtp.host')],
            ['service' => 'mail', 'secret_key' => 'port', 'value' => config('mail.mailers.smtp.port')],
            ['service' => 'mail', 'secret_key' => 'username', 'value' => config('mail.mailers.smtp.username')],
            ['service' => 'mail', 'secret_key' => 'password', 'value' => config('mail.mailers.smtp.password')],
            ['service' => 'mail', 'secret_key' => 'from_address', 'value' => config('mail.from.address')],
            ['service' => 'mail', 'secret_key' => 'from_name', 'value' => config('mail.from.name')],
            ['service' => 'google', 'secret_key' => 'client_id', 'value' => config('services.google.client_id')],
            ['service' => 'google', 'secret_key' => 'client_secret', 'value' => config('services.google.client_secret')],
            ['service' => 'google', 'secret_key' => 'redirect_uri', 'value' => config('services.google.redirect_uri')],
            ['service' => 's3_kyc', 'secret_key' => 'endpoint', 'value' => config('filesystems.disks.s3_kyc.endpoint')],
            ['service' => 's3_kyc', 'secret_key' => 'bucket', 'value' => config('filesystems.disks.s3_kyc.bucket')],
            ['service' => 's3_kyc', 'secret_key' => 'region', 'value' => config('filesystems.disks.s3_kyc.region')],
            ['service' => 's3_kyc', 'secret_key' => 'key', 'value' => config('filesystems.disks.s3_kyc.key')],
            ['service' => 's3_kyc', 'secret_key' => 'secret', 'value' => config('filesystems.disks.s3_kyc.secret')],
            ['service' => 's3_kyc', 'secret_key' => 'path_style', 'value' => config('filesystems.disks.s3_kyc.use_path_style_endpoint')],
        ];

        $imported = 0;
        $skipped = 0;

        foreach ($records as $record) {
            $existing = \App\Models\Secret::query()
                ->where('service', $record['service'])
                ->where('secret_key', $record['secret_key'])
                ->first();

            if ($existing && !$this->option('force')) {
                $skipped++;
                continue;
            }

            if ($record['value'] === null || $record['value'] === '') {
                $skipped++;
                continue;
            }

            $secretManager->upsert($record['service'], $record['secret_key'], $record['value'], true);
            $imported++;
        }

        $this->info("Imported {$imported} secret(s). Skipped {$skipped}.");

        return self::SUCCESS;
    }
}
