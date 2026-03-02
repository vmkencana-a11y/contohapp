<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Google OAuth 2.0 Service.
 * 
 * Implements Authorization Code Flow with PKCE for secure authentication.
 * All token verification is done server-side — never trust client data.
 */
class GoogleOAuthService
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const CERTS_URL = 'https://www.googleapis.com/oauth2/v3/certs';
    
    /**
     * Minimum scope — only what we need.
     */
    private const SCOPES = 'openid email profile';

    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    public function __construct()
    {
        $this->clientId = config('services.google.client_id', '');
        $this->clientSecret = config('services.google.client_secret', '');
        $this->redirectUri = config('services.google.redirect_uri', '');
    }

    /**
     * Check if Google OAuth is enabled via admin settings.
     */
    public function isEnabled(): bool
    {
        return (bool) SystemSetting::getValue('google.oauth_enabled', false)
            && !empty($this->clientId)
            && !empty($this->clientSecret);
    }

    /**
     * Generate the Google authorization URL with PKCE and state.
     * 
     * @return array{url: string, state: string, code_verifier: string}
     */
    public function getAuthUrl(): array
    {
        // PKCE: Generate code_verifier and code_challenge
        $codeVerifier = $this->generateCodeVerifier();
        $codeChallenge = $this->generateCodeChallenge($codeVerifier);

        // Anti-CSRF: Generate random state
        $state = Str::random(40);

        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => self::SCOPES,
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
            'access_type' => 'online', // We don't need refresh tokens
            'prompt' => 'select_account', // Always show account picker
        ];

        return [
            'url' => self::AUTH_URL . '?' . http_build_query($params),
            'state' => $state,
            'code_verifier' => $codeVerifier,
        ];
    }

    /**
     * Exchange authorization code for tokens and verify the id_token.
     * 
     * @return array{sub: string, email: string, name: string, picture: string|null}
     * @throws \RuntimeException On verification failure
     */
    public function handleCallback(string $code, string $codeVerifier): array
    {
        // Exchange code for tokens
        $response = Http::timeout(10)->asForm()->post(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code',
            'code_verifier' => $codeVerifier,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Gagal menukar authorization code.');
        }

        $tokens = $response->json();
        
        if (empty($tokens['id_token'])) {
            throw new \RuntimeException('Response tidak berisi id_token.');
        }

        // Verify and decode the id_token
        return $this->verifyIdToken($tokens['id_token']);
    }

    /**
     * Verify id_token strictly.
     * 
     * Checks:
     * - iss: must be accounts.google.com or https://accounts.google.com
     * - aud: must match our client ID
     * - exp: must not be expired
     * - email_verified: must be true
     * - sub: Google's unique user identifier
     * 
     * @return array{sub: string, email: string, name: string, picture: string|null}
     * @throws \RuntimeException On any validation failure
     */
    public function verifyIdToken(string $idToken): array
    {
        // Decode JWT parts (header.payload.signature)
        $parts = explode('.', $idToken);
        if (count($parts) !== 3) {
            throw new \RuntimeException('Format id_token tidak valid.');
        }

        // Decode payload
        $payload = json_decode($this->base64UrlDecode($parts[1]), true);
        if (!$payload) {
            throw new \RuntimeException('Payload id_token tidak dapat di-decode.');
        }

        // Verify issuer
        $validIssuers = ['accounts.google.com', 'https://accounts.google.com'];
        if (!isset($payload['iss']) || !in_array($payload['iss'], $validIssuers)) {
            throw new \RuntimeException('Issuer id_token tidak valid.');
        }

        // Verify audience matches our client ID
        if (!isset($payload['aud']) || $payload['aud'] !== $this->clientId) {
            throw new \RuntimeException('Audience id_token tidak sesuai.');
        }

        // Verify not expired
        if (!isset($payload['exp']) || $payload['exp'] < time()) {
            throw new \RuntimeException('Token sudah kedaluwarsa.');
        }

        // Verify email is verified by Google
        if (empty($payload['email_verified'])) {
            throw new \RuntimeException('Email belum diverifikasi oleh Google.');
        }

        // Verify sub exists
        if (empty($payload['sub'])) {
            throw new \RuntimeException('sub identifier tidak ditemukan.');
        }

        // Verify iat (issued at) — token must be fresh (within 5 minutes)
        if (isset($payload['iat']) && $payload['iat'] < (time() - 300)) {
            throw new \RuntimeException('Token terlalu lama (iat check).');
        }

        // Additionally verify signature using Google's public keys
        $this->verifySignature($idToken, $parts);

        return [
            'sub' => $payload['sub'],
            'email' => $payload['email'] ?? '',
            'name' => $payload['name'] ?? '',
            'picture' => $payload['picture'] ?? null,
        ];
    }

    /**
     * Verify JWT signature against Google's public keys.
     */
    private function verifySignature(string $idToken, array $parts): void
    {
        // Decode header to get key ID
        $header = json_decode($this->base64UrlDecode($parts[0]), true);
        if (!$header || empty($header['kid'])) {
            throw new \RuntimeException('JWT header tidak valid.');
        }

        // Fetch Google's public keys (cached by HTTP client)
        $certsResponse = Http::timeout(10)->get(self::CERTS_URL);
        if (!$certsResponse->successful()) {
            throw new \RuntimeException('Gagal mengambil public keys Google.');
        }

        $certs = $certsResponse->json();
        $keys = $certs['keys'] ?? [];

        // Find the matching key
        $matchingKey = null;
        foreach ($keys as $key) {
            if (($key['kid'] ?? '') === $header['kid']) {
                $matchingKey = $key;
                break;
            }
        }

        if (!$matchingKey) {
            throw new \RuntimeException('Signing key tidak ditemukan.');
        }

        // Build public key from JWK
        $publicKey = $this->jwkToPublicKey($matchingKey);

        // Verify signature
        $signatureInput = $parts[0] . '.' . $parts[1];
        $signature = $this->base64UrlDecode($parts[2]);

        $alg = match ($header['alg'] ?? 'RS256') {
            'RS256' => OPENSSL_ALGO_SHA256,
            'RS384' => OPENSSL_ALGO_SHA384,
            'RS512' => OPENSSL_ALGO_SHA512,
            default => throw new \RuntimeException('Algoritma tidak didukung: ' . ($header['alg'] ?? 'unknown')),
        };

        $result = openssl_verify($signatureInput, $signature, $publicKey, $alg);
        
        if ($result !== 1) {
            throw new \RuntimeException('Signature id_token tidak valid.');
        }
    }

    /**
     * Convert JWK to OpenSSL public key.
     */
    private function jwkToPublicKey(array $jwk): \OpenSSLAsymmetricKey
    {
        $n = $this->base64UrlDecode($jwk['n']);
        $e = $this->base64UrlDecode($jwk['e']);

        // Build DER-encoded RSA public key
        $modulus = "\x00" . $n;
        $exponent = $e;

        $modulusLen = strlen($modulus);
        $exponentLen = strlen($exponent);

        // ASN.1 integer encoding
        $modulusEncoded = "\x02" . $this->asn1Length($modulusLen) . $modulus;
        $exponentEncoded = "\x02" . $this->asn1Length($exponentLen) . $exponent;

        $sequence = $modulusEncoded . $exponentEncoded;
        $sequenceEncoded = "\x30" . $this->asn1Length(strlen($sequence)) . $sequence;

        // Bit string wrapper
        $bitString = "\x00" . $sequenceEncoded;
        $bitStringEncoded = "\x03" . $this->asn1Length(strlen($bitString)) . $bitString;

        // Algorithm identifier (RSA)
        $algorithmOid = "\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00";

        $publicKeyInfo = $algorithmOid . $bitStringEncoded;
        $publicKeyInfoEncoded = "\x30" . $this->asn1Length(strlen($publicKeyInfo)) . $publicKeyInfo;

        $pem = "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode($publicKeyInfoEncoded), 64, "\n")
            . "-----END PUBLIC KEY-----";

        $key = openssl_pkey_get_public($pem);
        if (!$key) {
            throw new \RuntimeException('Gagal membangun public key dari JWK.');
        }

        return $key;
    }

    /**
     * ASN.1 DER length encoding.
     */
    private function asn1Length(int $length): string
    {
        if ($length < 0x80) {
            return chr($length);
        }

        $bytes = '';
        $temp = $length;
        while ($temp > 0) {
            $bytes = chr($temp & 0xff) . $bytes;
            $temp >>= 8;
        }

        return chr(0x80 | strlen($bytes)) . $bytes;
    }

    /**
     * Generate PKCE code_verifier (128 random bytes, base64url encoded).
     */
    private function generateCodeVerifier(): string
    {
        return $this->base64UrlEncode(random_bytes(64));
    }

    /**
     * Generate PKCE code_challenge from verifier (SHA-256, base64url encoded).
     */
    private function generateCodeChallenge(string $codeVerifier): string
    {
        return $this->base64UrlEncode(hash('sha256', $codeVerifier, true));
    }

    /**
     * Base64URL encode (RFC 7636).
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64URL decode.
     */
    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
