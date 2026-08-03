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
