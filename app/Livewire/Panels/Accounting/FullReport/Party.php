<?php

namespace App\Livewire\Panels\Accounting\FullReport;

use App\Models\Sepidar\GNR\Party as SepidarParty;
use App\Models\Sepidar\INV\InventoryReceipt;
use App\Models\Sepidar\RPA\PaymentCheque;
use App\Models\Sepidar\RPA\ReceiptCheque;
use App\Models\Sepidar\SLS\Invoice;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class Party extends Component
{
    use WithPagination;

    public SepidarParty $party;

    public function mount(SepidarParty $party): void
    {
        $this->authorize('accounting_full_report_index');

        $this->party = $party;
    }

    public function openDebtReminderSms(): void
    {
        $summary = $this->summary;
        $finalBalance = (float) ($summary['final_balance'] ?? 0);

        if ($finalBalance <= 0) {
            return;
        }

        $this->dispatch(
            'panels.accounting.full-report.send-sms',
            (int) $this->party->PartyId,
            trim($this->party->Name.' '.$this->party->LastName),
            number_format($finalBalance),
        );
    }

    public function receiptChequeStateLabel(?int $state): string
    {
        return match ($state) {
            1 => __('app.receipt_cheque_state_1'),
            5 => __('app.receipt_cheque_state_5'),
            default => __('app.cheque_state_fallback', ['n' => $state ?? '-']),
        };
    }

    public function paymentChequeStateLabel(?int $state): string
    {
        return match ($state) {
            1 => __('app.payment_cheque_state_1'),
            2 => __('app.payment_cheque_state_2'),
            default => __('app.cheque_state_fallback', ['n' => $state ?? '-']),
        };
    }

    #[Computed]
    public function summary(): array
    {
        $fy = config('sepidar.FiscalYearRef');
        $dlRef = $this->party->DLRef;

        $empty = [
            'debit' => 0.0,
            'credit' => 0.0,
            'uncashed_receipts' => 0.0,
            'uncashed_payments' => 0.0,
            'balance' => 0.0,
            'final_balance' => 0.0,
        ];

        if (! $dlRef) {
            return $empty;
        }

        $vouchers = DB::connection('sqlsrv')->table('ACC.VoucherItem')
            ->join('ACC.Voucher', 'ACC.VoucherItem.VoucherRef', '=', 'ACC.Voucher.VoucherId')
            ->where('ACC.VoucherItem.DLRef', $dlRef)
            ->where('ACC.Voucher.FiscalYearRef', $fy)
            ->selectRaw('SUM(ACC.VoucherItem.Debit) as total_debit, SUM(ACC.VoucherItem.Credit) as total_credit')
            ->first();

        $uncashedReceipts = DB::connection('sqlsrv')->table('RPA.ReceiptCheque')
            ->where('DlRef', $dlRef)
            ->whereIn('State', [1, 5])
            ->sum('Amount');

        $uncashedPayments = DB::connection('sqlsrv')->table('RPA.PaymentCheque')
            ->where('DlRef', $dlRef)
            ->whereIn('State', [1, 2])
            ->sum('Amount');

        $debit = (float) ($vouchers->total_debit ?? 0);
        $credit = (float) ($vouchers->total_credit ?? 0);
        $ur = (float) $uncashedReceipts;
        $up = (float) $uncashedPayments;

        return [
            'debit' => $debit,
            'credit' => $credit,
            'uncashed_receipts' => $ur,
            'uncashed_payments' => $up,
            'balance' => $debit - $credit,
            'final_balance' => ($debit + $up) - ($credit + $ur),
        ];
    }

    #[Computed]
    public function partyInvoices()
    {
        return Invoice::query()
            ->where('CustomerPartyRef', $this->party->PartyId)
            ->where('FiscalYearRef', config('sepidar.FiscalYearRef'))
            ->orderByDesc('Date')
            ->orderByDesc('InvoiceId')
            ->paginate(25, ['*'], 'partyInvoices');
    }

    #[Computed]
    public function partyInventoryReceipts()
    {
        $dlRef = $this->party->DLRef;

        if (! $dlRef) {
            return InventoryReceipt::query()->whereRaw('1 = 0')->paginate(25, ['*'], 'partyInventoryReceipts');
        }

        return InventoryReceipt::query()
            ->with('dl')
            ->where('DelivererDLRef', $dlRef)
            ->where('FiscalYearRef', config('sepidar.FiscalYearRef'))
            ->orderByDesc('Date')
            ->orderByDesc('InventoryReceiptID')
            ->paginate(25, ['*'], 'partyInventoryReceipts');
    }

    #[Computed]
    public function voucherLines()
    {
        $fy = config('sepidar.FiscalYearRef');
        $dlRef = $this->party->DLRef;

        if (! $dlRef) {
            return DB::connection('sqlsrv')->table('ACC.VoucherItem as vi')
                ->join('ACC.Voucher as v', 'vi.VoucherRef', '=', 'v.VoucherId')
                ->whereRaw('1 = 0')
                ->select([
                    'vi.VoucherItemId',
                    'vi.VoucherRef',
                    'vi.Debit',
                    'vi.Credit',
                    'v.Date as voucher_date',
                    'v.Number as voucher_number',
                    'v.Description as voucher_description',
                ])
                ->paginate(25, ['*'], 'voucherLines');
        }

        return DB::connection('sqlsrv')->table('ACC.VoucherItem as vi')
            ->join('ACC.Voucher as v', 'vi.VoucherRef', '=', 'v.VoucherId')
            ->where('vi.DLRef', $dlRef)
            ->where('v.FiscalYearRef', $fy)
            ->orderByDesc('v.Date')
            ->orderByDesc('vi.VoucherItemId')
            ->select([
                'vi.VoucherItemId',
                'vi.VoucherRef',
                'vi.Debit',
                'vi.Credit',
                'v.Date as voucher_date',
                'v.Number as voucher_number',
                'v.Description as voucher_description',
            ])
            ->paginate(25, ['*'], 'voucherLines');
    }

    #[Computed]
    public function partyReceiptCheques()
    {
        $dlRef = $this->party->DLRef;

        if (! $dlRef) {
            return ReceiptCheque::query()->whereRaw('1 = 0')->paginate(25, ['*'], 'partyReceiptCheques');
        }

        return ReceiptCheque::query()
            ->with('dl')
            ->where('DlRef', $dlRef)
            ->whereIn('State', [1, 5])
            ->orderByDesc('Date')
            ->orderByDesc('ReceiptChequeId')
            ->paginate(25, ['*'], 'partyReceiptCheques');
    }

    #[Computed]
    public function partyPaymentCheques()
    {
        $dlRef = $this->party->DLRef;

        if (! $dlRef) {
            return PaymentCheque::query()->whereRaw('1 = 0')->paginate(25, ['*'], 'partyPaymentCheques');
        }

        return PaymentCheque::query()
            ->with('dl')
            ->where('DlRef', $dlRef)
            ->whereIn('State', [1, 2])
            ->orderByDesc('Date')
            ->orderByDesc('PaymentChequeId')
            ->paginate(25, ['*'], 'partyPaymentCheques');
    }

    #[Layout('layouts.panels.accounting')]
    public function render()
    {
        return view('livewire.panels.accounting.full-report.party');
    }
}
