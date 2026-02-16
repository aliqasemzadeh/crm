<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('app:update-currency-rate')->hourly();
Schedule::command('app:sepidar:alert-on-new-invoice-command')->everyFiveMinutes();

