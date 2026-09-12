<?php

use App\Models\Sepidar\FMK\FiscalYear;
use App\Services\Dashboard\AdministratorDashboardService;
use App\Services\Dashboard\BankAccountMonthBalanceService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

new #[Layout('layouts.panels.administrator')] class extends Component
{
    public int|string $fiscalYearRef;

    public int $bankBalanceMonth;

    public function mount(): void
    {
        $this->authorize('administrator_dashboard_index');
        $this->fiscalYearRef = (int) config('sepidar.FiscalYearRef');
        $this->bankBalanceMonth = (int) Jalalian::now()->getMonth();
    }

    public function updatedFiscalYearRef(): void
    {
        unset($this->stats);
        unset($this->bankMonthChart);
    }

    public function updatedBankBalanceMonth(): void
    {
        unset($this->bankMonthChart);
    }

    public function reload(): void
    {
        AdministratorDashboardService::clearCache($this->fiscalYearRef);
        BankAccountMonthBalanceService::clearCache($this->fiscalYearRef);
        app(BankAccountMonthBalanceService::class)->syncFiscalYear((int) $this->fiscalYearRef);

        unset($this->stats);
        unset($this->bankMonthChart);

        Flux::toast(__('app.dashboard_data_reloaded'));
    }

    public function openSalesBreakdown(): void
    {
        Flux::modal('panels.administrator.dashboard.sales-breakdown.modal')->show();
    }

    #[Computed]
    public function stats(): array
    {
        return app(AdministratorDashboardService::class)->stats($this->fiscalYearRef);
    }

    #[Computed]
    public function bankMonthChart(): array
    {
        return app(BankAccountMonthBalanceService::class)
            ->chartRows($this->fiscalYearRef, $this->bankBalanceMonth);
    }

    #[Computed]
    public function fiscalYears()
    {
        return FiscalYear::query()->orderByDesc('FiscalYearId')->get();
    }
};

?>

<x-slot name="title">
    {{ __('app.dashboard') }}
</x-slot>

