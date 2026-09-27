<?php

namespace App\Models\Accounting;

use App\Models\Sepidar\RPA\ChequeState;
use App\Models\Sepidar\RPA\ReceiptCheque;
use App\Services\Accounting\ChequePeriodBuckets;
use Morilog\Jalali\Jalalian;

class ReceiptChequePeriodStat
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
    public static function forMonth(int $year, int $month, string $mode = 'ten_days'): array
    {
        $daysInMonth = (new Jalalian($year, $month, 1))->getMonthDays();
        $start = (new Jalalian($year, $month, 1))->toCarbon()->startOfDay();
        $end = (new Jalalian($year, $month, $daysInMonth))->toCarbon()->endOfDay();

        $cheques = ReceiptCheque::query()
            ->where('State', '!=', 64)
            ->where('IsGuarantee', false)
            ->whereBetween('Date', [$start, $end])
            ->get();

        return PaymentChequePeriodStat::build(
            $cheques,
            $year,
            $month,
            $daysInMonth,
            $mode,
            fn (ReceiptCheque $cheque): bool => ChequeState::receiptPassed($cheque->State),
        );
    }
}
