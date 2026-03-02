<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class EncryptAdminData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'admin:encrypt-data {--dry-run : Show what would be encrypted without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Encrypt existing admin name and email fields and generate email hashes';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting admin data encryption...');
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made.');
        }

        // Get all admins with raw query to bypass model accessors
        $admins = DB::table('admins')->get();
        
        $encryptedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        foreach ($admins as $admin) {
            $this->line("Processing admin ID: {$admin->id} - {$admin->email}");

            // Check if name is already encrypted
            $nameNeedsEncryption = $this->needsEncryption($admin->name);
            $emailNeedsEncryption = $this->needsEncryption($admin->email);

            if (!$nameNeedsEncryption && !$emailNeedsEncryption && $admin->email_hash) {
                $this->line("  → Already encrypted, skipping.");
                $skippedCount++;
                continue;
            }

            try {
                $updateData = [];

                // Get the raw values (if encrypted, we skip; if not, we encrypt)
                if ($nameNeedsEncryption) {
                    $rawName = $admin->name;
                    $updateData['name'] = Crypt::encryptString($rawName);
                    $this->line("  → Name will be encrypted");
                }

                if ($emailNeedsEncryption) {
                    $rawEmail = $admin->email;
                    $updateData['email'] = Crypt::encryptString($rawEmail);
                    $updateData['email_hash'] = hash('sha256', strtolower($rawEmail));
                    $this->line("  → Email will be encrypted, hash generated");
                } elseif (!$admin->email_hash) {
                    // Email is encrypted but hash is missing - need to decrypt first
                    try {
                        $decryptedEmail = Crypt::decryptString($admin->email);
                        $updateData['email_hash'] = hash('sha256', strtolower($decryptedEmail));
                        $this->line("  → Email hash generated from existing encrypted email");
                    } catch (\Exception $e) {
                        $this->error("  → Could not decrypt email to generate hash");
                    }
                }

                if (!empty($updateData) && !$isDryRun) {
                    $updateData['updated_at'] = now();
                    DB::table('admins')
                        ->where('id', $admin->id)
                        ->update($updateData);
                }

                if (!empty($updateData)) {
                    $encryptedCount++;
                    $this->info("  ✓ Processed successfully");
                }
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("  ✗ Error: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("=== Summary ===");
        $this->info("Processed: {$encryptedCount}");
        $this->info("Skipped (already encrypted): {$skippedCount}");
        
        if ($errorCount > 0) {
            $this->error("Errors: {$errorCount}");
        }

        if ($isDryRun) {
            $this->warn("This was a DRY RUN. Run without --dry-run to apply changes.");
        }

        return Command::SUCCESS;
    }

    /**
     * Check if a value needs encryption.
     * Encrypted values from Laravel's Crypt are base64-encoded JSON.
     */
    private function needsEncryption(?string $value): bool
    {
        if (empty($value)) {
            return false;
        }

        // Try to decrypt - if it fails, the value is not encrypted
        try {
            Crypt::decryptString($value);
            return false; // Successfully decrypted, so already encrypted
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return true; // Could not decrypt, so it's plaintext
        }
    }
}
