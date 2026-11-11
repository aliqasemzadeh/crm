<?php

use App\Models\Sepidar\FMK\FiscalYear;
use App\Services\Accounting\SalesExpertPerformanceService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

new #[Layout('layouts.panels.sale')] class extends Component
{
    #[Url]
    public int|string $fiscalYearRef = '';

    #[Url]
    public int $selectedMonth = 0;

    public int $detailMonth = 0;

    public function mount(): void
    {
        $this->authorize('sales_dashboard_index');

        if ($this->fiscalYearRef === '' || $this->fiscalYearRef === null) {
            $this->fiscalYearRef = (int) config('sepidar.FiscalYearRef');
        }

        if (! request()->has('selectedMonth') && $this->selectedMonth === 0) {
            $this->selectedMonth = (int) Jalalian::now()->getMonth();
        }
    }

    public function updatedFiscalYearRef(): void
    {
        unset(
            $this->fiscalYears,
            $this->performanceMetrics,
            $this->monthlyTrend,
            $this->monthDetailMetrics,
            $this->monthDetailChart
        );
    }

    public function updatedSelectedMonth(): void
    {
        $this->selectedMonth = max(0, min(12, (int) $this->selectedMonth));
        unset($this->performanceMetrics);
    }

    protected function monthFilter(): ?int
    {
        return $this->selectedMonth > 0 ? $this->selectedMonth : null;
    }

    protected function creatorId(): int
    {
        return (int) auth()->user()->resolveSepidarCreatorId();
    }

    public function openMonthDetails(int $month): void
    {
        $this->detailMonth = max(1, min(12, $month));
        unset($this->monthDetailMetrics, $this->monthDetailChart);
        Flux::modal('panels.sale.dashboard.month-details.modal')->show();
    }

    public function closeMonthDetails(): void
    {
        Flux::modal('panels.sale.dashboard.month-details.modal')->close();
        $this->detailMonth = 0;
        unset($this->monthDetailMetrics, $this->monthDetailChart);
    }

    #[Computed]
    public function fiscalYears()
    {
        return FiscalYear::query()->orderByDesc('FiscalYearID')->get();
    }

    #[Computed]
    public function performanceMetrics(): array
    {
        $creatorId = $this->creatorId();
        $metrics = app(SalesExpertPerformanceService::class)->metricsForCreators(
            [$creatorId],
            (int) $this->fiscalYearRef,
            $this->monthFilter()
        );

        return $metrics[$creatorId] ?? [
            'customer_count' => 0,
            'invoice_count' => 0,
            'sold_quantity' => 0,
            'new_customer_count' => 0,
            'profit' => null,
            'proforma_count' => null,
            'lead_conversion_rate' => null,
            'buyer_type_frequency' => null,
        ];
    }

    #[Computed]
    public function monthlyTrend(): array
    {
        return app(SalesExpertPerformanceService::class)->monthlyTrend(
            $this->creatorId(),
            (int) $this->fiscalYearRef
        );
    }

    #[Computed]
    public function monthDetailMetrics(): array
    {
        if ($this->detailMonth < 1) {
            return [
                'customer_count' => 0,
                'invoice_count' => 0,
                'sold_quantity' => 0,
                'new_customer_count' => 0,
                'profit' => null,
                'proforma_count' => null,
                'lead_conversion_rate' => null,
                'buyer_type_frequency' => null,
            ];
        }

        $creatorId = $this->creatorId();
        $metrics = app(SalesExpertPerformanceService::class)->metricsForCreators(
            [$creatorId],
            (int) $this->fiscalYearRef,
            $this->detailMonth
        );

        return $metrics[$creatorId] ?? [
            'customer_count' => 0,
            'invoice_count' => 0,
            'sold_quantity' => 0,
            'new_customer_count' => 0,
            'profit' => null,
            'proforma_count' => null,
            'lead_conversion_rate' => null,
            'buyer_type_frequency' => null,
        ];
    }

    /**
     * @return list<array{label: string, value: int|float}>
     */
    #[Computed]
    public function monthDetailChart(): array
    {
        $kpi = $this->monthDetailMetrics;

        return [
            [
                'label' => __('app.sales_expert_invoice_count'),
                'value' => (int) ($kpi['invoice_count'] ?? 0),
            ],
            [
                'label' => __('app.sales_expert_new_customers'),
                'value' => (int) ($kpi['new_customer_count'] ?? 0),
            ],
            [
                'label' => __('app.sales_expert_sold_quantity'),
                'value' => (float) ($kpi['sold_quantity'] ?? 0),
            ],
        ];
    }
};
?>

<x-slot name="title">
    {{ __('app.sales') }} — {{ __('app.dashboard') }}
