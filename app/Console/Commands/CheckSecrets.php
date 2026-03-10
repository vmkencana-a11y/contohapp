<?php

namespace App\Console\Commands;

use App\Models\Secret;
use App\Services\SecretCrypto;
use App\Support\SecretCatalog;
use Illuminate\Console\Command;
use Throwable;

class CheckSecrets extends Command
{
    protected $signature = 'secrets:check';

    protected $description = 'Verify that the master key is valid, all required secrets exist, and every secret decrypts successfully.';

    public function handle(SecretCrypto $crypto): int
    {
        $hasErrors = false;

        // ── 1. Master key ────────────────────────────────────────────
        $this->info('Checking master key...');

        $masterKey = (string) config('secrets.master_key', '');

        if ($masterKey === '') {
            $this->error('  ✗ CREDENTIAL_MASTER_KEY is not set.');
            return self::FAILURE;
        }

        try {
            // Encrypt + decrypt a probe value to verify the key works end-to-end.
            $probe = $crypto->encrypt('__probe__');
            $decrypted = $crypto->decrypt($probe['encrypted_value'], $probe['iv']);

            if ($decrypted !== '__probe__') {
                throw new \RuntimeException('Round-trip mismatch.');
            }

            $this->line('  ✓ Master key is valid (32-byte, round-trip OK).');
        } catch (Throwable $e) {
            $this->error("  ✗ Master key validation failed: {$e->getMessage()}");
            return self::FAILURE;
        }

        // ── 2. Required secrets ──────────────────────────────────────
        $this->info('');
        $this->info('Checking required secrets...');

        $definitions = SecretCatalog::definitions();
        $storedSecrets = Secret::all()->keyBy(fn (Secret $s) => "{$s->service}::{$s->secret_key}");

        foreach ($definitions as $service => $definition) {
            foreach ($definition['keys'] as $key => $meta) {
                $compositeKey = "{$service}::{$key}";
                $required = $meta['required'] ?? false;

                if (!$storedSecrets->has($compositeKey)) {
                    if ($required) {
                        $this->error("  ✗ MISSING (required): {$compositeKey} [{$meta['label']}]");
                        $hasErrors = true;
                    } else {
                        $this->warn("  ○ Not configured (optional): {$compositeKey}");
                    }
                    continue;
                }

                $secret = $storedSecrets->get($compositeKey);

                if (!$secret->is_active) {
                    $this->warn("  ○ Inactive: {$compositeKey}");
                    continue;
                }

                $this->line("  ✓ {$compositeKey}");
            }
        }

        // ── 3. Decrypt all stored secrets ────────────────────────────
        $this->info('');
        $this->info('Verifying decryption of all stored secrets...');

        $allSecrets = Secret::all();
        $decryptOk = 0;
        $decryptFail = 0;

        foreach ($allSecrets as $secret) {
            try {
                $crypto->decrypt($secret->encrypted_value, $secret->iv);
                $decryptOk++;
            } catch (Throwable $e) {
                $decryptFail++;
                $hasErrors = true;
                $this->error("  ✗ Decrypt failed: {$secret->service}::{$secret->secret_key} — {$e->getMessage()}");
            }
        }

        $this->line("  {$decryptOk} OK, {$decryptFail} failed out of {$allSecrets->count()} total.");

        // ── Summary ──────────────────────────────────────────────────
        $this->info('');

        if ($hasErrors) {
            $this->error('Health check completed with errors. Review the issues above.');
            return self::FAILURE;
        }

        $this->info('All checks passed. ✓');
        return self::SUCCESS;
    }
}
