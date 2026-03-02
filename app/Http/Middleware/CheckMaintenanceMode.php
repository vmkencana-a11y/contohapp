<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\SystemSetting;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (SystemSetting::getValue('general.maintenance_mode', false)) {
            // Allow admin routes to bypass maintenance mode so admins can turn it off
            if (!$request->is('admin') && !$request->is('admin/*')) {
                $endTime = SystemSetting::getValue('general.maintenance_end_time');
                return response()->view('errors.maintenance', ['endTime' => $endTime], 503);
            }
        }

        return $next($request);
    }
}