<div class="space-y-6">
    <div class="relative w-full">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.administrator') }}</flux:heading>
                <flux:subheading size="lg" class="mb-2">{{ __('app.administrator_description') }}</flux:subheading>
            </div>
            <div class="flex items-center gap-2">
                <flux:button wire:click="reload" icon="arrow-path" size="sm" variant="subtle" wire:loading.attr="disabled">
                    {{ __('app.reload') }}
                </flux:button>
                <flux:select wire:model.live="fiscalYearRef" class="w-48">
                    @foreach($this->fiscalYears as $year)
                        <flux:select.option value="{{ $year->FiscalYearId }}">{{ $year->Title }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>
        <flux:separator variant="subtle" class="mt-4" />
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5 md:gap-6" wire:loading.class="opacity-60" wire:target="fiscalYearRef,reload">
        <a href="{{ route('panels.accounting.bank.index') }}" class="block">
            <flux:card class="border-t-4 border-teal-500 h-full transition hover:bg-zinc-50 dark:hover:bg-zinc-800">
                <flux:text>{{ __('app.total_bank_accounts_balance') }}</flux:text>
                <flux:heading size="xl" class="mt-2 tabular-nums">
                    {{ number_format($this->stats['bankAccountsBalance'] ?? 0) }}
                    <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
                </flux:heading>
            </flux:card>
        </a>

        <a href="{{ route('panels.warehouse.item.index') }}" class="block">
            <flux:card class="border-t-4 border-indigo-500 h-full transition hover:bg-zinc-50 dark:hover:bg-zinc-800">
                <flux:text>{{ __('app.inventory_rial_balance') }}</flux:text>
                <flux:heading size="xl" class="mt-2 tabular-nums">
                    {{ number_format($this->stats['inventoryBalance'] ?? 0) }}
                    <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
                </flux:heading>
            </flux:card>
        </a>

        <button type="button" wire:click="openSalesBreakdown" class="text-start w-full">
            <flux:card class="border-t-4 border-emerald-500 h-full transition hover:bg-zinc-50 dark:hover:bg-zinc-800 cursor-pointer">
                <flux:text>{{ __('app.annual_sales') }}</flux:text>
                <flux:heading size="xl" class="mt-2 tabular-nums">
                    {{ number_format($this->stats['annualSalesTotal'] ?? 0) }}
                    <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
                </flux:heading>
                <flux:text size="sm" class="mt-2 text-emerald-600 dark:text-emerald-400">{{ __('app.view_official_unofficial_breakdown') }}</flux:text>
            </flux:card>
        </button>

        <a href="{{ route('panels.accounting.payment-cheque.index') }}" class="block">
            <flux:card class="border-t-4 border-orange-500 h-full transition hover:bg-zinc-50 dark:hover:bg-zinc-800">
                <flux:text>{{ __('app.payable_cheques_balance') }}</flux:text>
                <flux:heading size="xl" class="mt-2 tabular-nums">
                    {{ number_format($this->stats['payableChequesBalance'] ?? 0) }}
                    <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
                </flux:heading>
            </flux:card>
        </a>

        <a href="{{ route('panels.accounting.receipt-cheque.index') }}" class="block">
            <flux:card class="border-t-4 border-sky-500 h-full transition hover:bg-zinc-50 dark:hover:bg-zinc-800">
                <flux:text>{{ __('app.receivable_cheques_balance') }}</flux:text>
                <flux:heading size="xl" class="mt-2 tabular-nums">
                    {{ number_format($this->stats['receivableChequesBalance'] ?? 0) }}
                    <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
                </flux:heading>
            </flux:card>
        </a>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <flux:card>
            <div class="mb-4 flex items-center justify-between gap-3">
                <flux:heading size="lg">{{ __('app.sales_by_month') }}</flux:heading>
                <a href="{{ route('panels.accounting.invoice.index') }}" class="text-sm text-teal-600 hover:underline dark:text-teal-400">
                    {{ __('app.invoices') }}
                </a>
            </div>

            <flux:chart :value="$this->stats['salesChart'] ?? []" class="h-80">
                <flux:chart.viewport class="min-h-[20rem]">
                    <flux:chart.svg>
                        <flux:chart.bar field="sales" class="text-teal-500" radius="4" width="70%" />

                        <flux:chart.axis axis="x" field="month">
                            <flux:chart.axis.tick />
                        </flux:chart.axis>

                        <flux:chart.axis axis="y" :format="['useGrouping' => true]">
                            <flux:chart.axis.grid />
                            <flux:chart.axis.tick />
                        </flux:chart.axis>

                        <flux:chart.cursor type="area" />
                    </flux:chart.svg>
                </flux:chart.viewport>

                <flux:chart.tooltip>
                    <flux:chart.tooltip.heading field="month" />
                    <flux:chart.tooltip.value field="sales" :label="__('app.sales')" :format="['useGrouping' => true]" />
                </flux:chart.tooltip>
            </flux:chart>
        </flux:card>

        <flux:card>
            <flux:heading size="lg" class="mb-4">{{ __('app.receipts_vs_expenses') }}</flux:heading>

            <flux:chart :value="$this->stats['receiptsPaymentsChart'] ?? []" class="h-80">
                <flux:chart.viewport class="min-h-[20rem]">
                    <flux:chart.svg>
                        <flux:chart.line field="receipts" class="text-green-500" curve="none" />
                        <flux:chart.point field="receipts" class="text-green-500" r="4" stroke-width="2" />

                        <flux:chart.line field="expenses" class="text-red-500" curve="none" />
                        <flux:chart.point field="expenses" class="text-red-500" r="4" stroke-width="2" />

                        <flux:chart.axis axis="x" field="month">
                            <flux:chart.axis.tick />
                        </flux:chart.axis>

                        <flux:chart.axis axis="y" :format="['useGrouping' => true]">
                            <flux:chart.axis.grid />
                            <flux:chart.axis.tick />
                        </flux:chart.axis>

                        <flux:chart.cursor type="area" />
                    </flux:chart.svg>
                </flux:chart.viewport>

                <div class="flex justify-center gap-6 pt-4">
                    <flux:chart.legend :label="__('app.receipts')">
                        <flux:chart.legend.indicator class="bg-green-500" />
                    </flux:chart.legend>
                    <flux:chart.legend :label="__('app.expenses')">
                        <flux:chart.legend.indicator class="bg-red-500" />
                    </flux:chart.legend>
                </div>

                <flux:chart.tooltip>
                    <flux:chart.tooltip.heading field="month" />
                    <flux:chart.tooltip.value field="receipts" :label="__('app.receipts')" :format="['useGrouping' => true]" />
                    <flux:chart.tooltip.value field="expenses" :label="__('app.expenses')" :format="['useGrouping' => true]" />
                </flux:chart.tooltip>
            </flux:chart>
        </flux:card>
    </div>

    <flux:card>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <flux:heading size="lg">{{ __('app.bank_month_end_balances') }}</flux:heading>
                <flux:text size="sm" class="mt-1">{{ __('app.bank_month_end_balances_description') }}</flux:text>
            </div>
            <div class="flex items-center gap-2">
                <flux:select wire:model.live="bankBalanceMonth" class="w-44">
                    @foreach(range(1, 12) as $month)
                        <flux:select.option value="{{ $month }}">{{ __('app.jalali_months.'.$month) }}</flux:select.option>
                    @endforeach
                </flux:select>
                <a href="{{ route('panels.accounting.bank.index') }}" class="text-sm text-teal-600 hover:underline dark:text-teal-400">
                    {{ __('app.banks') }}
                </a>
            </div>
        </div>

        @if(count($this->bankMonthChart) > 0)
            <flux:chart :value="$this->bankMonthChart" class="h-96">
                <flux:chart.viewport class="min-h-[24rem]">
                    <flux:chart.svg>
                        <flux:chart.bar field="ending_balance" class="text-violet-500" radius="4" width="75%" />

                        <flux:chart.axis axis="x" field="account">
                            <flux:chart.axis.tick />
                        </flux:chart.axis>

                        <flux:chart.axis axis="y" :format="['useGrouping' => true]">
                            <flux:chart.axis.grid />
                            <flux:chart.axis.tick />
                        </flux:chart.axis>

                        <flux:chart.cursor type="area" />
                    </flux:chart.svg>
                </flux:chart.viewport>

                <flux:chart.tooltip>
                    <flux:chart.tooltip.heading field="account" />
                    <flux:chart.tooltip.value field="ending_balance" :label="__('app.ending_balance')" :format="['useGrouping' => true]" />
                    <flux:chart.tooltip.value field="min_balance" :label="__('app.min_balance')" :format="['useGrouping' => true]" />
                </flux:chart.tooltip>
            </flux:chart>
        @else
            <flux:text class="py-10 text-center">{{ __('app.bank_month_balances_empty') }}</flux:text>
        @endif
    </flux:card>

    <flux:modal name="panels.administrator.dashboard.sales-breakdown.modal" flyout position="right" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('app.annual_sales_breakdown') }}</flux:heading>
                <flux:text class="mt-2">{{ __('app.annual_sales_breakdown_description') }}</flux:text>
            </div>

            <flux:card class="border-t-4 border-emerald-500">
                <flux:text>{{ __('app.annual_sales') }}</flux:text>
                <flux:heading size="lg" class="mt-1 tabular-nums">
                    {{ number_format($this->stats['annualSalesTotal'] ?? 0) }} {{ __('app.rial') }}
                </flux:heading>
            </flux:card>

            <flux:card class="border-t-4 border-blue-500">
                <flux:text>{{ __('app.official') }}</flux:text>
                <flux:heading size="lg" class="mt-1 tabular-nums">
                    {{ number_format($this->stats['annualSalesOfficial'] ?? 0) }} {{ __('app.rial') }}
                </flux:heading>
                @php
                    $total = (float) ($this->stats['annualSalesTotal'] ?? 0);
                    $officialPct = $total > 0 ? (($this->stats['annualSalesOfficial'] ?? 0) / $total) * 100 : 0;
                @endphp
                <flux:text size="sm" class="mt-1">{{ number_format($officialPct, 1) }}%</flux:text>
            </flux:card>

            <flux:card class="border-t-4 border-amber-500">
                <flux:text>{{ __('app.unofficial') }}</flux:text>
                <flux:heading size="lg" class="mt-1 tabular-nums">
                    {{ number_format($this->stats['annualSalesUnofficial'] ?? 0) }} {{ __('app.rial') }}
                </flux:heading>
                @php
                    $unofficialPct = $total > 0 ? (($this->stats['annualSalesUnofficial'] ?? 0) / $total) * 100 : 0;
                @endphp
                <flux:text size="sm" class="mt-1">{{ number_format($unofficialPct, 1) }}%</flux:text>
            </flux:card>

            <flux:button
                href="{{ route('panels.accounting.invoice.index') }}"
                variant="primary"
                color="teal"
                class="w-full"
            >
                {{ __('app.view_invoices') }}
            </flux:button>
        </div>
    </flux:modal>
</div>
