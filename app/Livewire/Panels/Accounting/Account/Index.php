<?php

namespace App\Livewire\Panels\Accounting\Account;

use App\Models\Sepidar\ACC\Account;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Index extends Component
{
    #[Computed]
    public function accounts()
    {
        return Account::with(['topics'])
            ->whereNull('ParentAccountRef')
            ->get();
    }

    public function render()
    {
        return view('livewire.panels.accounting.account.index')
            ->layout('layouts.panels.accounting');
    }
}
