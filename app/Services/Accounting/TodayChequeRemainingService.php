<?php

namespace App\Services\Accounting;

use App\Models\Sepidar\RPA\PaymentCheque;
use App\Models\Sepidar\RPA\ReceiptCheque;
use App\Models\Sepidar\RPA\ReceiptChequeBankingItem;
use Illuminate\Support\Facades\Cache;

class TodayChequeRemainingService
{
    /**
     * @return array{payment: float, receipt: float}
     */
    public static function balances(): array
    {
        $payload = self::payload();

        return [
            'payment' => $payload['payment'],
            'receipt' => $payload['receipt'],
        ];
    }

    /**
     * @return array<int, array{payment: float, receipt: float}>
     */
    public static function balancesByBankAccount(): array
    {
        return self::payload()['by_account'];
    }

    /**
     * @return array{payment: float, receipt: float, by_account: array<int, array{payment: float, receipt: float}>}
     */
    public static function payload(): array
    {
        $dateKey = now()->format('Y-m-d');
        $ttl = max(60, (int) now()->endOfDay()->diffInSeconds(now()));

        return Cache::remember(self::cacheKey($dateKey), $ttl, function () {
            $start = now()->startOfDay();
            $end = now()->endOfDay();
            $passedStates = [4, 16, 32];

            $paymentRows = PaymentCheque::query()
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
                ->selectRaw('COALESCE(BankAccountRef, 0) AS bank_account_id, SUM(Amount) AS total')
                ->groupByRaw('COALESCE(BankAccountRef, 0)')
                ->get();

            $latestBanking = ReceiptChequeBankingItem::query()
                ->from('RPA.ReceiptChequeBankingItem')
                ->selectRaw('ReceiptChequeRef, BankAccountRef, ROW_NUMBER() OVER (PARTITION BY ReceiptChequeRef ORDER BY ReceiptChequeBankingItemId DESC) AS rn');

            $receiptRows = ReceiptCheque::query()
                ->from('RPA.ReceiptCheque as rc')
                ->where('rc.State', '!=', 64)
                ->where('rc.IsGuarantee', false)
                ->whereNotIn('rc.State', $passedStates)
                ->whereBetween('rc.Date', [$start, $end])
                ->leftJoinSub($latestBanking, 'bi', function ($join) {
                    $join->on('bi.ReceiptChequeRef', '=', 'rc.ReceiptChequeId')
                        ->where('bi.rn', '=', 1);
                })
                ->selectRaw('COALESCE(bi.BankAccountRef, 0) AS bank_account_id, SUM(rc.Amount) AS total')
                ->groupByRaw('COALESCE(bi.BankAccountRef, 0)')
                ->get();

            $byAccount = [];
            $paymentTotal = 0.0;
            $receiptTotal = 0.0;

            foreach ($paymentRows as $row) {
                $id = (int) $row->bank_account_id;
                $amount = (float) $row->total;
                $paymentTotal += $amount;
                $byAccount[$id] ??= ['payment' => 0.0, 'receipt' => 0.0];
                $byAccount[$id]['payment'] = $amount;
            }

            foreach ($receiptRows as $row) {
                $id = (int) $row->bank_account_id;
                $amount = (float) $row->total;
                $receiptTotal += $amount;
                $byAccount[$id] ??= ['payment' => 0.0, 'receipt' => 0.0];
                $byAccount[$id]['receipt'] = $amount;
            }

            return [
                'payment' => $paymentTotal,
                'receipt' => $receiptTotal,
                'by_account' => $byAccount,
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
