<?php

namespace App\Livewire\Panels\Accounting\Bank;

use App\Services\Accounting\TodayChequeRemainingService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    public $totalBalance = 0;
    public $totalUSDTBalance = 0;
    public $usdtRate = 0;
    public $search = '';

    /**
     * @return array{payment: float, receipt: float}
     */
    #[Computed]
    public function todayChequeRemaining(): array
    {
        return TodayChequeRemainingService::balances();
    }

    /**
     * @return array<int, array{payment: float, receipt: float}>
     */
    #[Computed]
    public function todayChequeRemainingByAccount(): array
    {
        return TodayChequeRemainingService::balancesByBankAccount();
    }

    #[Layout('layouts.panels.accounting')]
    public function render()
    {
        $bankAccounts = \App\Models\Sepidar\RPA\BankAccount::with(['bankBranch.bank', 'creator'])
            ->when($this->search, function ($query) {
                $query->whereHas('bankBranch.bank', function ($q) {
                    $q->where('Title', 'like', '%' . $this->search . '%');
                })->orWhere('AccountNo', 'like', '%' . $this->search . '%');
            })
            ->get();

        $this->totalBalance = $bankAccounts->sum('Balance');

        $this->usdtRate = \App\Models\CurrencyRate::getRate(now());

        if ($this->usdtRate > 0) {
            $this->totalUSDTBalance = $this->totalBalance / $this->usdtRate;
        }

        return view('livewire.panels.accounting.bank.index', compact('bankAccounts'));
    }
}
