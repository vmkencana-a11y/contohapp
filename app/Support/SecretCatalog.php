<?php

namespace App\Support;

use InvalidArgumentException;

class SecretCatalog
{
    /**
     * Supported secret-backed config entries.
     *
     * @return array<string, array{label: string, keys: array<string, array{label: string, type: string, required?: bool}>}>
     */
    public static function definitions(): array
    {
        return [
            'mail' => [
                'label' => 'SMTP Mailer',
                'keys' => [
                    'mailer' => ['label' => 'Default Mailer', 'type' => 'string', 'required' => true],
                    'scheme' => ['label' => 'SMTP Scheme', 'type' => 'string'],
                    'host' => ['label' => 'SMTP Host', 'type' => 'string', 'required' => true],
                    'port' => ['label' => 'SMTP Port', 'type' => 'integer', 'required' => true],
                    'username' => ['label' => 'SMTP Username', 'type' => 'string'],
                    'password' => ['label' => 'SMTP Password', 'type' => 'string'],
                    'from_address' => ['label' => 'From Address', 'type' => 'string', 'required' => true],
                    'from_name' => ['label' => 'From Name', 'type' => 'string', 'required' => true],
                ],
            ],
            'google' => [
                'label' => 'Google OAuth',
                'keys' => [
                    'client_id' => ['label' => 'Client ID', 'type' => 'string', 'required' => true],
                    'client_secret' => ['label' => 'Client Secret', 'type' => 'string', 'required' => true],
                    'redirect_uri' => ['label' => 'Redirect URI', 'type' => 'string', 'required' => true],
                ],
            ],
            's3_kyc' => [
                'label' => 'KYC S3 Storage',
                'keys' => [
                    'endpoint' => ['label' => 'Endpoint', 'type' => 'string', 'required' => true],
                    'bucket' => ['label' => 'Bucket', 'type' => 'string', 'required' => true],
                    'region' => ['label' => 'Region', 'type' => 'string', 'required' => true],
                    'key' => ['label' => 'Access Key', 'type' => 'string', 'required' => true],
                    'secret' => ['label' => 'Secret Key', 'type' => 'string', 'required' => true],
                    'path_style' => ['label' => 'Use Path Style Endpoint', 'type' => 'boolean', 'required' => true],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array{value: string, service: string, secret_key: string, service_label: string, key_label: string, type: string}>
     */
    public static function flatDefinitions(): array
    {
        $definitions = [];

        foreach (self::definitions() as $service => $serviceDefinition) {
            foreach ($serviceDefinition['keys'] as $secretKey => $keyDefinition) {
                $definitions[] = [
                    'value' => self::composeDefinitionValue($service, $secretKey),
                    'service' => $service,
                    'secret_key' => $secretKey,
                    'service_label' => $serviceDefinition['label'],
                    'key_label' => $keyDefinition['label'],
                    'type' => $keyDefinition['type'],
                ];
            }
        }

        return $definitions;
    }

    public static function definition(string $service, string $secretKey): ?array
    {
        return self::definitions()[$service]['keys'][$secretKey] ?? null;
    }

    public static function serviceLabel(string $service): string
    {
        return self::definitions()[$service]['label'] ?? $service;
    }

    public static function keyLabel(string $service, string $secretKey): string
    {
        return self::definition($service, $secretKey)['label'] ?? $secretKey;
    }

    public static function isSupported(string $service, string $secretKey): bool
    {
        return self::definition($service, $secretKey) !== null;
    }

    public static function type(string $service, string $secretKey): string
    {
        return self::definition($service, $secretKey)['type'] ?? 'string';
    }

    public static function cast(string $service, string $secretKey, ?string $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return match (self::type($service, $secretKey)) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false,
            default => $value,
        };
    }

    public static function normalize(string $service, string $secretKey, mixed $value): string
    {
        return match (self::type($service, $secretKey)) {
            'integer' => (string) ((int) $value),
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOL) ? '1' : '0',
            default => (string) $value,
        };
    }

    /**
     * Validation rules for secret values (controller adds required/nullable).
     *
     * @return array<int, string>
     */
    public static function valueRules(string $service, string $secretKey): array
    {
        return match (self::type($service, $secretKey)) {
            'integer' => ['integer', 'min:0', 'max:65535'],
            'boolean' => ['boolean'],
            default => ['string', 'max:16384'],
        };
    }

    public static function parseDefinitionValue(string $value): array
    {
        $parts = explode('::', $value, 2);

        if (count($parts) !== 2 || !self::isSupported($parts[0], $parts[1])) {
            throw new InvalidArgumentException('Unsupported secret definition.');
        }

        return ['service' => $parts[0], 'secret_key' => $parts[1]];
    }

    public static function composeDefinitionValue(string $service, string $secretKey): string
    {
        return $service . '::' . $secretKey;
    }
}
