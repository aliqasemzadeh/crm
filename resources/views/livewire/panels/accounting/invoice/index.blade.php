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

    <div class="space-y-6 mb-10">
        @php
            $monthly = $this->invoiceStats['monthly'];
            $maxAmount = max($monthly);
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($monthly as $monthNumber => $amount)
                @php
                    $colorClass = '';
                    if ($amount > 0 && $amount == $maxAmount) {
                        $colorClass = 'border-green-500 bg-green-50/50 dark:bg-green-900/20';
                    }
                @endphp
                <flux:card class="flex flex-col items-center justify-center p-6 border-t-4 {{ $colorClass }}">
                    <flux:heading size="lg" class="mb-2">
                        {{ __('app.jalali_months.' . $monthNumber) }}
                    </flux:heading>
                    <flux:text size="xl" class="font-bold text-zinc-800 dark:text-zinc-100">
                        {{ number_format($amount) }} <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') ?? 'ریال' }}</span>
                    </flux:text>
                </flux:card>
            @endforeach
        </div>

        <flux:card class="bg-zinc-50 dark:bg-zinc-900 border-t-4 border-zinc-500">
            <div class="flex justify-between items-center">
                <flux:heading size="lg">{{ __('app.total_annual_invoices') }}</flux:heading>
                <flux:text size="2xl" class="font-black text-zinc-900 dark:text-white">
                    {{ number_format($this->invoiceStats['total']) }} <span class="text-lg font-bold">{{ __('app.rial') ?? 'ریال' }}</span>
                </flux:text>
            </div>
        </flux:card>
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
            @can('administrator_access')
                <flux:table.column>{{ __('app.usdt_rate') }}</flux:table.column>
            @endcan
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
                @can('administrator_access')
                    <flux:table.cell class="whitespace-nowrap">
                        {{ number_format(\App\Models\CurrencyRate::getRate($invoice->Date)) }}
                    </flux:table.cell>
                @endcan
                <flux:table.cell class="whitespace-nowrap">
                    {{ $invoice->Date ? \Morilog\Jalali\Jalalian::fromDateTime($invoice->Date)->format('%Y-%m-%d') : '-' }}
                </flux:table.cell>

            </flux:table.row>
        @endforeach
    </flux:table>
</div>
