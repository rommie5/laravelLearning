<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('contracts:check-expirations')->daily();
Schedule::command('clauses:expire')->daily();
Schedule::command('installments:mark-overdue')->daily();
Schedule::command('notifications:prune')->daily()->at('02:00');
