<?php

use App\Models\Sepidar\GNR\Party;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.panels.sale')] class extends Component
{
    public Party $party;

    public function mount(Party $party): void
    {
        $this->authorize('sales_party_view');
        $this->party = $party->load(['phones', 'addresses']);
    }

    public function partyName(): string
    {
        return trim(implode(' ', array_filter([
            trim((string) ($this->party->Name ?? '')),
            trim((string) ($this->party->LastName ?? '')),
        ], static fn (string $part): bool => $part !== ''))) ?: '-';
    }

    #[Computed]
    public function phones()
    {
        return $this->party->phones;
    }

    #[Computed]
    public function addresses()
    {
        return $this->party->addresses;
    }
};
?>

<x-slot name="title">
    {{ __('app.customer') }} — {{ $this->partyName() }}
</x-slot>

<div>
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between gap-4">
            <div>
                <flux:heading size="xl" level="1">{{ $this->partyName() }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.sales_customers_description') }}</flux:subheading>
            </div>
            <flux:button variant="ghost" href="{{ route('panels.sale.party.index') }}" wire:navigate icon="arrow-right">
                {{ __('app.back') }}
            </flux:button>
        </div>
        <flux:separator variant="subtle" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        @can('sales_party_phone')
            <flux:card>
                <flux:heading size="lg" class="mb-4">{{ __('app.phones') }}</flux:heading>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('app.phone') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse($this->phones as $phone)
                            <flux:table.row wire:key="party-view-phone-{{ $phone->PartyPhoneId ?? $loop->index }}">
                                <flux:table.cell>{{ $phone->Phone }}</flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell>{{ __('app.no_results') }}</flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </flux:card>
        @endcan

        @can('sales_party_address')
            <flux:card>
                <flux:heading size="lg" class="mb-4">{{ __('app.addresses') }}</flux:heading>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('app.address') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse($this->addresses as $address)
                            <flux:table.row wire:key="party-view-address-{{ $address->PartyAddressId ?? $loop->index }}">
                                <flux:table.cell>{{ $address->Address }}</flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell>{{ __('app.no_results') }}</flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </flux:card>
        @endcan
    </div>
</div>
