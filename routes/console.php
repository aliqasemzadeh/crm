<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Schedule::command('app:update-currency-rate')->hourly()->runInBackground()->onSuccess(fn () => Log::info('Scheduled app:update-currency-rate executed successfully.'));
Schedule::command('app:price-text-message-notification-command')->hourly()->runInBackground()->onSuccess(fn () => Log::info('Scheduled app:price-text-message-notification-command executed successfully.'));
Schedule::command('app:sepidar:alert-on-new-invoice-command')->everyMinute()->withoutOverlapping()->runInBackground()->onSuccess(fn () => Log::info('Scheduled app:sepidar:alert-on-new-invoice-command executed successfully.'));
Schedule::command('app:voip:import-phones-from-sepidar')->dailyAt('00:00')->runInBackground()->onSuccess(fn () => Log::info('Scheduled app:voip:import-phones-from-sepidar executed successfully.'));
Schedule::command('app:workspace:generate-recurring-tasks')->dailyAt('08:00')->runInBackground()->onSuccess(fn () => Log::info('Scheduled app:workspace:generate-recurring-tasks executed successfully.'));
Schedule::command('app:day-check-creation-command')->dailyAt('08:00')->runInBackground()->onSuccess(fn () => Log::info('Scheduled app:day-check-creation-command executed successfully.'));
Schedule::command('app:follow-up-command')->dailyAt('08:00')->runInBackground()->onSuccess(fn () => Log::info('Scheduled app:follow-up-command executed successfully.'))->days([0, 1, 2, 3, 4, 6]);
Schedule::command('app:cash-back-discount-code-command')->hourly()->runInBackground()->onSuccess(fn () => Log::info('Scheduled app:cash-back-discount-code-command executed successfully.'));
Schedule::command('app:setaregan-co:send-paid-order-bale-notification')->everyTwoMinutes()->runInBackground()->onSuccess(fn () => Log::info('Scheduled app:setaregan-co:send-paid-order-bale-notification executed successfully.'));
Schedule::command('app:quick-pricing-in-stock-cluster-command')->hourly()->runInBackground()->onSuccess(fn () => Log::info('Scheduled app:quick-pricing-in-stock-cluster-command executed successfully.'));
