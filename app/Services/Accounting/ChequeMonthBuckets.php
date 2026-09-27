<?php

namespace App\Services\Accounting;

class ChequeMonthBuckets
{
    /**
     * @return array<int, array{passed: float, unpassed: float, total: float, accounts: array<int, array{label: string, balance: ?float, passed: float, unpassed: float}>}>
     */
    public static function blank(): array
    {
        $months = [];

        for ($month = 1; $month <= 12; $month++) {
            $months[$month] = [
                'passed' => 0.0,
                'unpassed' => 0.0,
                'total' => 0.0,
                'accounts' => [],
            ];
        }

        return $months;
    }

    /**
     * @param  array<int, array{passed: float, unpassed: float, total: float, accounts: array<int, array{label: string, balance: ?float, passed: float, unpassed: float}>}>  $months
     */
    public static function add(
        array &$months,
        int $month,
        float $amount,
        bool $passed,
        int $accountId,
        string $label,
        ?float $balance,
    ): void {
        $months[$month]['total'] += $amount;
        $months[$month][$passed ? 'passed' : 'unpassed'] += $amount;

        if (! isset($months[$month]['accounts'][$accountId])) {
            $months[$month]['accounts'][$accountId] = [
                'label' => $label,
                'balance' => $balance,
                'passed' => 0.0,
                'unpassed' => 0.0,
            ];
        }

        $months[$month]['accounts'][$accountId][$passed ? 'passed' : 'unpassed'] += $amount;
    }

    /**
     * @param  array<int, array{passed: float, unpassed: float, total: float}>  $months
     * @return array{passed: float, unpassed: float, total: float}
     */
    public static function totals(array $months): array
    {
        return [
            'passed' => (float) array_sum(array_column($months, 'passed')),
            'unpassed' => (float) array_sum(array_column($months, 'unpassed')),
            'total' => (float) array_sum(array_column($months, 'total')),
        ];
    }

    /**
     * @param  array<int, array{label: string, balance: ?float, passed: float, unpassed: float}>  $accounts
     * @return list<array{label: string, balance: ?float, passed: float, unpassed: float, deposit: ?float}>
     */
    public static function accountRows(array $accounts, bool $withDeposit): array
    {
        $rows = [];

        foreach ($accounts as $account) {
            $balance = $account['balance'];
            $unpassed = $account['unpassed'];

            $rows[] = [
                'label' => $account['label'],
                'balance' => $balance,
                'passed' => $account['passed'],
                'unpassed' => $unpassed,
                'deposit' => ($withDeposit && $balance !== null) ? max(0, $unpassed - $balance) : null,
            ];
        }

        usort($rows, function (array $left, array $right): int {
            $byUnpassed = $right['unpassed'] <=> $left['unpassed'];

            return $byUnpassed !== 0 ? $byUnpassed : strcmp($left['label'], $right['label']);
        });

        return $rows;
    }
}
