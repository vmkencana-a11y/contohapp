<?php

use App\Http\Middleware\AuthenticateAdmin;
use App\Http\Middleware\AuthenticateUser;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\ThrottleAuth;
use App\Http\Middleware\VerifyUserStatus;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands()
    ->withMiddleware(function (Middleware $middleware): void {
        // Global middleware - apply to all routes
        $middleware->append(SecurityHeaders::class);
        $middleware->append(\App\Http\Middleware\CheckMaintenanceMode::class);

        // Trust proxies - configure in .env: TRUSTED_PROXIES=192.168.1.1,10.0.0.0/8
        // Use '*' only if behind a KNOWN trusted load balancer (e.g., AWS ALB, GCP LB)
        $trustedProxies = trim((string) env('TRUSTED_PROXIES', '127.0.0.1'));
        if ($trustedProxies === '') {
            $trustedProxies = '127.0.0.1';
        }
        $middleware->trustProxies(
            at: $trustedProxies === '*'
                ? '*'
                : array_map('trim', explode(',', $trustedProxies))
        );
        
        // Register middleware aliases
        $middleware->alias([
            'auth.user' => AuthenticateUser::class,
            'auth.admin' => AuthenticateAdmin::class,
            'throttle.auth' => ThrottleAuth::class,
            'verify.status' => VerifyUserStatus::class, // Missing comma was here, but just fixing block
        ]);

        // Redirect authenticated users
        $middleware->redirectUsersTo(function (Request $request) {
            if ($request->cookie('admin_token')) {
                return route('admin.dashboard');
            }
            return route('dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
