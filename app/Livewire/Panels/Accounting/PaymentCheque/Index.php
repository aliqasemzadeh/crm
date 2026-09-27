<?php

namespace App\Livewire\Panels\Accounting\PaymentCheque;

use App\Livewire\Panels\Accounting\Concerns\SelectsJalaliChequeMonth;
use App\Models\Sepidar\RPA\PaymentCheque;
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
        return true;
    }

    public function chequeIsPassed(PaymentCheque $cheque): bool
    {
        return (int) $cheque->is_passed === 1;
    }

    public function chequeTone(PaymentCheque $cheque): string
    {
        if ($this->chequeIsPassed($cheque)) {
            return 'green';
        }

        if ($this->chequeIsOverdue($cheque)) {
            return 'red';
        }

        return '';
    }

    public function chequeStatusLabel(PaymentCheque $cheque): string
    {
        return $this->chequeIsPassed($cheque)
            ? __('app.cheque_passed')
            : __('app.cheque_unpassed');
    }

    public function chequeAccountLabel(PaymentCheque $cheque): string
    {
        return $cheque->bankAccount?->displayName() ?? __('app.cheque_no_bank_account');
    }

    public function chequeAccountBalance(PaymentCheque $cheque): ?float
    {
        return $cheque->bankAccount ? (float) $cheque->bankAccount->Balance : null;
    }

    #[Computed]
    public function chequeStats(): array
    {
        [$start, $end] = $this->gregorianBounds(1, 12);

        $cheques = PaymentCheque::query()
            ->with(['bankAccount.bankBranch.bank'])
            ->withPassedFlag()
            ->where('State', 1)
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

            $account = $cheque->bankAccount;

            ChequeMonthBuckets::add(
                $monthly,
                $jalali->getMonth(),
                (float) $cheque->Amount,
                $this->chequeIsPassed($cheque),
                (int) ($cheque->BankAccountRef ?? 0),
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
        $accounts = ChequeMonthBuckets::accountRows($month['accounts'], true);

        return [
            'passed' => $month['passed'],
            'unpassed' => $month['unpassed'],
            'remaining' => $month['total'] - $month['passed'],
            'total' => $month['total'],
            'accounts' => $accounts,
            'deposit' => array_sum(array_map(fn (array $row) => $row['deposit'] ?? 0, $accounts)),
        ];
    }

    public static function clearCache(): void
    {
        $fiscalYearRef = config('sepidar.FiscalYearRef');
        Cache::forget("payment_cheque_stats_fiscal_year_{$fiscalYearRef}");
        \App\Services\Accounting\TodayChequeRemainingService::clearCache();
    }

    #[Computed]
    public function cheques()
    {
        [$start, $end] = $this->gregorianBounds($this->month, $this->month);

        return PaymentCheque::query()
            ->with(['dl', 'bankAccount.bankBranch.bank'])
            ->withPassedFlag()
            ->where('State', 1)
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
        return view('livewire.panels.accounting.payment-cheque.index');
    }

    private function chequeIsOverdue(PaymentCheque $cheque): bool
    {
        if (! $cheque->Date) {
            return false;
        }

        return Carbon::parse($cheque->Date)->startOfDay()->lt(now()->startOfDay());
    }
}
