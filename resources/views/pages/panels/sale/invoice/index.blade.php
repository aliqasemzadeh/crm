<?php

use App\Models\Sepidar\SLS\Invoice;
use Flux\Flux;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

new #[Layout('layouts.panels.sale')] class extends Component
{
    use WithPagination;

    public string $sortBy = 'Date';

    public string $sortDirection = 'desc';

    public string $search = '';

    #[Url]
    public string $saleType = 'all';

    public int $selectedMonth = 0;

    public function mount(): void
    {
        $this->authorize('sales_invoice_index');
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

    public function showMonthDetail(int $month): void
    {
        $this->selectedMonth = $month;
        Flux::modal('panels.sale.invoice.month-stats.modal')->show();
    }

    public function updatedSaleType(): void
    {
        unset($this->invoiceStats);
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function invoiceStats()
    {
        $fiscalYearRef = config('sepidar.FiscalYearRef');
        $cacheKey = "sale_invoice_stats_fiscal_year_{$fiscalYearRef}_{$this->saleType}";

        return Cache::rememberForever($cacheKey, function () use ($fiscalYearRef) {
            $invoices = Invoice::where('FiscalYearRef', $fiscalYearRef)
                ->when($this->saleType !== 'all', function ($query) {
                    if ($this->saleType === 'official') {
                        $query->where('SaleTypeRef', 1);
                    } else {
                        $query->where('SaleTypeRef', '!=', 1);
                    }
                })
                ->select('Price', 'Date')
                ->get();

            $monthlyStats = array_fill(1, 12, 0);

            foreach ($invoices as $invoice) {
                if ($invoice->Date) {
                    $jalaliDate = Jalalian::fromDateTime($invoice->Date);
                    $month = $jalaliDate->getMonth();
                    $monthlyStats[$month] += $invoice->Price;
                }
            }

            return [
                'monthly' => $monthlyStats,
                'total' => array_sum($monthlyStats),
            ];
        });
    }

    #[Computed]
    public function invoices()
    {
        return Invoice::query()
            ->with(['creator'])
            ->when($this->saleType !== 'all', function ($query) {
                if ($this->saleType === 'official') {
                    $query->where('SaleTypeRef', 1);
                } else {
                    $query->where('SaleTypeRef', '!=', 1);
                }
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('CustomerRealName', 'like', '%'.$this->search.'%')
                        ->orWhere('Number', 'like', '%'.$this->search.'%');
                });
            })
            ->tap(function ($query) {
                if ($this->sortBy) {
                    $query->orderBy($this->sortBy, $this->sortDirection);
                }
            })
            ->paginate(50);
    }
};
?>

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

                @can('sales_invoice_create')
                    <flux:button
                        size="sm"
                        variant="primary"
                        color="orange"
                        icon="plus"
                        icon:variant="outline"
                        href="{{ route('panels.sale.invoice.create') }}"
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
                $maxAmount = max($monthly) ?: 1;
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

        <flux:modal name="panels.sale.invoice.month-stats.modal" class="md:w-96" flyout position="right">
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
                        :key="'sale-invoice-month-stats-'.$selectedMonth.'-'.$saleType"
                        lazy="on-load"
                    />
                @endif
            </div>
        </flux:modal>

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
                <flux:table.column sortable :sorted="$sortBy === 'Date'" :direction="$sortDirection" wire:click="sort('Date')">{{ __('app.date') }}</flux:table.column>
            </flux:table.columns>

            @foreach ($this->invoices as $invoice)
                <flux:table.row :key="$invoice->InvoiceId">
                    <flux:table.cell class="w-1 whitespace-nowrap">
                        <div class="flex items-center gap-2">
                            @can('sales_invoice_view')
                                <flux:tooltip content="{{ __('app.view') }}">
                                    <flux:button
                                        size="xs"
                                        variant="primary"
                                        color="teal"
                                        icon="eye"
                                        icon:variant="outline"
                                        href="{{ route('panels.sale.invoice.view', $invoice->InvoiceId) }}"
                                        wire:navigate
                                    />
                                </flux:tooltip>
                            @endcan
                            @can('sales_invoice_edit')
                                <flux:tooltip content="{{ __('app.edit') }}">
                                    <flux:button
                                        size="xs"
                                        variant="primary"
                                        color="orange"
                                        icon="pencil"
                                        icon:variant="outline"
                                        href="{{ route('panels.sale.invoice.edit', $invoice->InvoiceId) }}"
                                        wire:navigate
                                    />
                                </flux:tooltip>
                            @endcan
                            @can('sales_invoice_print')
                                <flux:tooltip content="{{ __('app.print') }}">
                                    <flux:button
                                        size="xs"
                                        variant="primary"
                                        color="zinc"
                                        icon="printer"
                                        icon:variant="outline"
                                        href="{{ route('panels.sale.invoice.print', $invoice->InvoiceId) }}"
                                        wire:navigate
                                    />
                                </flux:tooltip>
                            @endcan
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $invoice->Number }}</flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $invoice->CustomerRealName }}</flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $invoice->creator?->Name ?? '-' }}</flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">
                        {{ (int) $invoice->SaleTypeRef === 1 ? __('app.official') : __('app.unofficial') }}
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ number_format($invoice->Price) }}</flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">
                        {{ $invoice->Date ? Jalalian::fromDateTime($invoice->Date)->format('%Y-%m-%d') : '-' }}
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table>
    </div>
</div>
