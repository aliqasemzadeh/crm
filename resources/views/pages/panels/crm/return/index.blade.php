<?php

use App\Livewire\Forms\Crm\CustomerSearchForm;
use App\Models\Sepidar\GNR\Party;
use App\Models\Sepidar\SLS\Invoice;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

return new #[Layout('layouts.panels.crm')] class extends Component
{
    use WithPagination;

    public CustomerSearchForm $form;

    public bool $filtersApplied = false;

    public function mount(): void
    {
        $this->authorize('crm_return_index');
    }

    public function applyFilters(): void
    {
        $this->form->normalize();
        $this->form->validate();

        if (! $this->form->hasActiveFilters()) {
            $this->filtersApplied = false;
            Flux::toast(__('app.customer_search_filters_required'));

            return;
        }

        $this->filtersApplied = true;
        $this->resetPage();
        unset($this->customers);
    }

    public function clearFilters(): void
    {
        $this->form->clear();
        $this->filtersApplied = false;
        $this->resetPage();
        unset($this->customers);
    }

    public function updatingFormSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function customers(): LengthAwarePaginator
    {
        if (! $this->filtersApplied || ! $this->form->hasActiveFilters()) {
            return new LengthAwarePaginator([], 0, 25);
        }

        $dateFrom = $this->parseJalaliDate($this->form->date_from, startOfDay: true);
        $dateTo = $this->parseJalaliDate($this->form->date_to, endOfDay: true);
        $noPurchaseSince = $this->parseJalaliDate($this->form->no_purchase_since, startOfDay: true);

        $rangeAgg = Invoice::query()
            ->selectRaw('CustomerPartyRef, COUNT(*) as invoice_count, SUM(NetPrice) as total_amount')
            ->when($dateFrom, fn ($q) => $q->where('Date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('Date', '<=', $dateTo))
            ->groupBy('CustomerPartyRef')
            ->when($this->form->min_count !== null, fn ($q) => $q->havingRaw('COUNT(*) >= ?', [$this->form->min_count]))
            ->when($this->form->max_count !== null, fn ($q) => $q->havingRaw('COUNT(*) <= ?', [$this->form->max_count]))
            ->when($this->form->min_amount !== null, fn ($q) => $q->havingRaw('SUM(NetPrice) >= ?', [$this->form->min_amount]))
            ->when($this->form->max_amount !== null, fn ($q) => $q->havingRaw('SUM(NetPrice) <= ?', [$this->form->max_amount]));

        $lastPurchase = Invoice::query()
            ->selectRaw('CustomerPartyRef, MAX(Date) as last_purchase_date')
            ->groupBy('CustomerPartyRef');

        $phoneSub = DB::connection('sqlsrv')
            ->table('GNR.PartyPhone')
            ->selectRaw('PartyRef, MAX(Phone) as Phone')
            ->where('IsMain', 1)
            ->groupBy('PartyRef');

        return Party::query()
            ->from('GNR.Party as p')
            ->where('p.IsCustomer', 1)
            ->joinSub($rangeAgg, 'agg', 'agg.CustomerPartyRef', '=', 'p.PartyId')
            ->joinSub($lastPurchase, 'lp', 'lp.CustomerPartyRef', '=', 'p.PartyId')
            ->leftJoinSub($phoneSub, 'ph', 'ph.PartyRef', '=', 'p.PartyId')
            ->when($noPurchaseSince, fn ($q) => $q->where('lp.last_purchase_date', '<', $noPurchaseSince))
            ->when($this->form->search !== '', function ($query) {
                $search = $this->form->search;
                $query->where(function ($q) use ($search) {
                    $q->where('p.Name', 'like', "%{$search}%")
                        ->orWhere('p.LastName', 'like', "%{$search}%")
                        ->orWhere('p.IdentificationCode', 'like', "%{$search}%")
                        ->orWhere('p.EconomicCode', 'like', "%{$search}%")
                        ->orWhere('ph.Phone', 'like', "%{$search}%");
                });
            })
            ->select([
                'p.PartyId',
                'p.Name',
                'p.LastName',
                'p.IdentificationCode',
                'p.EconomicCode',
                'agg.invoice_count',
                'agg.total_amount',
                'lp.last_purchase_date',
                'ph.Phone as main_phone',
            ])
            ->orderByDesc('agg.total_amount')
            ->paginate(25);
    }

    private function parseJalaliDate(?string $date, bool $startOfDay = false, bool $endOfDay = false): ?string
    {
        if ($date === null || trim($date) === '') {
            return null;
        }

        try {
            $carbon = Jalalian::fromFormat('Y/m/d', $date)->toCarbon();

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

<div>
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.customer_search') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.customer_search_description') }}</flux:subheading>
            </div>
        </div>
        <flux:separator variant="subtle" />
    </div>

    <flux:card class="panel-filter-card mb-4">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 mb-3">
            <flux:input
                wire:model="form.search"
                icon="magnifying-glass"
                :label="__('app.search')"
                placeholder="{{ __('app.customer_search_placeholder') }}"
            />

            <x-date-select wire:model="form.date_from" :label="__('app.from_date')" />
            <x-date-select wire:model="form.date_to" :label="__('app.to_date')" />
            <x-date-select wire:model="form.no_purchase_since" :label="__('app.customer_search_no_purchase_since')" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 mb-3">
            <flux:input
                wire:model="form.min_count"
                type="number"
                min="0"
                :label="__('app.customer_search_min_count')"
                placeholder="{{ __('app.customer_search_min_count') }}"
            />
            <flux:input
                wire:model="form.max_count"
                type="number"
                min="0"
                :label="__('app.customer_search_max_count')"
                placeholder="{{ __('app.customer_search_max_count') }}"
            />
            <flux:input
                wire:model="form.min_amount"
                :label="__('app.customer_search_min_amount')"
                placeholder="{{ __('app.customer_search_min_amount') }}"
            />
            <flux:input
                wire:model="form.max_amount"
                :label="__('app.customer_search_max_amount')"
                placeholder="{{ __('app.customer_search_max_amount') }}"
            />
        </div>

        <div class="flex flex-col sm:flex-row gap-2 sm:justify-end">
            <flux:button variant="primary" color="zinc" wire:click="clearFilters" class="w-full sm:w-auto">
                {{ __('app.clear_all_filters') }}
            </flux:button>
            <flux:button variant="primary" color="teal" icon="search" wire:click="applyFilters" class="w-full sm:w-auto">
                {{ __('app.apply') }}
            </flux:button>
        </div>
    </flux:card>

    <div class="relative min-h-[16rem]">
        <div
            wire:loading.delay.shortest
            wire:target="applyFilters,clearFilters,gotoPage,nextPage,previousPage,setPage"
            class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-3 rounded-xl bg-white/80 dark:bg-zinc-900/80 backdrop-blur-sm"
        >
            <flux:icon icon="loading" class="size-9 text-teal-600 dark:text-teal-400" />
            <flux:text class="text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('app.loading') }}</flux:text>
        </div>

        @if (! $filtersApplied)
            <flux:callout variant="secondary" icon="info">
                {{ __('app.customer_search_filters_hint') }}
            </flux:callout>
        @else
            <flux:table :paginate="$this->customers">
                <flux:table.columns>
                    <flux:table.column>{{ __('app.name') }}</flux:table.column>
                    <flux:table.column>{{ __('app.last_name') }}</flux:table.column>
                    <flux:table.column>{{ __('app.identification_code') }}</flux:table.column>
                    <flux:table.column>{{ __('app.phone') }}</flux:table.column>
                    <flux:table.column>{{ __('app.invoice_count') }}</flux:table.column>
                    <flux:table.column>{{ __('app.customer_search_total_amount') }}</flux:table.column>
                    <flux:table.column>{{ __('app.customer_search_last_purchase') }}</flux:table.column>
                </flux:table.columns>

                @forelse ($this->customers as $customer)
                    <flux:table.row :key="$customer->PartyId">
                        <flux:table.cell class="whitespace-nowrap">{{ $customer->Name }}</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ $customer->LastName }}</flux:table.cell>
                        <flux:table.cell>{{ $customer->IdentificationCode ?: '—' }}</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap tabular-nums">{{ $customer->main_phone ?: '—' }}</flux:table.cell>
                        <flux:table.cell class="tabular-nums">{{ number_format((int) $customer->invoice_count) }}</flux:table.cell>
                        <flux:table.cell class="tabular-nums whitespace-nowrap">{{ number_format((float) $customer->total_amount) }}</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">
                            {{ $customer->last_purchase_date
                                ? Jalalian::fromDateTime($customer->last_purchase_date)->format('Y/m/d')
                                : '—' }}
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center text-zinc-500">
                            {{ __('app.customer_search_empty') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table>
        @endif
    </div>
</div>
