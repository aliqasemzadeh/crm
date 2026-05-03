<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('app:update-currency-rate')->hourly()->runInBackground();
Schedule::command('app:price-text-message-notification-command')->hourly()->runInBackground();
Schedule::command('app:sepidar:alert-on-new-invoice-command')->everyFiveMinutes()->runInBackground();
Schedule::command('app:voip:import-phones-from-sepidar')->dailyAt('00:00')->runInBackground();
