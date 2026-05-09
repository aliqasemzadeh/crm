<?php

namespace App\Livewire\Panels\Accounting\FullReport;

use App\Models\Sepidar\GNR\Party;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    private function partyReportBaseQuery()
    {
        $fiscalYearRef = config('sepidar.FiscalYearRef');

        return Party::query()
            ->select('GNR.Party.PartyId', 'GNR.Party.Name', 'GNR.Party.LastName', 'GNR.Party.DLRef')
            ->leftJoin('ACC.DL', 'GNR.Party.DLRef', '=', 'ACC.DL.DLId')
            ->addSelect([
                'debit' => DB::connection('sqlsrv')->table('ACC.VoucherItem')
                    ->join('ACC.Voucher', 'ACC.VoucherItem.VoucherRef', '=', 'ACC.Voucher.VoucherId')
                    ->whereColumn('ACC.VoucherItem.DLRef', 'GNR.Party.DLRef')
                    ->where('ACC.Voucher.FiscalYearRef', $fiscalYearRef)
                    ->selectRaw('SUM(ACC.VoucherItem.Debit)'),
                'credit' => DB::connection('sqlsrv')->table('ACC.VoucherItem')
                    ->join('ACC.Voucher', 'ACC.VoucherItem.VoucherRef', '=', 'ACC.Voucher.VoucherId')
                    ->whereColumn('ACC.VoucherItem.DLRef', 'GNR.Party.DLRef')
                    ->where('ACC.Voucher.FiscalYearRef', $fiscalYearRef)
                    ->selectRaw('SUM(ACC.VoucherItem.Credit)'),
                'uncashed_receipts' => DB::connection('sqlsrv')->table('RPA.ReceiptCheque')
                    ->whereColumn('RPA.ReceiptCheque.DlRef', 'GNR.Party.DLRef')
                    ->whereIn('RPA.ReceiptCheque.State', [1, 5]) // 1: At Hand, 5: Returned
                    ->selectRaw('SUM(RPA.ReceiptCheque.Amount)'),
                'uncashed_payments' => DB::connection('sqlsrv')->table('RPA.PaymentCheque')
                    ->whereColumn('RPA.PaymentCheque.DlRef', 'GNR.Party.DLRef')
                    ->whereIn('RPA.PaymentCheque.State', [1, 2]) // Issued, not yet cashed
                    ->selectRaw('SUM(RPA.PaymentCheque.Amount)'),
            ])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('GNR.Party.Name', 'like', '%'.$this->search.'%')
                        ->orWhere('GNR.Party.LastName', 'like', '%'.$this->search.'%');
                });
            });
    }

    #[Computed]
    public function parties()
    {
        return $this->partyReportBaseQuery()->paginate(250);
    }

    #[Computed]
    public function finalBalanceSplits(): array
    {
        $debtorTotal = 0.0;
        $creditorTotal = 0.0;

        foreach ($this->partyReportBaseQuery()->cursor() as $party) {
            $debit = (float) ($party->debit ?? 0);
            $credit = (float) ($party->credit ?? 0);
            $uncashedReceipts = (float) ($party->uncashed_receipts ?? 0);
            $uncashedPayments = (float) ($party->uncashed_payments ?? 0);
            $final = ($debit + $uncashedPayments) - ($credit + $uncashedReceipts);

            if ($final > 0) {
                $debtorTotal += $final;
            } elseif ($final < 0) {
                $creditorTotal += abs($final);
            }
        }

        return [
            'debtor_total' => $debtorTotal,
            'creditor_total' => $creditorTotal,
        ];
    }

    #[Computed]
    public function totals()
    {
        $fiscalYearRef = config('sepidar.FiscalYearRef');

        // We calculate totals for the filtered parties if search is active, or for all parties
        $query = Party::query()
            ->leftJoin('ACC.DL', 'GNR.Party.DLRef', '=', 'ACC.DL.DLId')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('GNR.Party.Name', 'like', '%'.$this->search.'%')
                        ->orWhere('GNR.Party.LastName', 'like', '%'.$this->search.'%');
                });
            });

        $dlRefs = $query->pluck('GNR.Party.DLRef')->filter()->toArray();

        if (empty($dlRefs)) {
            return [
                'debit' => 0,
                'credit' => 0,
                'uncashed_receipts' => 0,
                'uncashed_payments' => 0,
            ];
        }

        $vouchers = DB::connection('sqlsrv')->table('ACC.VoucherItem')
            ->join('ACC.Voucher', 'ACC.VoucherItem.VoucherRef', '=', 'ACC.Voucher.VoucherId')
            ->whereIn('ACC.VoucherItem.DLRef', $dlRefs)
            ->where('ACC.Voucher.FiscalYearRef', $fiscalYearRef)
            ->selectRaw('SUM(Debit) as total_debit, SUM(Credit) as total_credit')
            ->first();

        $receiptCheques = DB::connection('sqlsrv')->table('RPA.ReceiptCheque')
            ->whereIn('DlRef', $dlRefs)
            ->whereIn('State', [1, 5])
            ->sum('Amount');

        $paymentCheques = DB::connection('sqlsrv')->table('RPA.PaymentCheque')
            ->whereIn('DlRef', $dlRefs)
            ->whereIn('State', [1, 2])
            ->sum('Amount');

        return [
            'debit' => $vouchers->total_debit ?? 0,
            'credit' => $vouchers->total_credit ?? 0,
            'uncashed_receipts' => $receiptCheques,
            'uncashed_payments' => $paymentCheques,
        ];
    }

    #[Layout('layouts.panels.accounting')]
    public function render()
    {
        return view('livewire.panels.accounting.full-report.index');
    }
}
