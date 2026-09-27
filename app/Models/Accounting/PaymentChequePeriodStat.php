<?php

namespace App\Models\Accounting;

use App\Models\Sepidar\RPA\PaymentCheque;
use App\Services\Accounting\ChequePeriodBuckets;
use Illuminate\Support\Collection;
use Morilog\Jalali\Jalalian;

class PaymentChequePeriodStat
{
    /**
     * @return array{
     *     periods: list<array{label: string, range: string, from: int, to: int, passed: float, unpassed: float, remaining: float, total: float}>,
     *     passed: float,
     *     unpassed: float,
     *     remaining: float,
     *     total: float
     * }
     */
    public static function forMonth(int $year, int $month, int $periodDays = 10): array
    {
        $periodDays = ChequePeriodBuckets::normalizeDays($periodDays);
        $daysInMonth = (new Jalalian($year, $month, 1))->getMonthDays();
        $start = (new Jalalian($year, $month, 1))->toCarbon()->startOfDay();
        $end = (new Jalalian($year, $month, $daysInMonth))->toCarbon()->endOfDay();

        $cheques = PaymentCheque::query()
            ->withPassedFlag()
            ->where('State', 1)
            ->where('IsGuarantee', false)
            ->whereBetween('Date', [$start, $end])
            ->get();

        return self::build(
            $cheques,
            $year,
            $month,
            $daysInMonth,
            $periodDays,
            fn (PaymentCheque $cheque): bool => (int) $cheque->is_passed === 1,
        );
    }

    /**
     * @param  Collection<int, object{Date: mixed, Amount: mixed}>  $cheques
     * @return array{
     *     periods: list<array{label: string, range: string, from: int, to: int, passed: float, unpassed: float, remaining: float, total: float}>,
     *     passed: float,
     *     unpassed: float,
     *     remaining: float,
     *     total: float
     * }
     */
    public static function build(Collection $cheques, int $year, int $month, int $daysInMonth, int $periodDays, callable $isPassed): array
    {
        $periodDays = ChequePeriodBuckets::normalizeDays($periodDays);
        $periods = ChequePeriodBuckets::blank($daysInMonth, $periodDays);

        foreach ($cheques as $cheque) {
            if (! $cheque->Date) {
                continue;
            }

            $jalali = Jalalian::fromDateTime($cheque->Date);

            if ($jalali->getYear() !== $year || $jalali->getMonth() !== $month) {
                continue;
            }

            ChequePeriodBuckets::add(
                $periods,
                $jalali->getDay(),
                (float) $cheque->Amount,
                (bool) $isPassed($cheque),
            );
        }

        $rows = [];

        foreach ($periods as $index => $period) {
            $rows[] = [
                'label' => __('app.cheque_period_card_label', [
                    'n' => $index + 1,
                    'from' => $period['from'],
                    'to' => $period['to'],
                ]),
                'range' => __('app.invoice_period_range', ['from' => $period['from'], 'to' => $period['to']]),
                'from' => $period['from'],
                'to' => $period['to'],
                'passed' => $period['passed'],
                'unpassed' => $period['unpassed'],
                'remaining' => $period['remaining'],
                'total' => $period['total'],
            ];
        }

        return [
            'periods' => $rows,
            ...ChequePeriodBuckets::totals($periods),
        ];
    }
}
