<x-slot name="title">
    {{ __('app.customers') }}
</x-slot>

<div>
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.customers') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.customers_description') }}</flux:subheading>
            </div>
        </div>

        <flux:separator variant="subtle"/>
    </div>

    <div class="mb-6">
        <flux:field>
            <flux:label>{{ __('app.search') }}</flux:label>
            <flux:input wire:model.live.debounce.500ms="search" type="text"
                        placeholder="{{ __('app.search_in_customers') }}"/>
        </flux:field>
    </div>

    <livewire:panels.accounting.party.addresses/>
    <livewire:panels.accounting.party.invoices/>
    <livewire:panels.accounting.party.phones/>
    <livewire:panels.accounting.grouping.item.invoice/>
    <livewire:panels.accounting.grouping.item.receipt/>
    <livewire:panels.accounting.invoice.view/>


    <flux:table :paginate="$this->customers">
        <flux:table.columns>
            <flux:table.column class="w-1 whitespace-nowrap">{{ __('app.options') }}</flux:table.column>
            <flux:table.column>{{ __('app.name') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'LastName'" :direction="$sortDirection"
                               wire:click="sort('LastName')">{{ __('app.last_name') }}</flux:table.column>
            <flux:table.column>{{ __('app.economic_code') }}</flux:table.column>
            <flux:table.column>{{ __('app.identification_code') }}</flux:table.column>
        </flux:table.columns>

        @foreach ($this->customers as $customer)
            <flux:table.row :key="$customer->id">
                <flux:table.cell class="w-1 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                        <flux:button size="xs" variant="primary" color="sky"
                                     wire:click="$dispatch('panels.accounting.party.addresses.assign-data', { PartyId: '{{ $customer->PartyId }}' })">{{ __('app.address') }}</flux:button>
                        <flux:button size="xs" variant="primary" color="green"
                                     wire:click="$dispatch('panels.accounting.party.invoices.assign-data', { PartyId: '{{ $customer->PartyId }}' })">{{ __('app.invoices') }}</flux:button>
                        <flux:button size="xs" variant="primary" color="red"
                                     wire:click="$dispatch('panels.accounting.party.phones.assign-data', { PartyId: '{{ $customer->PartyId }}' })">{{ __('app.phones') }}</flux:button>
                    </div>
                </flux:table.cell>
                <flux:table.cell class="whitespace-nowrap">
                    {{ $customer->Name }}
                </flux:table.cell>
                <flux:table.cell class="whitespace-nowrap">
                    <div class="flex flex-row items-center gap-2">
                        {{ $customer->LastName }}
                    </div>
                </flux:table.cell>
                <flux:table.cell>
                    {{ $customer->EconomicCode }}
                </flux:table.cell>
                <flux:table.cell>
                    {{ $customer->IdentificationCode }}
                </flux:table.cell>

            </flux:table.row>
        @endforeach
    </flux:table>
</div>
