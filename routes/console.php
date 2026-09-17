<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Keeps confirming KHQR payments even after a customer closes the checkout
// tab. The Bakong daily quota guard inside BakongService keeps this (and
// live page polling) from ever exceeding the account's request cap.
Schedule::command('bakong:check-pending --limit=5')->everyFiveMinutes();
