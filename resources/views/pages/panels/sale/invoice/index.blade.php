<?php

use App\Models\Sepidar\SLS\Invoice;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

new #[Layout('layouts.panels.sale')] class extends Component
{
    use WithPagination;

    public string $sortBy = 'CreationDate';

    public string $sortDirection = 'desc';

    public string $search = '';

    #[Url]
    public string $saleType = 'all';

    #[Url]
    public string $invoiceStatus = 'all';

    public string $invoiceNumber = '';

    public string $dateFrom = '';

    public string $dateTo = '';

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

    public function updatedSaleType(): void
    {
        $this->resetPage();
    }

    public function updatedInvoiceStatus(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedInvoiceNumber(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->invoiceNumber = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->invoiceStatus = 'all';
        $this->resetPage();
    }

    #[Computed]
    public function invoices()
    {
        $dateFrom = $this->parseJalaliDate($this->dateFrom, startOfDay: true);
        $dateTo = $this->parseJalaliDate($this->dateTo, endOfDay: true);

        return Invoice::query()
            ->with(['creator'])
            ->when($this->saleType !== 'all', function ($query) {
                if ($this->saleType === 'official') {
                    $query->where('SaleTypeRef', 1);
                } else {
                    $query->where('SaleTypeRef', '!=', 1);
                }
            })
            ->when($this->invoiceStatus !== 'all', function ($query) {
                if ($this->invoiceStatus === 'registered') {
                    $query->where('State', '<>', 2);
                } elseif ($this->invoiceStatus === 'canceled') {
                    $query->where('State', 2);
                }
            })
            ->when(trim($this->invoiceNumber) !== '', function ($query) {
                $query->where('Number', 'like', '%'.trim($this->invoiceNumber).'%');
            })
            ->when($dateFrom, fn ($query) => $query->where('Date', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->where('Date', '<=', $dateTo))
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
            ->paginate(20);
    }

    private function parseJalaliDate(?string $date, bool $startOfDay = false, bool $endOfDay = false): ?string
    {
        if ($date === null || trim($date) === '') {
            return null;
        }

        try {
            $carbon = Jalalian::fromFormat('Y/m/d', trim(str_replace('-', '/', $date)))->toCarbon();

            if ($startOfDay) {
                $carbon->startOfDay();
            }

            if ($endOfDay) {
                $carbon->endOfDay();
            }

            return $carbon->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
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
                <div wire:loading wire:target="saleType,invoiceStatus,search,invoiceNumber,dateFrom,dateTo">
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

    <flux:card class="panel-filter-card mb-4">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3 items-end">
            <flux:input
                wire:model.live.debounce.500ms="search"
                icon="magnifying-glass"
                :label="__('app.search')"
                placeholder="{{ __('app.search_placeholder') }}"
            />

            <flux:input
                wire:model.live.debounce.500ms="invoiceNumber"
                icon="hash"
                :label="__('app.invoice_number')"
                placeholder="{{ __('app.invoice_number') }}"
            />

            <flux:select wire:model.live="invoiceStatus" :label="__('app.invoice_status')" searchable>
                <flux:select.option value="all">{{ __('app.all') }}</flux:select.option>
                <flux:select.option value="registered">{{ __('app.registered') }}</flux:select.option>
                <flux:select.option value="canceled">{{ __('app.canceled') }}</flux:select.option>
            </flux:select>

            <x-date-select wire:model.live="dateFrom" :label="__('app.from_date')" />
            <x-date-select wire:model.live="dateTo" :label="__('app.to_date')" />
        </div>

        <div class="mt-3 flex justify-end">
            <flux:button variant="primary" color="zinc" wire:click="clearFilters" class="w-full sm:w-auto">
                {{ __('app.clear_all_filters') }}
            </flux:button>
        </div>
    </flux:card>

    <div class="relative">
        <div wire:loading.delay.longer wire:target="saleType,invoiceStatus,search,invoiceNumber,dateFrom,dateTo" class="absolute inset-0 bg-white/50 dark:bg-zinc-900/50 z-10 flex items-center justify-center backdrop-blur-sm rounded-xl">
            <flux:icon.loader-circle class="animate-spin text-zinc-500 w-10 h-10" />
        </div>

        <flux:table :paginate="$this->invoices">
            <flux:table.columns>
                <flux:table.column class="w-1 whitespace-nowrap">{{ __('app.options') }}</flux:table.column>
                <flux:table.column>{{ __('app.number') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'CustomerRealName'" :direction="$sortDirection" wire:click="sort('CustomerRealName')">{{ __('app.name') }}</flux:table.column>
                <flux:table.column>{{ __('app.creator') }}</flux:table.column>
                <flux:table.column>{{ __('app.category') }}</flux:table.column>
                <flux:table.column>{{ __('app.invoice_status') }}</flux:table.column>
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
                    <flux:table.cell class="whitespace-nowrap">
                        @if ((int) $invoice->State === 2)
                            <flux:badge color="red" size="sm">{{ __('app.canceled') }}</flux:badge>
                        @else
                            <flux:badge color="emerald" size="sm">{{ __('app.registered') }}</flux:badge>
                        @endif
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
