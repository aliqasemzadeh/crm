<?php

namespace App\Livewire\Panels\Accounting\Bank;

use App\Models\Sepidar\RPA\BankAccountBalance;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Index extends Component
{
    public $totalBalance = 0;

    public function render()
    {
        $bankAccounts = BankAccountBalance::all();
        return view('livewire.panels.accounting.bank.index', compact('bankAccounts'));
    }
}
