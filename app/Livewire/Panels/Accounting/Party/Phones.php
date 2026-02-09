<?php

namespace App\Livewire\Panels\Accounting\Party;

use App\Models\Sepidar\GNR\Party;
use App\Models\Sepidar\GNR\PartyPhone;
use App\Models\Sepidar\SLS\Invoice;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Phones extends Component
{
    public ?Party $party = null;

    #[On('panels.accounting.party.phones.assign-data')]
    public function assignData($id): void
    {
        $this->party = Party::findOrFail($id);
        Flux::modal('panels.accounting.party.phones.modal')->show();
    }

    #[Computed]
    public function phones()
    {
        if (!$this->party) {
            return collect();
        }

        return PartyPhone::query()
            ->where('PartyRef', $this->party->PartyId)
            ->get();
    }

    public function render()
    {
        return view('livewire.panels.accounting.party.phones');
    }
}
