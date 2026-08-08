<?php

use App\Models\Sepidar\GNR\Party;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public ?Party $party = null;

    #[On('panels.sale.party.phones.assign-data')]
    public function assignData($PartyId): void
    {
        $this->authorize('sales_party_phone');

        $this->party = Party::findOrFail($PartyId);
        Flux::modal('panels.sale.party.phones.modal')->show();
    }

    #[Computed]
    public function phones()
    {
        return $this->party?->phones ?? collect();
    }
};
?>

<flux:modal name="panels.sale.party.phones.modal" class="md:w-1/3" flyout position="right">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.phones') }}: {{ $party->Name ?? '' }} {{ $party->LastName ?? '' }}</flux:heading>
            <flux:text class="mt-2">{{ __('app.phones_description') }}</flux:text>
        </div>
        <flux:table>
            <flux:table.columns class="bg-white dark:bg-zinc-900">
                <flux:table.column>{{ __('app.phone') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach($this->phones as $phone)
                    <flux:table.row wire:key="sale-phone-{{ $phone->PartyPhoneId ?? $phone->Phone }}">
                        <flux:table.cell>{{ $phone->Phone }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>
</flux:modal>
