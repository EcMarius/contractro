<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule::command('inspire')->hourly();
Schedule::command('subscriptions:cancel-expired')->hourly();
Schedule::command('evenleads:cleanup-stuck-syncs')->everyFifteenMinutes();
Schedule::command('evenleads:run-automated-syncs')->everyFifteenMinutes();

// Account Warmup - Run hourly to process scheduled warmup activities
Schedule::command('warmup:run')->hourly();

// API Usage Cleanup - Run daily at 2 AM to clean up logs older than 90 days
Schedule::command('api-usage:cleanup')->dailyAt('02:00');

// Contract Management - Send signature reminders daily at 10 AM
Schedule::job(new \App\Jobs\SendSignatureReminders)->dailyAt('10:00');

// Contract Management - Check for expiring contracts daily at 9 AM
Schedule::job(new \App\Jobs\CheckExpiringContracts)->dailyAt('09:00');

// License Management - Check for expiring licenses daily at 9:30 AM
Schedule::command('licenses:check-expiring')->dailyAt('09:30');

// License Management - Cleanup old license check logs weekly (Sundays at 3 AM)
Schedule::command('licenses:cleanup-logs --days=90')->weekly()->sundays()->at('03:00');
