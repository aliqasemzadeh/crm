<?php

use App\Models\Sepidar\FMK\User as SepidarUser;
use App\Models\Sepidar\SLS\Invoice;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

new #[Layout('layouts.panels.accounting')] class extends Component
{
    use WithPagination;

    public SepidarUser $sepidarUser;

    public string $search = '';

    #[Url]
    public ?int $selectedMonth = null;

    public string $sortBy = 'Date';

    public string $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'selectedMonth' => ['except' => null],
    ];

    public function mount(SepidarUser $sepidarUser): void
    {
        $this->authorize('accounting_user_report');

        abort_if($sepidarUser->IsDeleted, 404);

        $this->sepidarUser = $sepidarUser;
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

    public function selectMonth(?int $month): void
    {
        $this->selectedMonth = $this->selectedMonth === $month ? null : $month;
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function invoiceStats(): array
    {
        $fiscalYearRef = config('sepidar.FiscalYearRef');
        $userId = $this->sepidarUser->UserID;
        $cacheKey = "accounting_user_invoice_stats_{$userId}_{$fiscalYearRef}";

        return Cache::rememberForever($cacheKey, function () use ($fiscalYearRef, $userId) {
            $invoices = Invoice::query()
                ->where('FiscalYearRef', $fiscalYearRef)
                ->where('Creator', $userId)
                ->select(['Price', 'Date'])
                ->get();

            $monthlyStats = array_fill(1, 12, 0);

            foreach ($invoices as $invoice) {
                if ($invoice->Date) {
                    $month = Jalalian::fromDateTime($invoice->Date)->getMonth();
                    $monthlyStats[$month] += (float) $invoice->Price;
                }
            }

            return [
                'monthly' => $monthlyStats,
                'total' => array_sum($monthlyStats),
                'count' => $invoices->count(),
            ];
        });
    }

    #[Computed]
    public function selectedMonthInvoiceIds(): array
    {
        if (! $this->selectedMonth) {
            return [];
        }

        $fiscalYearRef = config('sepidar.FiscalYearRef');

        return Invoice::query()
            ->where('FiscalYearRef', $fiscalYearRef)
            ->where('Creator', $this->sepidarUser->UserID)
            ->whereNotNull('Date')
            ->get(['InvoiceId', 'Date'])
            ->filter(function (Invoice $invoice) {
                return Jalalian::fromDateTime($invoice->Date)->getMonth() === $this->selectedMonth;
            })
            ->pluck('InvoiceId')
            ->all();
    }

    #[Computed]
    public function invoices()
    {
        $fiscalYearRef = config('sepidar.FiscalYearRef');
        $monthInvoiceIds = $this->selectedMonthInvoiceIds;

        return Invoice::query()
            ->with(['customer'])
            ->where('FiscalYearRef', $fiscalYearRef)
            ->where('Creator', $this->sepidarUser->UserID)
            ->when($this->selectedMonth, function ($query) use ($monthInvoiceIds) {
                $query->whereIn('InvoiceId', $monthInvoiceIds ?: [0]);
            })
            ->when($this->search !== '', function ($query) {
                $search = '%'.$this->search.'%';
                $query->where(function ($inner) use ($search) {
                    $inner->where('CustomerRealName', 'like', $search)
                        ->orWhere('Number', 'like', $search);
                });
            })
            ->tap(function ($query) {
                if ($this->sortBy) {
                    $query->orderBy($this->sortBy, $this->sortDirection);
                }
            })
            ->paginate(100);
    }
};
?>

<x-slot name="title">
    {{ __('app.user_invoices_report') }} - {{ $sepidarUser->Name }}
</x-slot>

<div>
    <div class="relative mb-6 w-full">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.user_invoices_report') }}</flux:heading>
                <flux:subheading size="lg" class="mb-2">
                    {{ $sepidarUser->Name }}
                    @if($sepidarUser->UserName)
                        <span class="text-zinc-500">({{ $sepidarUser->UserName }})</span>
                    @endif
                </flux:subheading>
                <flux:text class="text-zinc-500">{{ __('app.user_invoices_report_description') }}</flux:text>
            </div>

            <flux:button
                variant="primary"
                color="zinc"
                icon="arrow-right"
                href="{{ route('panels.accounting.user.index') }}"
                wire:navigate
            >
                {{ __('app.back_to_users') }}
            </flux:button>
        </div>

        <flux:separator variant="subtle" class="mt-6" />
    </div>

    <div class="relative">
        <div wire:loading.delay.longer wire:target="selectedMonth, search" class="absolute inset-0 bg-white/50 dark:bg-zinc-900/50 z-10 flex items-center justify-center backdrop-blur-sm rounded-xl">
            <flux:icon.loader-circle class="animate-spin text-zinc-500 w-10 h-10" />
        </div>

        <div class="space-y-6 mb-10">
            @php
                $monthly = $this->invoiceStats['monthly'];
                $maxAmount = max($monthly) ?: 0;
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($monthly as $monthNumber => $amount)
                    @php
                        $isSelected = $selectedMonth === $monthNumber;
                        $colorClass = $isSelected
                            ? 'border-sky-500 bg-sky-50/50 dark:bg-sky-900/20 ring-2 ring-sky-500'
                            : ($amount > 0 && $amount == $maxAmount
                                ? 'border-green-500 bg-green-50/50 dark:bg-green-900/20'
                                : 'border-zinc-200 dark:border-zinc-700');
                    @endphp
                    <button
                        type="button"
                        wire:click="selectMonth({{ $monthNumber }})"
                        class="text-start rounded-xl border-t-4 transition-colors {{ $colorClass }}"
                    >
                        <flux:card class="border-0 shadow-none bg-transparent">
                            <flux:heading size="lg" class="mb-2">
                                {{ __('app.jalali_months.' . $monthNumber) }}
                            </flux:heading>
                            <flux:text size="xl" class="font-bold text-zinc-800 dark:text-zinc-100">
                                {{ number_format($amount) }}
                                <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
                            </flux:text>
                        </flux:card>
                    </button>
                @endforeach
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:card class="bg-zinc-50 dark:bg-zinc-900 border-t-4 border-zinc-500">
                    <div class="flex justify-between items-center">
                        <flux:heading size="lg">{{ __('app.total_user_invoices') }}</flux:heading>
                        <flux:text size="2xl" class="font-black text-zinc-900 dark:text-white">
                            {{ number_format($this->invoiceStats['total']) }}
                            <span class="text-lg font-bold">{{ __('app.rial') }}</span>
                        </flux:text>
                    </div>
                </flux:card>

                <flux:card class="bg-zinc-50 dark:bg-zinc-900 border-t-4 border-sky-500">
                    <div class="flex justify-between items-center">
                        <flux:heading size="lg">{{ __('app.invoice_count') }}</flux:heading>
                        <flux:text size="2xl" class="font-black text-zinc-900 dark:text-white">
                            {{ number_format($this->invoiceStats['count']) }}
                        </flux:text>
                    </div>
                </flux:card>
            </div>
        </div>

        @if($selectedMonth)
            <flux:card class="mb-4 flex items-center justify-between gap-4">
                <flux:text>
                    {{ __('app.filtered_by_month') }}:
                    <span class="font-semibold">{{ __('app.jalali_months.' . $selectedMonth) }}</span>
                </flux:text>
                <flux:button size="sm" variant="ghost" wire:click="selectMonth({{ $selectedMonth }})">
                    {{ __('app.clear_filter') }}
                </flux:button>
            </flux:card>
        @endif

        <livewire:panels.accounting.invoice.view />

        <flux:card class="mb-4">
            <flux:input
                wire:model.live.debounce.400ms="search"
                icon="search"
                placeholder="{{ __('app.search_placeholder') }}"
            />
        </flux:card>

        <flux:table :paginate="$this->invoices">
            <flux:table.columns>
                <flux:table.column class="w-1 whitespace-nowrap">{{ __('app.options') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'Number'" :direction="$sortDirection" wire:click="sort('Number')">{{ __('app.number') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'CustomerRealName'" :direction="$sortDirection" wire:click="sort('CustomerRealName')">{{ __('app.name') }}</flux:table.column>
                <flux:table.column>{{ __('app.category') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'Price'" :direction="$sortDirection" wire:click="sort('Price')">{{ __('app.price') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'Date'" :direction="$sortDirection" wire:click="sort('Date')">{{ __('app.date') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->invoices as $invoice)
                    <flux:table.row :key="$invoice->InvoiceId">
                        <flux:table.cell class="w-1 whitespace-nowrap">
                            <flux:button
                                size="xs"
                                variant="primary"
                                color="sky"
                                wire:click="$dispatch('panels.accounting.invoice.view.assign-data', { InvoiceId: '{{ $invoice->InvoiceId }}' })"
                            >
                                {{ __('app.view') }}
                            </flux:button>
                        </flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ $invoice->Number }}</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ $invoice->CustomerRealName }}</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">
                            @if($invoice->SaleTypeRef == 1)
                                {{ __('app.official') }}
                            @else
                                {{ __('app.unofficial') }}
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ number_format($invoice->Price) }}</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">
                            {{ $invoice->Date ? Jalalian::fromDateTime($invoice->Date)->format('%Y-%m-%d') : '-' }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>
</div>
