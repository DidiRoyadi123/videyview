<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Process;

Artisan::command('inspire', function () {
    $this->comment(Illuminate\Foundation\Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Robot Mandor Otonom v3.0
Schedule::command('video:auto-heal')->hourly();
Schedule::command('database:backup')->daily();
