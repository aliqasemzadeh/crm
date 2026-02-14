<?php

namespace App\Livewire\Panels\Accounting\Party;

use App\Models\Sepidar\GNR\Party;
use App\Models\Sepidar\SLS\Invoice;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Invoices extends Component
{
    public ?Party $party = null;

    #[On('panels.accounting.party.invoices.assign-data')]
    public function assignData($PartyId): void
    {
        $this->party = Party::findOrFail($PartyId);
        Flux::modal('panels.accounting.party.invoices.modal')->show();
    }

    #[Computed]
    public function invoices()
    {
        return $this->party?->invoices ?? collect();
    }

    public function render()
    {
        return view('livewire.panels.accounting.party.invoices');
    }
}
