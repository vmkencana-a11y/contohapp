<?php

namespace App\Providers;

use App\Services\SecretRuntimeConfig;
use Illuminate\Support\ServiceProvider;

class SecretServiceProvider extends ServiceProvider
{
    public function boot(SecretRuntimeConfig $runtimeConfig): void
    {
        $runtimeConfig->bootstrap();
    }
}
