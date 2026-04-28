<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Reset every user's monthly credits on the 1st of each month at midnight UTC.
Schedule::command('credits:reset-monthly')->monthlyOn(1, '00:00');
