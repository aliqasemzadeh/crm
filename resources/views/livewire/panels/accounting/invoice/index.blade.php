<x-slot name="title">
    {{ __('app.invoices') }}
</x-slot>

<div>
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.invoices') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.invoices_description') }}</flux:subheading>
            </div>
        </div>

        <flux:separator variant="subtle" />
    </div>

    <div class="mb-6">
        <flux:field>
            <flux:label>{{ __('app.search') }}</flux:label>
            <flux:input wire:model.live.debounce.500ms="search" type="text" placeholder="{{ __('app.search_in_invoices') }}" />
        </flux:field>
    </div>

    <livewire:panels.accounting.grouping.item.invoice />
    <livewire:panels.accounting.grouping.item.receipt />
    <livewire:panels.accounting.invoice.view />


    <flux:table :paginate="$this->invoices">
        <flux:table.columns>
            <flux:table.column class="w-1 whitespace-nowrap">{{ __('app.options') }}</flux:table.column>
            <flux:table.column>{{ __('app.number') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'CustomerRealName'" :direction="$sortDirection" wire:click="sort('CustomerRealName')">{{ __('app.name') }}</flux:table.column>
            <flux:table.column>{{ __('app.category') }}</flux:table.column>
            <flux:table.column>{{ __('app.price') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'Date'" :direction="$sortDirection" wire:click="sort('Date')">{{ __('app.date') }}</flux:table.column>

        </flux:table.columns>

        @foreach ($this->invoices as $invoice)
            <flux:table.row :key="$invoice->id">
                <flux:table.cell class="w-1 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                        <flux:button size="xs" variant="primary" color="sky" wire:click="$dispatch('panels.accounting.invoice.view.assign-data', { id: '{{ $invoice->InvoiceId }}' })">{{ __('app.view') }}</flux:button>
                    </div>
                </flux:table.cell>
                <flux:table.cell class="whitespace-nowrap">
                    {{ $invoice->Number }}
                </flux:table.cell>
                <flux:table.cell class="whitespace-nowrap">
                    <div class="flex flex-row items-center gap-2">
                        {{ $invoice->CustomerRealName }}
                    </div>
                </flux:table.cell>
                <flux:table.cell class="whitespace-nowrap">
                    @if($invoice->SaleTypeRef == 1)
                        رسمی
                    @else
                        غیر رسمی
                    @endif
                </flux:table.cell>
                <flux:table.cell class="whitespace-nowrap">
                    {{ number_format($invoice->Price) }}
                </flux:table.cell>
                <flux:table.cell class="whitespace-nowrap">
                    {{ $invoice->Date ? \Morilog\Jalali\Jalalian::fromDateTime($invoice->Date)->format('%Y-%m-%d') : '-' }}
                </flux:table.cell>

            </flux:table.row>
        @endforeach
    </flux:table>
</div>
