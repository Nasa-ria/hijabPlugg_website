<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Scheduled tasks (run `php artisan schedule:run` every minute from cron).
| Laravel 11 reads the schedule from here; app/Console/Kernel.php is not used.
*/
Schedule::command('newsletter:send-scheduled')->everyMinute()->withoutOverlapping();
Schedule::command('orders:release-unpaid')->hourly()->withoutOverlapping();
