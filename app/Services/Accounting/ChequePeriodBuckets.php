<?php

namespace App\Services\Accounting;

class ChequePeriodBuckets
{
    public const ALLOWED_DAYS = [5, 7, 10];

    public static function normalizeDays(int $periodDays): int
    {
        return in_array($periodDays, self::ALLOWED_DAYS, true) ? $periodDays : 10;
    }

    /**
     * @return array<int, array{0: int, 1: int}>
     */
    public static function ranges(int $daysInMonth, int $periodDays = 10): array
    {
        $chunk = self::normalizeDays($periodDays);
        $ranges = [];

        for ($start = 1; $start <= $daysInMonth; $start += $chunk) {
            $ranges[] = [$start, min($start + $chunk - 1, $daysInMonth)];
        }

        return $ranges;
    }

    /**
     * @return list<array{passed: float, unpassed: float, remaining: float, total: float, from: int, to: int}>
     */
    public static function blank(int $daysInMonth, int $periodDays = 10): array
    {
        $periods = [];

        foreach (self::ranges($daysInMonth, $periodDays) as [$from, $to]) {
            $periods[] = [
                'from' => $from,
                'to' => $to,
                'passed' => 0.0,
                'unpassed' => 0.0,
                'remaining' => 0.0,
                'total' => 0.0,
            ];
        }

        return $periods;
    }

    /**
     * @param  list<array{passed: float, unpassed: float, remaining: float, total: float, from: int, to: int}>  $periods
     */
    public static function add(array &$periods, int $day, float $amount, bool $passed): void
    {
        foreach ($periods as &$period) {
            if ($day < $period['from'] || $day > $period['to']) {
                continue;
            }

            $period['total'] += $amount;
            $period[$passed ? 'passed' : 'unpassed'] += $amount;
            $period['remaining'] = $period['total'] - $period['passed'];

            return;
        }
    }

    /**
     * @param  list<array{passed: float, unpassed: float, remaining: float, total: float, from: int, to: int}>  $periods
     * @return array{passed: float, unpassed: float, remaining: float, total: float}
     */
    public static function totals(array $periods): array
    {
        $passed = (float) array_sum(array_column($periods, 'passed'));
        $total = (float) array_sum(array_column($periods, 'total'));
        $unpassed = (float) array_sum(array_column($periods, 'unpassed'));

        return [
            'passed' => $passed,
            'unpassed' => $unpassed,
            'remaining' => $total - $passed,
            'total' => $total,
        ];
    }
}
