<?php

namespace App\Livewire\Panels\Accounting\ReceiptCheque;

use App\Livewire\Panels\Accounting\Concerns\SelectsJalaliChequeMonth;
use App\Models\Sepidar\RPA\ChequeState;
use App\Models\Sepidar\RPA\ReceiptCheque;
use App\Services\Accounting\ChequeMonthBuckets;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

class Index extends Component
{
    use SelectsJalaliChequeMonth;
    use WithPagination;

    public string $sortBy = 'Date';

    public string $sortDirection = 'asc';

    public string $search = '';

    #[Url]
    public int $year = 0;

    #[Url]
    public int $month = 0;

    public function mount(): void
    {
        $this->bootJalaliChequeMonth();
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function showsDepositNeeded(): bool
    {
        return false;
    }

    public function chequeIsPassed(ReceiptCheque $cheque): bool
    {
        return ChequeState::receiptPassed($cheque->State);
    }

    public function chequeTone(ReceiptCheque $cheque): string
    {
        if ($this->chequeIsPassed($cheque)) {
            return 'green';
        }

        if ($this->chequeIsOverdue($cheque)) {
            return 'red';
        }

        return '';
    }

    public function chequeStatusLabel(ReceiptCheque $cheque): string
    {
        if ((int) $cheque->State === 32) {
            return ChequeState::receiptLabel($cheque->State);
        }

        return $this->chequeIsPassed($cheque)
            ? __('app.cheque_passed')
            : __('app.cheque_unpassed');
    }

    public function chequeAccountLabel(ReceiptCheque $cheque): string
    {
        return $cheque->latestBankingItem?->bankAccount?->displayName() ?? __('app.cheque_no_bank_account');
    }

    public function chequeAccountBalance(ReceiptCheque $cheque): ?float
    {
        $account = $cheque->latestBankingItem?->bankAccount;

        return $account ? (float) $account->Balance : null;
    }

    #[Computed]
    public function chequeStats(): array
    {
        [$start, $end] = $this->gregorianBounds(1, 12);

        $cheques = ReceiptCheque::query()
            ->with(['latestBankingItem.bankAccount.bankBranch.bank'])
            ->where('State', '!=', 64)
            ->where('IsGuarantee', false)
            ->whereBetween('Date', [$start, $end])
            ->get();

        $monthly = ChequeMonthBuckets::blank();

        foreach ($cheques as $cheque) {
            if (! $cheque->Date) {
                continue;
            }

            $jalali = Jalalian::fromDateTime($cheque->Date);

            if ($jalali->getYear() !== $this->year) {
                continue;
            }

            $account = $cheque->latestBankingItem?->bankAccount;

            ChequeMonthBuckets::add(
                $monthly,
                $jalali->getMonth(),
                (float) $cheque->Amount,
                $this->chequeIsPassed($cheque),
                (int) ($account?->BankAccountId ?? 0),
                $account?->displayName() ?? __('app.cheque_no_bank_account'),
                $account ? (float) $account->Balance : null,
            );
        }

        return [
            'monthly' => $monthly,
            ...ChequeMonthBuckets::totals($monthly),
        ];
    }

    #[Computed]
    public function monthReport(): array
    {
        $month = $this->chequeStats['monthly'][$this->month];
        $accounts = ChequeMonthBuckets::accountRows($month['accounts'], false);

        return [
            'passed' => $month['passed'],
            'unpassed' => $month['unpassed'],
            'total' => $month['total'],
            'accounts' => $accounts,
            'deposit' => 0.0,
        ];
    }

    public static function clearCache(): void
    {
        $fiscalYearRef = config('sepidar.FiscalYearRef');
        Cache::forget("receipt_cheque_stats_fiscal_year_{$fiscalYearRef}");
    }

    #[Computed]
    public function cheques()
    {
        [$start, $end] = $this->gregorianBounds($this->month, $this->month);

        return ReceiptCheque::query()
            ->with(['dl', 'latestBankingItem.bankAccount.bankBranch.bank'])
            ->where('State', '!=', 64)
            ->where('IsGuarantee', false)
            ->whereBetween('Date', [$start, $end])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('Number', 'like', '%'.$this->search.'%')
                        ->orWhere('SayadCode', 'like', '%'.$this->search.'%')
                        ->orWhereHas('dl', function ($inner) {
                            $inner->where('Title', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(100);
    }

    #[Layout('layouts.panels.accounting')]
    public function render()
    {
        return view('livewire.panels.accounting.receipt-cheque.index');
    }

    private function chequeIsOverdue(ReceiptCheque $cheque): bool
    {
        if (! $cheque->Date || $this->chequeIsPassed($cheque)) {
            return false;
        }

        return Carbon::parse($cheque->Date)->startOfDay()->lt(now()->startOfDay());
    }
}
