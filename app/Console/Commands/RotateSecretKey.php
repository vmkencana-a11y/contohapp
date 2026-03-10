<?php

namespace App\Console\Commands;

use App\Models\Secret;
use App\Services\SecretCrypto;
use App\Services\SecretManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class RotateSecretKey extends Command
{
    protected $signature = 'secrets:rotate-key
        {--old-key= : The old master key (base64:... or hex:...) to decrypt existing secrets}
        {--new-key= : The new master key (base64:... or hex:...) to re-encrypt secrets}
        {--force : Skip confirmation prompt}';

    protected $description = 'Re-encrypt all secrets with a new master key.';

    public function handle(SecretManager $manager): int
    {
        $oldKeyRaw = $this->option('old-key');
        $newKeyRaw = $this->option('new-key');

        if (!$oldKeyRaw || !$newKeyRaw) {
            $this->error('Both --old-key and --new-key are required.');
            $this->line('');
            $this->line('Usage:');
            $this->line('  php artisan secrets:rotate-key --old-key=base64:OLD... --new-key=base64:NEW...');
            return self::FAILURE;
        }

        if ($oldKeyRaw === $newKeyRaw) {
            $this->error('Old key and new key must be different.');
            return self::FAILURE;
        }

        $secrets = Secret::all();

        if ($secrets->isEmpty()) {
            $this->info('No secrets found in the database. Nothing to rotate.');
            return self::SUCCESS;
        }

        $this->info("Found {$secrets->count()} secret(s) to re-encrypt.");

        if (!$this->option('force') && !$this->confirm('Proceed with key rotation?')) {
            $this->info('Aborted.');
            return self::SUCCESS;
        }

        // Build crypto instances for old and new keys.
        config(['secrets.master_key' => $oldKeyRaw]);
        $oldCrypto = new SecretCrypto();

        config(['secrets.master_key' => $newKeyRaw]);
        $newCrypto = new SecretCrypto();

        $rotated = 0;
        $failed = 0;

        DB::transaction(function () use ($secrets, $oldCrypto, $newCrypto, &$rotated, &$failed) {
            foreach ($secrets as $secret) {
                try {
                    // Decrypt with old key.
                    $plaintext = $oldCrypto->decrypt($secret->encrypted_value, $secret->iv);

                    // Re-encrypt with new key.
                    $encrypted = $newCrypto->encrypt($plaintext);

                    $secret->encrypted_value = $encrypted['encrypted_value'];
                    $secret->iv = $encrypted['iv'];
                    $secret->saveQuietly(); // Skip model events to avoid cache churn.

                    $rotated++;
                } catch (Throwable $e) {
                    $failed++;
                    $this->error("  Failed: {$secret->service}::{$secret->secret_key} — {$e->getMessage()}");
                }
            }
        });

        // Flush all caches after rotation.
        $manager->forgetAllConfiguredServices();

        $this->info('');
        $this->info("Rotation complete: {$rotated} rotated, {$failed} failed.");

        if ($failed > 0) {
            $this->warn('Some secrets failed to rotate. Review the errors above and retry.');
            return self::FAILURE;
        }

        $this->info('');
        $this->info('Next steps:');
        $this->line('  1. Update CREDENTIAL_MASTER_KEY in your .env to the new key.');
        $this->line('  2. Restart your application workers / clear config cache.');
        $this->line('  3. Verify with: php artisan secrets:check');

        return self::SUCCESS;
    }
}
