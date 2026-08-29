<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Yessal ERP - Subscription Scheduler
|--------------------------------------------------------------------------
*/

// Vérification des abonnements arrivés à expiration
Schedule::command('subscriptions:expire')
    ->hourly();

// Création automatique des paiements de renouvellement
Schedule::command('subscriptions:renew')
    ->dailyAt('00:05');