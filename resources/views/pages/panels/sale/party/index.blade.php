<?php

use App\Models\Sepidar\GNR\Party;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.panels.sale')] class extends Component
{
    use WithPagination;

    public string $sortBy = 'CreationDate';

    public string $sortDirection = 'desc';

    public string $search = '';

    public function mount(): void
    {
        $this->authorize('sales_party_index');
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function customers()
    {
        return Party::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('Name', 'like', '%'.$this->search.'%')
                        ->orWhere('LastName', 'like', '%'.$this->search.'%');
                });
            })
            ->tap(function ($query) {
                if ($this->sortBy) {
                    $query->orderBy($this->sortBy, $this->sortDirection);
                }
            })
            ->paginate(25);
    }
};
?>

<x-slot name="title">
    {{ __('app.customers') }}
</x-slot>

<div>
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.customers') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.sales_customers_description') }}</flux:subheading>
            </div>
        </div>
        <flux:separator variant="subtle"/>
    </div>

    <flux:card class="mb-6">
        <flux:field>
            <flux:label>{{ __('app.search') }}</flux:label>
            <flux:input wire:model.live.debounce.500ms="search" type="text" placeholder="{{ __('app.search_in_customers') }}"/>
        </flux:field>
    </flux:card>

    <livewire:panels.sale.party.addresses />
    <livewire:panels.sale.party.phones />

    <flux:table :paginate="$this->customers">
        <flux:table.columns>
            <flux:table.column class="w-1 whitespace-nowrap">{{ __('app.options') }}</flux:table.column>
            <flux:table.column>{{ __('app.name') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'LastName'" :direction="$sortDirection" wire:click="sort('LastName')">{{ __('app.last_name') }}</flux:table.column>
            <flux:table.column>{{ __('app.economic_code') }}</flux:table.column>
            <flux:table.column>{{ __('app.identification_code') }}</flux:table.column>
        </flux:table.columns>

        @foreach ($this->customers as $customer)
            <flux:table.row :key="$customer->PartyId">
                <flux:table.cell class="w-1 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                        @can('sales_party_view')
                            <flux:tooltip content="{{ __('app.view') }}">
                                <flux:button
                                    size="xs"
                                    variant="primary"
                                    color="teal"
                                    icon="eye"
                                    icon:variant="outline"
                                    href="{{ route('panels.sale.party.view', $customer->PartyId) }}"
                                    wire:navigate
                                />
                            </flux:tooltip>
                        @endcan
                        @can('sales_party_address')
                            <flux:tooltip content="{{ __('app.address') }}">
                                <flux:button
                                    size="xs"
                                    variant="primary"
                                    color="sky"
                                    icon="map-pin"
                                    icon:variant="outline"
                                    wire:click="$dispatch('panels.sale.party.addresses.assign-data', { PartyId: '{{ $customer->PartyId }}' })"
                                />
                            </flux:tooltip>
                        @endcan
                        @can('sales_party_phone')
                            <flux:tooltip content="{{ __('app.phones') }}">
                                <flux:button
                                    size="xs"
                                    variant="primary"
                                    color="rose"
                                    icon="phone"
                                    icon:variant="outline"
                                    wire:click="$dispatch('panels.sale.party.phones.assign-data', { PartyId: '{{ $customer->PartyId }}' })"
                                />
                            </flux:tooltip>
                        @endcan
                    </div>
                </flux:table.cell>
                <flux:table.cell class="whitespace-nowrap">{{ $customer->Name }}</flux:table.cell>
                <flux:table.cell class="whitespace-nowrap">{{ $customer->LastName }}</flux:table.cell>
                <flux:table.cell>{{ $customer->EconomicCode }}</flux:table.cell>
                <flux:table.cell>{{ $customer->IdentificationCode }}</flux:table.cell>
            </flux:table.row>
        @endforeach
    </flux:table>
</div>
