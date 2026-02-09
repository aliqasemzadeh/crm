<?php

namespace App\Livewire\Panels\Accounting\Party;

use App\Models\Sepidar\GNR\Party;
use App\Models\Sepidar\GNR\PartyAddress;
use App\Models\Sepidar\GNR\PartyPhone;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Addresses extends Component
{
    public ?Party $party = null;

    #[On('panels.accounting.party.addresses.assign-data')]
    public function assignData($PartyId): void
    {
        $this->party = Party::findOrFail($PartyId);
        Flux::modal('panels.accounting.party.addresses.modal')->show();
    }

    #[Computed]
    public function addresses()
    {
        if (!$this->party) {
            return collect();
        }

        return PartyAddress::query()
            ->where('PartyRef', $this->party->PartyId)
            ->get();
    }

    public function render()
    {
        return view('livewire.panels.accounting.party.addresses');
    }
}
