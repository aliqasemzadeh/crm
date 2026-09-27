<?php

namespace App\Services\Accounting;

use App\Models\Sepidar\RPA\PaymentCheque;
use App\Models\Sepidar\RPA\ReceiptCheque;
use Illuminate\Support\Facades\Cache;

class TodayChequeRemainingService
{
    /**
     * @return array{payment: float, receipt: float}
     */
    public static function balances(): array
    {
        $dateKey = now()->format('Y-m-d');
        $ttl = max(60, now()->endOfDay()->diffInSeconds(now()));

        return Cache::remember(self::cacheKey($dateKey), $ttl, function () {
            $start = now()->startOfDay();
            $end = now()->endOfDay();

            $payment = (float) PaymentCheque::query()
                ->where('State', 1)
                ->where('IsGuarantee', false)
                ->whereBetween('Date', [$start, $end])
                ->whereNotExists(function ($query) {
                    $query->selectRaw('1')
                        ->from('RPA.PaymentChequeBankingItem')
                        ->whereColumn(
                            'RPA.PaymentChequeBankingItem.PaymentChequeRef',
                            'RPA.PaymentCheque.PaymentChequeId'
                        );
                })
                ->sum('Amount');

            $passedStates = [4, 16, 32];

            $receipt = (float) ReceiptCheque::query()
                ->where('State', '!=', 64)
                ->where('IsGuarantee', false)
                ->whereNotIn('State', $passedStates)
                ->whereBetween('Date', [$start, $end])
                ->sum('Amount');

            return [
                'payment' => $payment,
                'receipt' => $receipt,
            ];
        });
    }

    public static function clearCache(): void
    {
        Cache::forget(self::cacheKey(now()->format('Y-m-d')));
    }

    private static function cacheKey(string $dateKey): string
    {
        return "accounting_today_cheque_remaining_{$dateKey}";
    }
}
