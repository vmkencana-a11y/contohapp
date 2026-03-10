<?php

namespace App\Services;

use RuntimeException;

class SecretCrypto
{
    private const CIPHER = 'AES-256-GCM';
    private const LEGACY_CIPHER = 'AES-256-CBC';
    private const VERSION_PREFIX = 'gcm:';

    /**
     * @return array{encrypted_value: string, iv: string}
     */
    public function encrypt(string $value): array
    {
        $key = $this->resolveKey();
        $iv = random_bytes(12);
        $tag = '';

        $ciphertext = openssl_encrypt($value, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);

        if ($ciphertext === false) {
            throw new RuntimeException('Failed to encrypt secret.');
        }

        if ($tag === '') {
            throw new RuntimeException('Failed to encrypt secret (missing auth tag).');
        }

        return [
            'encrypted_value' => self::VERSION_PREFIX . base64_encode($ciphertext) . ':' . base64_encode($tag),
            'iv' => $iv,
        ];
    }

    public function decrypt(string $encryptedValue, string $iv): string
    {
        $key = $this->resolveKey();

        if (str_starts_with($encryptedValue, self::VERSION_PREFIX)) {
            $payload = substr($encryptedValue, strlen(self::VERSION_PREFIX));
            [$ciphertextB64, $tagB64] = array_pad(explode(':', $payload, 2), 2, null);

            if (!is_string($ciphertextB64) || !is_string($tagB64) || $ciphertextB64 === '' || $tagB64 === '') {
                throw new RuntimeException('Invalid encrypted secret payload.');
            }

            $ciphertext = base64_decode($ciphertextB64, true);
            $tag = base64_decode($tagB64, true);

            if ($ciphertext === false || $tag === false) {
                throw new RuntimeException('Invalid encrypted secret payload.');
            }

            $decrypted = openssl_decrypt($ciphertext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);

            if ($decrypted === false) {
                throw new RuntimeException('Failed to decrypt secret.');
            }

            return $decrypted;
        }

        $ciphertext = base64_decode($encryptedValue, true);

        if ($ciphertext === false) {
            throw new RuntimeException('Invalid encrypted secret payload.');
        }

        $decrypted = openssl_decrypt($ciphertext, self::LEGACY_CIPHER, $key, OPENSSL_RAW_DATA, $iv);

        if ($decrypted === false) {
            throw new RuntimeException('Failed to decrypt secret.');
        }

        return $decrypted;
    }

    private function resolveKey(): string
    {
        $configured = (string) config('secrets.master_key', '');

        if ($configured === '') {
            throw new RuntimeException('CREDENTIAL_MASTER_KEY is not configured. Use base64:... or hex:...');
        }

        if (str_starts_with($configured, 'base64:')) {
            $decoded = base64_decode(substr($configured, 7), true);
        } elseif (str_starts_with($configured, 'hex:')) {
            $decoded = hex2bin(substr($configured, 4));
        } else {
            // Allow raw 32-byte keys (not recommended for .env due to encoding issues).
            $decoded = $configured;
        }

        $length = is_string($decoded) ? strlen($decoded) : 0;

        if (!is_string($decoded) || $length !== 32) {
            throw new RuntimeException("CREDENTIAL_MASTER_KEY must decode to exactly 32 bytes (got {$length}).");
        }

        return $decoded;
    }
}
