@php
    use App\Support\Accounting\CustomerSegment;
@endphp

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

    <flux:card class="panel-filter-card mb-6">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
            <flux:field>
                <flux:label>{{ __('app.search') }}</flux:label>
                <flux:input
                    wire:model.live.debounce.500ms="search"
                    type="text"
                    placeholder="{{ __('app.search_in_customers') }}"
                />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('app.customer_segment') }}</flux:label>
                <flux:select wire:model.live="customerSegment" searchable>
                    @foreach (CustomerSegment::options() as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            @if (CustomerSegment::isValuable($customerSegment))
                <flux:field>
                    <flux:label>{{ __('app.fiscal_year') }}</flux:label>
                    <flux:select wire:model.live="fiscalYearRef" searchable>
                        @foreach ($this->fiscalYears as $year)
                            <flux:select.option value="{{ $year->FiscalYearID }}">{{ $year->Title }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('app.month') }}</flux:label>
                    <flux:select wire:model.live="selectedMonth" searchable>
                        <flux:select.option value="0">{{ __('app.whole_year') }}</flux:select.option>
                        @for ($i = 1; $i <= 12; $i++)
                            <flux:select.option value="{{ $i }}">{{ __('app.jalali_months.'.$i) }}</flux:select.option>
                        @endfor
                    </flux:select>
                </flux:field>
            @endif
        </div>

        @if (CustomerSegment::isValuable($customerSegment))
            <flux:text class="mt-3 text-sm text-zinc-500">{{ __('app.customer_segment_valuable_hint') }}</flux:text>
        @endif
    </flux:card>

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
            @if (CustomerSegment::isValuable($customerSegment))
                <flux:table.column sortable :sorted="$sortBy === 'total_profit'" :direction="$sortDirection"
                                   wire:click="sort('total_profit')">{{ __('app.customer_period_profit') }}</flux:table.column>
                <flux:table.column>{{ __('app.invoice_count') }}</flux:table.column>
            @endif
        </flux:table.columns>

        @foreach ($this->customers as $customer)
            <flux:table.row :key="$customer->PartyId">
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
                @if (CustomerSegment::isValuable($customerSegment))
                    <flux:table.cell class="tabular-nums whitespace-nowrap">
                        {{ number_format((float) $customer->total_profit) }}
                    </flux:table.cell>
                    <flux:table.cell class="tabular-nums">
                        {{ number_format((int) $customer->invoice_count) }}
                    </flux:table.cell>
                @endif
            </flux:table.row>
        @endforeach
    </flux:table>
</div>
