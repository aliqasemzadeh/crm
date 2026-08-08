<?php

namespace App\Services\Sepidar;

use App\Models\Sepidar\GNR\Party;
use Illuminate\Support\Facades\DB;

class PartyBalance
{
    /**
     * @return array{
     *     debit: float,
     *     credit: float,
     *     uncashed_receipts: float,
     *     uncashed_payments: float,
     *     balance: float,
     *     final_balance: float,
     *     debtor: float,
     *     creditor: float
     * }
     */
    public function forParty(int $partyId): array
    {
        $empty = [
            'debit' => 0.0,
            'credit' => 0.0,
            'uncashed_receipts' => 0.0,
            'uncashed_payments' => 0.0,
            'balance' => 0.0,
            'final_balance' => 0.0,
            'debtor' => 0.0,
            'creditor' => 0.0,
        ];

        $party = Party::query()->select(['PartyId', 'DLRef'])->find($partyId);

        if (! $party?->DLRef) {
            return $empty;
        }

        $dlRef = $party->DLRef;
        $fy = config('sepidar.FiscalYearRef');

        $vouchers = DB::connection('sqlsrv')->table('ACC.VoucherItem')
            ->join('ACC.Voucher', 'ACC.VoucherItem.VoucherRef', '=', 'ACC.Voucher.VoucherId')
            ->where('ACC.VoucherItem.DLRef', $dlRef)
            ->where('ACC.Voucher.FiscalYearRef', $fy)
            ->selectRaw('SUM(ACC.VoucherItem.Debit) as total_debit, SUM(ACC.VoucherItem.Credit) as total_credit')
            ->first();

        $uncashedReceipts = (float) DB::connection('sqlsrv')->table('RPA.ReceiptCheque')
            ->where('DlRef', $dlRef)
            ->whereIn('State', [1, 5])
            ->sum('Amount');

        $uncashedPayments = (float) DB::connection('sqlsrv')->table('RPA.PaymentCheque')
            ->where('DlRef', $dlRef)
            ->whereIn('State', [1, 2])
            ->sum('Amount');

        $debit = (float) ($vouchers->total_debit ?? 0);
        $credit = (float) ($vouchers->total_credit ?? 0);
        $final = ($debit + $uncashedPayments) - ($credit + $uncashedReceipts);

        return [
            'debit' => $debit,
            'credit' => $credit,
            'uncashed_receipts' => $uncashedReceipts,
            'uncashed_payments' => $uncashedPayments,
            'balance' => $debit - $credit,
            'final_balance' => $final,
            'debtor' => max($final, 0.0),
            'creditor' => max(-$final, 0.0),
        ];
    }
}
