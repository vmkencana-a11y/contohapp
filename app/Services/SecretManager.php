<?php

namespace App\Services;

use App\Models\Secret;
use App\Support\SecretCatalog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class SecretManager
{
    private const CACHE_TTL_SECONDS = 86400; // 24 hours

    public function __construct(private SecretCrypto $crypto)
    {
    }

    public function get(string $service, string $secretKey, mixed $default = null): mixed
    {
        $secrets = $this->all($service);

        return $secrets[$secretKey] ?? $default;
    }

    /**
     * Retrieve a secret value or throw if it is not configured.
     *
     * @throws \RuntimeException
     */
    public function getOrFail(string $service, string $secretKey): mixed
    {
        $secrets = $this->all($service);

        if (!isset($secrets[$secretKey])) {
            throw new RuntimeException(
                "Secret [{$service}::{$secretKey}] is not configured or inactive."
            );
        }

        return $secrets[$secretKey];
    }

    /**
     * @return array<string, mixed>
     */
    public function all(string $service): array
    {
        return Cache::remember(self::cacheKey($service), self::CACHE_TTL_SECONDS, function () use ($service) {
            $rows = Secret::query()
                ->where('service', $service)
                ->where('is_active', true)
                ->get();

            $resolved = [];

            foreach ($rows as $row) {
                if (!SecretCatalog::isSupported($row->service, $row->secret_key)) {
                    continue;
                }

                try {
                    $plain = $this->crypto->decrypt($row->encrypted_value, $row->iv);
                    $resolved[$row->secret_key] = SecretCatalog::cast($row->service, $row->secret_key, $plain);
                } catch (Throwable $e) {
                    Log::error('Failed to decrypt secret row; skipping.', [
                        'secret_id' => $row->id,
                        'service' => $row->service,
                        'secret_key' => $row->secret_key,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return $resolved;
        });
    }

    public function upsert(string $service, string $secretKey, mixed $value, bool $isActive, ?int $updatedBy = null): Secret
    {
        if (!SecretCatalog::isSupported($service, $secretKey)) {
            throw new InvalidArgumentException('Unsupported secret key.');
        }

        $normalized = SecretCatalog::normalize($service, $secretKey, $value);
        $encrypted = $this->crypto->encrypt($normalized);

        // Include soft-deleted rows so we restore instead of violating the unique constraint.
        $secret = Secret::withTrashed()->firstOrNew([
            'service' => $service,
            'secret_key' => $secretKey,
        ]);

        // If the row was soft-deleted, restore it.
        if ($secret->trashed()) {
            $secret->restore();
        }

        $secret->fill([
            'encrypted_value' => $encrypted['encrypted_value'],
            'iv' => $encrypted['iv'],
            'is_active' => $isActive,
            'updated_by' => $updatedBy,
        ]);

        $secret->save();

        return $secret;
    }

    public function updateMetadata(Secret $secret, bool $isActive, ?int $updatedBy = null): Secret
    {
        $secret->fill([
            'is_active' => $isActive,
            'updated_by' => $updatedBy,
        ]);

        $secret->save();

        return $secret;
    }

    public function delete(Secret $secret): void
    {
        $secret->delete();
    }

    public function forgetService(string $service): void
    {
        self::forgetCacheFor($service);
    }

    public function forgetAllConfiguredServices(): void
    {
        foreach (array_keys(SecretCatalog::definitions()) as $service) {
            $this->forgetService($service);
        }
    }

    /**
     * Build the cache key for a given service.
     *
     * Used by both SecretManager and the Secret model boot hook so the
     * cache-key format is defined in exactly one place.
     */
    public static function cacheKey(string $service): string
    {
        return config('secrets.cache_prefix', 'secret') . ':' . $service;
    }

    /**
     * Forget the cached secrets for a service (static, callable from model hooks).
     */
    public static function forgetCacheFor(string $service): void
    {
        Cache::forget(self::cacheKey($service));
    }
}

