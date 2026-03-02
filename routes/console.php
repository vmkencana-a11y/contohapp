<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Cleanup expired OTPs and sessions every hour
Schedule::command('sekuota:cleanup')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

// Prune old logs weekly
Schedule::command('model:prune')
    ->weekly()
    ->withoutOverlapping();
