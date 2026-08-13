<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

/**
 * HIGEST Wallet Automated Daily Reconciliation Scheduler (02:00 AM)
 */
Schedule::command('wallet:verify-ledger --notify')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->onOneServer();

/**
 * HIGEST Wallet Promotions Outbox Worker (Every Minute)
 */
Schedule::command('wallet:promotions:process-outbox --batch=50 --lease=60')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

