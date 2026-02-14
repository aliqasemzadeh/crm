<?php

namespace App\Livewire\Panels\Accounting\Bank;

use App\Models\Sepidar\RPA\BankAccountBalance;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    public $totalBalance = 0;
    public $totalUSDTBalance = 0;
    public $usdtRate = 0;
    public $search = '';

    #[Layout('layouts.panels.accounting')]
    public function render()
    {
        $fiscalYearRef = config('sepidar.FiscalYearRef');

        $bankAccounts = BankAccountBalance::with(['bankAccount.bankBranch.bank'])
            ->where('FiscalYearRef', $fiscalYearRef)
            ->when($this->search, function ($query) {
                $query->whereHas('bankAccount.bankBranch.bank', function ($q) {
                    $q->where('Title', 'like', '%' . $this->search . '%');
                })->orWhereHas('bankAccount', function ($q) {
                    $q->where('AccountNo', 'like', '%' . $this->search . '%');
                });
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
