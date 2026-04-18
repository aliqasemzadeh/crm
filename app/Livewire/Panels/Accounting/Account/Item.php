<?php

namespace App\Livewire\Panels\Accounting\Account;

use App\Models\Sepidar\ACC\Account;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Item extends Component
{
    public Account $account;
    public bool $isExpanded = false;

    public function mount(Account $account)
    {
        $this->account = $account;
    }

    public function toggle()
    {
        $this->isExpanded = !$this->isExpanded;
    }

    #[Computed]
    public function children()
    {
        if (!$this->isExpanded) {
            return collect();
        }

        return $this->account->children()->with('topics')->get();
    }

    public function render()
    {
        return view('livewire.panels.accounting.account.item');
    }
}
