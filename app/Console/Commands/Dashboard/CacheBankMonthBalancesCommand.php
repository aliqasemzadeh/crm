<?php

namespace App\Console\Commands\Dashboard;

use App\Models\Sepidar\FMK\FiscalYear;
use App\Services\Dashboard\BankAccountMonthBalanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CacheBankMonthBalancesCommand extends Command
{
    protected $signature = 'app:dashboard:cache-bank-month-balances {--fiscal-year=}';

    protected $description = 'Cache end-of-month and minimum bank account balances for administrator dashboard charts';

    public function handle(BankAccountMonthBalanceService $service): int
    {
        Log::info('Command app:dashboard:cache-bank-month-balances started.');

        $fiscalYearOption = $this->option('fiscal-year');

        $fiscalYears = $fiscalYearOption
            ? FiscalYear::query()->where('FiscalYearId', $fiscalYearOption)->get()
            : FiscalYear::query()->orderByDesc('FiscalYearId')->limit(2)->get();

        if ($fiscalYears->isEmpty()) {
            $this->warn('No fiscal years found.');

            return self::FAILURE;
        }

        foreach ($fiscalYears as $fiscalYear) {
            $count = $service->syncFiscalYear((int) $fiscalYear->FiscalYearId);
            $this->info("Synced {$count} bank month balance rows for fiscal year {$fiscalYear->Title}.");
        }

        Log::info('Command app:dashboard:cache-bank-month-balances finished.');

        return self::SUCCESS;
    }
}
