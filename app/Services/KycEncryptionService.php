<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

/**
 * KYC Encryption Service
 * 
 * Implements envelope encryption for KYC files:
 * - Each file gets a unique 256-bit file key
 * - File key is encrypted with master key (APP_KEY)
 * - File content is encrypted with file key using AES-256-GCM
 */
class KycEncryptionService
{
    /**
     * Current key version.
     */
    private const KEY_VERSION = 1;

    /**
     * Encryption cipher for file content.
     */
    private const CIPHER = 'aes-256-gcm';

    /**
     * Generate a new random file key.
     */
    public function generateFileKey(): string
    {
        return random_bytes(32); // 256 bits
    }

    /**
     * Encrypt file key with master key.
     */
    public function encryptFileKey(string $fileKey): string
    {
        return Crypt::encryptString(base64_encode($fileKey));
    }

    /**
     * Decrypt file key with master key.
     */
    public function decryptFileKey(string $encryptedFileKey): string
    {
        try {
            return base64_decode(Crypt::decryptString($encryptedFileKey));
        } catch (DecryptException $e) {
            throw new \RuntimeException('Failed to decrypt file key: ' . $e->getMessage());
        }
    }

    /**
     * Encrypt file content with file key using AES-256-GCM.
     */
    public function encryptFile(string $content, string $fileKey): string
    {
        $iv = random_bytes(12); // GCM recommended IV size
        $tag = '';
        
        $encrypted = openssl_encrypt(
            $content,
            self::CIPHER,
            $fileKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16 // Tag length
        );

        if ($encrypted === false) {
            throw new \RuntimeException('File encryption failed: ' . openssl_error_string());
        }

        // Pack: IV (12) + Tag (16) + Encrypted data
        return $iv . $tag . $encrypted;
    }

    /**
     * Decrypt file content with file key using AES-256-GCM.
     */
    public function decryptFile(string $encryptedContent, string $fileKey): string
    {
        if (strlen($encryptedContent) < 28) { // 12 IV + 16 tag
            throw new \RuntimeException('Invalid encrypted content: too short');
        }

        // Unpack: IV (12) + Tag (16) + Encrypted data
        $iv = substr($encryptedContent, 0, 12);
        $tag = substr($encryptedContent, 12, 16);
        $encrypted = substr($encryptedContent, 28);

        $decrypted = openssl_decrypt(
            $encrypted,
            self::CIPHER,
            $fileKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($decrypted === false) {
            throw new \RuntimeException('File decryption failed: ' . openssl_error_string());
        }

        return $decrypted;
    }

    /**
     * Full encryption workflow: generate key, encrypt file, encrypt key.
     * 
     * @return array{content: string, encrypted_key: string, key_version: int}
     */
    public function encryptForStorage(string $fileContent): array
    {
        $fileKey = $this->generateFileKey();
        
        return [
            'content' => $this->encryptFile($fileContent, $fileKey),
            'encrypted_key' => $this->encryptFileKey($fileKey),
            'key_version' => self::KEY_VERSION,
        ];
    }

    /**
     * Full decryption workflow: decrypt key, then decrypt file.
     */
    public function decryptFromStorage(string $encryptedContent, string $encryptedKey): string
    {
        $fileKey = $this->decryptFileKey($encryptedKey);
        return $this->decryptFile($encryptedContent, $fileKey);
    }

    /**
     * Get current key version.
     */
    public function getKeyVersion(): int
    {
        return self::KEY_VERSION;
    }
}
