<?php

namespace App\Services\Dashboard;

use App\Models\Crm\BankAccountMonthBalance;
use App\Models\Sepidar\FMK\FiscalYear;
use App\Models\Sepidar\RPA\BankAccount;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;

class BankAccountMonthBalanceService
{
    public static function cacheKey(int|string $fiscalYearRef): string
    {
        return "administrator_dashboard_bank_month_balances_{$fiscalYearRef}";
    }

    public static function clearCache(int|string $fiscalYearRef): void
    {
        for ($month = 1; $month <= 12; $month++) {
            Cache::forget(self::cacheKey($fiscalYearRef).'_month_'.$month);
        }
    }

    public function syncFiscalYear(int|string $fiscalYearRef): int
    {
        $fiscalYear = FiscalYear::query()->find($fiscalYearRef);

        if (! $fiscalYear) {
            return 0;
        }

        $jalaliYear = (int) $fiscalYear->Title;
        $now = Jalalian::now();
        $maxMonth = ((int) $now->getYear() === $jalaliYear)
            ? (int) $now->getMonth()
            : (((int) $now->getYear() > $jalaliYear) ? 12 : 0);

        if ($maxMonth < 1) {
            return 0;
        }

        $accounts = BankAccount::query()
            ->with(['bankBranch.bank'])
            ->get();

        $upserted = 0;

        foreach ($accounts as $account) {
            $label = $this->accountLabel($account);
            $movements = $this->movementsForAccount((int) $account->BankAccountId, (int) $fiscalYearRef);

            for ($month = 1; $month <= $maxMonth; $month++) {
                [$ending, $min] = $this->balancesForMonth($movements, $jalaliYear, $month);

                BankAccountMonthBalance::query()->updateOrCreate(
                    [
                        'fiscal_year_ref' => (int) $fiscalYearRef,
                        'jalali_year' => $jalaliYear,
                        'jalali_month' => $month,
                        'bank_account_id' => (int) $account->BankAccountId,
                    ],
                    [
                        'account_label' => $label,
                        'ending_balance' => $ending,
                        'min_balance' => $min,
                    ]
                );

                $upserted++;
            }
        }

        self::clearCache($fiscalYearRef);

        return $upserted;
    }

    /**
     * @return array<int, array{account: string, ending_balance: float, min_balance: float}>
     */
    public function chartRows(int|string $fiscalYearRef, int $jalaliMonth): array
    {
        $exists = BankAccountMonthBalance::query()
            ->where('fiscal_year_ref', $fiscalYearRef)
            ->where('jalali_month', $jalaliMonth)
            ->exists();

        if (! $exists) {
            $this->syncFiscalYear($fiscalYearRef);
        }

        return Cache::rememberForever(self::cacheKey($fiscalYearRef).'_month_'.$jalaliMonth, function () use ($fiscalYearRef, $jalaliMonth) {
            return BankAccountMonthBalance::query()
                ->where('fiscal_year_ref', $fiscalYearRef)
                ->where('jalali_month', $jalaliMonth)
                ->orderByDesc('ending_balance')
                ->get()
                ->map(fn (BankAccountMonthBalance $row) => [
                    'account' => $row->account_label,
                    'ending_balance' => (float) $row->ending_balance,
                    'min_balance' => (float) $row->min_balance,
                ])
                ->values()
                ->all();
        });
    }

    private function accountLabel(BankAccount $account): string
    {
        $bankTitle = $account->bankBranch?->bank?->Title;
        $owner = $account->Owner;
        $accountNo = $account->AccountNo;

        if ($bankTitle && $accountNo) {
            return trim($bankTitle.' - '.$accountNo);
        }

        if ($owner && $accountNo) {
            return trim($owner.' - '.$accountNo);
        }

        return (string) ($accountNo ?: $owner ?: ('#'.$account->BankAccountId));
    }

    /**
     * @return list<array{date: string, amount: float}>
     */
    private function movementsForAccount(int $bankAccountId, int $fiscalYearRef): array
    {
        return DB::connection('sqlsrv')
            ->table('RPA.vwBankAccountBalanceFiscalYear')
            ->where('BankAccountRef', $bankAccountId)
            ->where('FiscalYearRef', $fiscalYearRef)
            ->orderBy('Date')
            ->orderBy('DocItemRef')
            ->get(['Date', 'AmountInBaseCurrency'])
            ->map(fn ($row) => [
                'date' => substr((string) $row->Date, 0, 10),
                'amount' => (float) $row->AmountInBaseCurrency,
            ])
            ->all();
    }

    /**
     * @param  list<array{date: string, amount: float}>  $movements
     * @return array{0: float, 1: float}
     */
    private function balancesForMonth(array $movements, int $jalaliYear, int $month): array
    {
        $monthStart = (new Jalalian($jalaliYear, $month, 1))->toCarbon()->startOfDay();
        $daysInMonth = (new Jalalian($jalaliYear, $month, 1))->getMonthDays();
        $monthEnd = (new Jalalian($jalaliYear, $month, $daysInMonth))->toCarbon()->endOfDay();

        $startDate = $monthStart->toDateString();
        $endDate = $monthEnd->toDateString();

        $running = 0.0;
        $minInMonth = null;
        $seenInMonth = false;

        foreach ($movements as $movement) {
            $date = $movement['date'];

            if ($date > $endDate) {
                break;
            }

            $running += $movement['amount'];

            if ($date >= $startDate && $date <= $endDate) {
                $seenInMonth = true;
                $minInMonth = $minInMonth === null ? $running : min($minInMonth, $running);
            }
        }

        $ending = $running;
        $min = $seenInMonth ? (float) $minInMonth : $ending;

        return [round($ending, 2), round($min, 2)];
    }
}