</x-slot>

<div>
    <div class="relative mb-6 w-full">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.sales_expert_cartable') }}</flux:heading>
                <flux:subheading size="lg" class="mb-2">{{ __('app.sales_dashboard_description') }}</flux:subheading>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <flux:select wire:model.live="selectedMonth" class="w-40">
                    <flux:select.option value="0">{{ __('app.whole_year') }}</flux:select.option>
                    @for($i = 1; $i <= 12; $i++)
                        <flux:select.option value="{{ $i }}">{{ __('app.jalali_months.'.$i) }}</flux:select.option>
                    @endfor
                </flux:select>

                <flux:select wire:model.live="fiscalYearRef" class="w-48">
                    @foreach($this->fiscalYears as $year)
                        <flux:select.option value="{{ $year->FiscalYearID }}">{{ $year->Title }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        <flux:separator variant="subtle" class="mt-4" />
    </div>

    @php $kpi = $this->performanceMetrics; @endphp

    <div class="mb-6" wire:loading.class="opacity-60" wire:target="fiscalYearRef,selectedMonth">
        <flux:heading size="lg" class="mb-4">{{ __('app.sales_expert_performance_kpis') }}</flux:heading>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <flux:card class="border-t-4 border-indigo-500">
                <flux:text>{{ __('app.sales_expert_new_customers') }}</flux:text>
                <flux:heading size="xl" class="mt-2 tabular-nums">{{ number_format($kpi['new_customer_count']) }}</flux:heading>
            </flux:card>

            <flux:card class="border-t-4 border-zinc-400">
                <flux:text>{{ __('app.sales_expert_proforma_count') }}</flux:text>
                <flux:heading size="xl" class="mt-2">
                    <flux:badge color="zinc" size="sm">{{ __('app.metric_unavailable') }}</flux:badge>
                </flux:heading>
            </flux:card>

            <flux:card class="border-t-4 border-teal-500">
                <flux:text>{{ __('app.sales_expert_invoice_count') }}</flux:text>
                <flux:heading size="xl" class="mt-2 tabular-nums">{{ number_format($kpi['invoice_count']) }}</flux:heading>
            </flux:card>

            <flux:card class="border-t-4 border-emerald-500">
                <flux:text>{{ __('app.sales_expert_sold_quantity') }}</flux:text>
                <flux:heading size="xl" class="mt-2 tabular-nums">{{ number_format($kpi['sold_quantity']) }}</flux:heading>
            </flux:card>

            <flux:card class="border-t-4 border-zinc-400">
                <flux:text>{{ __('app.sales_expert_buyer_type_frequency') }}</flux:text>
                <flux:heading size="xl" class="mt-2">
                    <flux:badge color="zinc" size="sm">{{ __('app.metric_unavailable') }}</flux:badge>
                </flux:heading>
            </flux:card>

            <flux:card class="border-t-4 border-zinc-400">
                <flux:text>{{ __('app.sales_expert_progress_vs_previous') }}</flux:text>
                <flux:heading size="xl" class="mt-2">
                    <flux:badge color="zinc" size="sm">{{ __('app.metric_unavailable') }}</flux:badge>
                </flux:heading>
            </flux:card>

            <flux:card class="border-t-4 border-zinc-400">
                <flux:text>{{ __('app.sales_expert_sales_conversion') }}</flux:text>
                <flux:heading size="xl" class="mt-2">
                    <flux:badge color="zinc" size="sm">{{ __('app.metric_unavailable') }}</flux:badge>
                </flux:heading>
            </flux:card>
        </div>
    </div>

    <flux:card class="mb-6" wire:loading.class="opacity-60" wire:target="fiscalYearRef">
        <flux:heading size="lg" class="mb-4">{{ __('app.sales_expert_monthly_trend_chart') }}</flux:heading>

        <flux:chart :value="$this->monthlyTrend" class="h-80">
            <flux:chart.viewport class="min-h-[20rem]">
                <flux:chart.svg>
                    <flux:chart.group>
                        <flux:chart.bar field="invoices" class="text-sky-500" radius="4" />
                        <flux:chart.bar field="new_customers" class="text-indigo-500" radius="4" />
                    </flux:chart.group>

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

            <div class="flex justify-center gap-4 pt-4">
                <flux:chart.legend :label="__('app.sales_expert_invoice_count')">
                    <flux:chart.legend.indicator class="bg-sky-500" />
                </flux:chart.legend>
                <flux:chart.legend :label="__('app.sales_expert_new_customers')">
                    <flux:chart.legend.indicator class="bg-indigo-500" />
                </flux:chart.legend>
            </div>

            <flux:chart.tooltip>
                <flux:chart.tooltip.heading field="month" />
                <flux:chart.tooltip.value field="invoices" :label="__('app.sales_expert_invoice_count')" :format="['useGrouping' => true]" />
                <flux:chart.tooltip.value field="new_customers" :label="__('app.sales_expert_new_customers')" :format="['useGrouping' => true]" />
            </flux:chart.tooltip>
        </flux:chart>
    </flux:card>

    <div class="mb-8" wire:loading.class="opacity-60" wire:target="fiscalYearRef">
        <flux:heading size="lg" class="mb-4">{{ __('app.sales_expert_months_overview') }}</flux:heading>

        @php
            $trend = $this->monthlyTrend;
            $maxInvoices = max(array_column($trend, 'invoices') ?: [0]);
        @endphp

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
            @foreach($trend as $index => $row)
                @php
                    $monthNumber = $index + 1;
                    $invoices = (int) ($row['invoices'] ?? 0);
                    $newCustomers = (int) ($row['new_customers'] ?? 0);
                    $isPeak = $invoices > 0 && $invoices === $maxInvoices;
                    $colorClass = $isPeak
                        ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-900/20'
                        : 'border-zinc-200 dark:border-zinc-700';
                @endphp
                <button
                    type="button"
                    wire:click="openMonthDetails({{ $monthNumber }})"
                    class="text-start rounded-xl border-t-4 transition-colors {{ $colorClass }} hover:ring-2 hover:ring-sky-400"
                >
                    <flux:card class="border-0 shadow-none bg-transparent">
                        <flux:heading size="lg" class="mb-2">
                            {{ $row['month'] }}
                        </flux:heading>
                        <flux:text class="tabular-nums">
                            {{ __('app.sales_expert_invoice_count') }}:
                            <span class="font-semibold text-zinc-800 dark:text-zinc-100">{{ number_format($invoices) }}</span>
                        </flux:text>
                        <flux:text class="mt-1 tabular-nums">
                            {{ __('app.sales_expert_new_customers') }}:
                            <span class="font-semibold text-zinc-800 dark:text-zinc-100">{{ number_format($newCustomers) }}</span>
                        </flux:text>
                    </flux:card>
                </button>
            @endforeach
        </div>
    </div>

    <flux:heading size="lg" class="mb-4">{{ __('app.sales_quick_access') }}</flux:heading>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
        @can('sales_invoice_index')
            <flux:card class="space-y-3">
                <flux:heading size="lg">{{ __('app.invoices') }}</flux:heading>
                <flux:text>{{ __('app.invoices_description') }}</flux:text>
                <flux:button variant="primary" color="orange" href="{{ route('panels.sale.invoice.index') }}" wire:navigate class="w-full">
                    {{ __('app.invoices') }}
                </flux:button>
            </flux:card>
        @endcan

        @can('sales_item_index')
            <flux:card class="space-y-3">
                <flux:heading size="lg">{{ __('app.items') }}</flux:heading>
                <flux:text>{{ __('app.sales_items_description') }}</flux:text>
                <flux:button variant="primary" color="teal" href="{{ route('panels.sale.item.index') }}" wire:navigate class="w-full">
                    {{ __('app.items') }}
                </flux:button>
            </flux:card>
        @endcan

        @can('sales_item_price_index')
            <flux:card class="space-y-3">
                <flux:heading size="lg">{{ __('app.price_notes') }}</flux:heading>
                <flux:text>{{ __('app.sales_item_price_description') }}</flux:text>
                <flux:button variant="primary" color="sky" href="{{ route('panels.sale.item-price.index') }}" wire:navigate class="w-full">
                    {{ __('app.price_notes') }}
                </flux:button>
            </flux:card>
        @endcan

        @can('sales_item_cluster_index')
            <flux:card class="space-y-3">
                <flux:heading size="lg">{{ __('app.item_clusters') }}</flux:heading>
                <flux:text>{{ __('app.sales_item_cluster_description') }}</flux:text>
                <flux:button variant="primary" color="lime" href="{{ route('panels.sale.item-cluster.index') }}" wire:navigate class="w-full">
                    {{ __('app.item_clusters') }}
                </flux:button>
            </flux:card>
        @endcan

        @can('sales_quick_pricing_index')
            <flux:card class="space-y-3">
                <flux:heading size="lg">{{ __('app.quick_pricing') }}</flux:heading>
                <flux:text>{{ __('app.sales_quick_pricing_description') }}</flux:text>
                <flux:button variant="primary" color="amber" href="{{ route('panels.sale.quick-pricing.index') }}" wire:navigate class="w-full">
                    {{ __('app.quick_pricing') }}
                </flux:button>
            </flux:card>
        @endcan

        @can('sales_party_index')
            <flux:card class="space-y-3">
                <flux:heading size="lg">{{ __('app.customers') }}</flux:heading>
                <flux:text>{{ __('app.sales_customers_description') }}</flux:text>
                <flux:button variant="primary" color="violet" href="{{ route('panels.sale.party.index') }}" wire:navigate class="w-full">
                    {{ __('app.customers') }}
                </flux:button>
            </flux:card>
        @endcan
    </div>

    <flux:modal
        name="panels.sale.dashboard.month-details.modal"
        class="md:w-2/3 lg:w-1/2"
        flyout
        position="right"
    >
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ __('app.sales_expert_month_details') }}
                    @if($detailMonth > 0)
                        — {{ __('app.jalali_months.'.$detailMonth) }}
                    @endif
                </flux:heading>
                <flux:text class="text-zinc-500">{{ __('app.sales_expert_month_details_description') }}</flux:text>
            </div>

            @php $detailKpi = $this->monthDetailMetrics; @endphp

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:card class="border-t-4 border-indigo-500">
                    <flux:text>{{ __('app.sales_expert_new_customers') }}</flux:text>
                    <flux:heading size="xl" class="mt-2 tabular-nums">{{ number_format($detailKpi['new_customer_count']) }}</flux:heading>
                </flux:card>

                <flux:card class="border-t-4 border-teal-500">
                    <flux:text>{{ __('app.sales_expert_invoice_count') }}</flux:text>
                    <flux:heading size="xl" class="mt-2 tabular-nums">{{ number_format($detailKpi['invoice_count']) }}</flux:heading>
                </flux:card>

                <flux:card class="border-t-4 border-emerald-500">
                    <flux:text>{{ __('app.sales_expert_sold_quantity') }}</flux:text>
                    <flux:heading size="xl" class="mt-2 tabular-nums">{{ number_format($detailKpi['sold_quantity']) }}</flux:heading>
                </flux:card>

                <flux:card class="border-t-4 border-sky-500">
                    <flux:text>{{ __('app.sales_expert_customer_count') }}</flux:text>
                    <flux:heading size="xl" class="mt-2 tabular-nums">{{ number_format($detailKpi['customer_count']) }}</flux:heading>
                </flux:card>

                <flux:card class="border-t-4 border-zinc-400">
                    <flux:text>{{ __('app.sales_expert_proforma_count') }}</flux:text>
                    <flux:heading size="xl" class="mt-2">
                        <flux:badge color="zinc" size="sm">{{ __('app.metric_unavailable') }}</flux:badge>
                    </flux:heading>
                </flux:card>

                <flux:card class="border-t-4 border-zinc-400">
                    <flux:text>{{ __('app.sales_expert_buyer_type_frequency') }}</flux:text>
                    <flux:heading size="xl" class="mt-2">
                        <flux:badge color="zinc" size="sm">{{ __('app.metric_unavailable') }}</flux:badge>
                    </flux:heading>
                </flux:card>

                <flux:card class="border-t-4 border-zinc-400">
                    <flux:text>{{ __('app.sales_expert_progress_vs_previous') }}</flux:text>
                    <flux:heading size="xl" class="mt-2">
                        <flux:badge color="zinc" size="sm">{{ __('app.metric_unavailable') }}</flux:badge>
                    </flux:heading>
                </flux:card>

                <flux:card class="border-t-4 border-zinc-400">
                    <flux:text>{{ __('app.sales_expert_sales_conversion') }}</flux:text>
                    <flux:heading size="xl" class="mt-2">
                        <flux:badge color="zinc" size="sm">{{ __('app.metric_unavailable') }}</flux:badge>
                    </flux:heading>
                </flux:card>
            </div>

            <flux:card>
                <flux:heading size="lg" class="mb-4">{{ __('app.sales_expert_month_detail_chart') }}</flux:heading>

                <flux:chart :value="$this->monthDetailChart" class="h-64">
                    <flux:chart.viewport class="min-h-[16rem]">
                        <flux:chart.svg>
                            <flux:chart.bar field="value" class="text-orange-500" radius="4" width="70%" />

                            <flux:chart.axis axis="x" field="label">
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
                        <flux:chart.tooltip.heading field="label" />
                        <flux:chart.tooltip.value field="value" :label="__('app.value')" :format="['useGrouping' => true]" />
                    </flux:chart.tooltip>
                </flux:chart>
            </flux:card>

            <flux:button
                variant="primary"
                color="zinc"
                class="w-full"
                wire:click="closeMonthDetails"
            >
                {{ __('app.close') }}
            </flux:button>
        </div>
    </flux:modal>
</div>
