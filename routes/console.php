<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduled Backups
$scriptPath = base_path('../scripts/backup.ps1');

/*
Schedule::exec("powershell.exe -ExecutionPolicy Bypass -File \"$scriptPath\" daily")
    ->daily()
    ->at('01:00')
    ->description('Daily Database & Code Backup');

Schedule::exec("powershell.exe -ExecutionPolicy Bypass -File \"$scriptPath\" weekly")
    ->weekly()
    ->on(1) // Monday
    ->at('02:00')
    ->description('Weekly Database & Code Backup');

Schedule::exec("powershell.exe -ExecutionPolicy Bypass -File \"$scriptPath\" monthly")
    ->monthly()
    ->on(1) // 1st of month
    ->at('03:00')
    ->description('Monthly Database & Code Backup');
*/
