<?php

namespace App\Providers;

use App\Services\CspNonceService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register CSP Nonce Service as singleton (same nonce per request)
        $this->app->singleton(CspNonceService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS when behind a proxy (ngrok, cloudflare, etc.)
        if (request()->header('X-Forwarded-Proto') === 'https' || 
            str_starts_with(config('app.url'), 'https')) {
            URL::forceScheme('https');
        }

        // Register csp_nonce() helper function
        Blade::directive('cspNonce', function () {
            return '<?php echo app(\App\Services\CspNonceService::class)->getNonceAttribute(); ?>';
        });
    }
}
