<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('audit:prune')->dailyAt('03:15');
Schedule::command('audit:retry-export --limit=1000')->everyFiveMinutes();
Schedule::command('audit:health')->hourly();
Schedule::command('cars:sync-operational-status')->everyMinute();
