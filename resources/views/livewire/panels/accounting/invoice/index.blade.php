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

            <div class="flex items-center gap-4">
                <div wire:loading wire:target="saleType">
                    <flux:icon.loader-circle class="animate-spin text-zinc-400" />
                </div>

                @can('accounting_invoice_create')
                    <flux:button
                        size="sm"
                        variant="primary"
                        color="orange"
                        icon="plus"
                        icon:variant="outline"
                        href="{{ route('panels.accounting.invoice.create') }}"
                        wire:navigate
                    >
                        <span class="max-md:hidden">{{ __('app.create_invoice') }}</span>
                    </flux:button>
                @endcan

                <flux:tabs variant="segmented" class="-my-px h-auto! max-md:hidden" wire:model.live="saleType">
                    <flux:tab name="all">{{ __('app.all') }}</flux:tab>
                    <flux:tab name="official">{{ __('app.official') }}</flux:tab>
                    <flux:tab name="unofficial">{{ __('app.unofficial') }}</flux:tab>
                </flux:tabs>
            </div>
        </div>

        <flux:separator variant="subtle" />
    </div>

    <div class="relative">
        <div wire:loading.delay.longer wire:target="saleType, search" class="absolute inset-0 bg-white/50 dark:bg-zinc-900/50 z-10 flex items-center justify-center backdrop-blur-sm rounded-xl">
            <flux:icon.loader-circle class="animate-spin text-zinc-500 w-10 h-10" />
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
                <flux:card
                    wire:click="showMonthDetail({{ $monthNumber }})"
                    wire:loading.class="pointer-events-none opacity-70"
                    wire:target="showMonthDetail({{ $monthNumber }})"
                    class="flex flex-col items-center justify-center p-6 border-t-4 cursor-pointer transition hover:shadow-md {{ $colorClass }}"
                >
                    <div class="relative mb-2 flex min-h-7 w-full items-center justify-center">
                        <flux:heading size="lg" wire:loading.remove wire:target="showMonthDetail({{ $monthNumber }})">
                            {{ __('app.jalali_months.' . $monthNumber) }}
                        </flux:heading>
                        <div wire:loading wire:target="showMonthDetail({{ $monthNumber }})" class="flex items-center justify-center">
                            <flux:icon.loader-circle class="animate-spin size-5 text-zinc-500" />
                        </div>
                    </div>
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

    <flux:modal name="panels.accounting.invoice.month-stats.modal" class="md:w-96" flyout position="right">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ __('app.invoice_month_detail', ['month' => $selectedMonth ? __('app.jalali_months.'.$selectedMonth) : '']) }}
                </flux:heading>
                <flux:text class="mt-2">{{ __('app.invoice_month_detail_description') }}</flux:text>
            </div>

            @if($selectedMonth)
                <livewire:panels.accounting.invoice.month-stats
                    :month="$selectedMonth"
                    :sale-type="$saleType"
                    :key="'invoice-month-stats-'.$selectedMonth.'-'.$saleType"
                    lazy="on-load"
                />
            @endif
        </div>
    </flux:modal>

    <livewire:panels.accounting.grouping.item.invoice />
    <livewire:panels.accounting.grouping.item.receipt />
    <livewire:panels.accounting.invoice.view />
    <livewire:panels.accounting.inventory-receipt.view />


    <div class="flex items-center justify-between gap-4 mb-4">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.500ms="search" icon="search" placeholder="{{ __('app.search_placeholder') }}" />
        </div>
    </div>

    <flux:table :paginate="$this->invoices">
        <flux:table.columns>
            <flux:table.column class="w-1 whitespace-nowrap">{{ __('app.options') }}</flux:table.column>
            <flux:table.column>{{ __('app.number') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'CustomerRealName'" :direction="$sortDirection" wire:click="sort('CustomerRealName')">{{ __('app.name') }}</flux:table.column>
            <flux:table.column>{{ __('app.creator') }}</flux:table.column>
            <flux:table.column>{{ __('app.category') }}</flux:table.column>
            <flux:table.column>{{ __('app.price') }}</flux:table.column>
            @can('accounting_profit_index')
                <flux:table.column>{{ __('app.price') }} (تتر)</flux:table.column>
                <flux:table.column>{{ __('app.usdt_rate') }}</flux:table.column>
            @endcan
            <flux:table.column sortable :sorted="$sortBy === 'Date'" :direction="$sortDirection" wire:click="sort('Date')">{{ __('app.date') }}</flux:table.column>

        </flux:table.columns>

        @foreach ($this->invoices as $invoice)
            <flux:table.row :key="$invoice->id">
                <flux:table.cell class="w-1 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                        <flux:button size="xs" variant="primary" color="sky" wire:click="$dispatch('panels.accounting.invoice.view.assign-data', { InvoiceId: '{{ $invoice->InvoiceId }}' })">{{ __('app.view') }}</flux:button>
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
                    {{ $invoice->creator?->Name ?? '-' }}
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
                @can('accounting_profit_index')
                    @php
                        $rate = \App\Models\CurrencyRate::getRate($invoice->Date);
                    @endphp
                    <flux:table.cell class="whitespace-nowrap">
                        {{ $rate > 0 ? number_format($invoice->Price / ($rate / 10), 2) : '-' }}
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">
                        {{ number_format($rate) }}
                    </flux:table.cell>
                @endcan
                <flux:table.cell class="whitespace-nowrap">
                    {{ $invoice->Date ? \Morilog\Jalali\Jalalian::fromDateTime($invoice->Date)->format('%Y-%m-%d') : '-' }}
                </flux:table.cell>

            </flux:table.row>
        @endforeach
    </flux:table>
    </div>
</div>
