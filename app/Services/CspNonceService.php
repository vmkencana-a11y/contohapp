<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * CSP Nonce Service
 * 
 * Generates cryptographically secure nonces for Content Security Policy.
 * Used to allow specific inline scripts/styles while blocking others.
 */
class CspNonceService
{
    private ?string $nonce = null;

    /**
     * Get or generate the nonce for this request.
     */
    public function getNonce(): string
    {
        if ($this->nonce === null) {
            // Generate a cryptographically secure random nonce
            $this->nonce = base64_encode(random_bytes(16));
        }

        return $this->nonce;
    }

    /**
     * Get the nonce attribute for inline scripts/styles.
     * 
     * Example usage in Blade: <script {!! csp_nonce() !!}>
     */
    public function getNonceAttribute(): string
    {
        return 'nonce="' . $this->getNonce() . '"';
    }
}
