<?php

use App\Services\Report\ItemYearlySalesService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

new #[Layout('layouts.panels.report')] class extends Component
{
    public int $selectedYear;

    public function mount(): void
    {
        $this->authorize('report_item_index');
        $this->selectedYear = (int) Jalalian::now()->getYear();
    }

    public function reload(): void
    {
        ItemYearlySalesService::clearCache();
        unset($this->yearlySales);
        unset($this->selectedYearChart);
        unset($this->topProducts);

        Flux::toast(__('app.dashboard_data_reloaded'));
    }

    /**
     * @return array{
     *     chart: list<array{year: string, sales: float}>,
     *     total: float,
     *     yearCharts: array<int, array{year: string, total: float, months: list<array{month: string, sales: float}>}>
     * }
     */
    #[Computed]
    public function yearlySales(): array
    {
        return app(ItemYearlySalesService::class)->yearlySales();
    }

    /**
     * @return array{year: string, total: float, months: list<array{month: string, sales: float}>}|null
     */
    #[Computed]
    public function selectedYearChart(): ?array
    {
        return $this->yearlySales['yearCharts'][$this->selectedYear] ?? null;
    }

    /**
     * @return list<int>
     */
    #[Computed]
    public function availableYears(): array
    {
        $years = array_keys($this->yearlySales['yearCharts'] ?? []);

        rsort($years);

        return array_map('intval', $years);
    }

    /**
     * @return list<array{rank: int, product_id: int, name: string, average_price: float, quantity: float, sales: float}>
     */
    #[Computed]
    public function topProducts(): array
    {
        return app(ItemYearlySalesService::class)->topProducts($this->selectedYear);
    }
};
?>

<x-slot name="title">
    {{ __('app.report_item_title') }}
</x-slot>

<div class="space-y-6">
    <div class="relative w-full">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.report_item_title') }}</flux:heading>
                <flux:subheading size="lg" class="mb-2">{{ __('app.report_item_subtitle') }}</flux:subheading>
            </div>
            <div class="flex items-end gap-2">
                <flux:button wire:click="reload" icon="arrow-path" size="sm" variant="subtle" wire:loading.attr="disabled">
                    {{ __('app.reload') }}
                </flux:button>
                <flux:field class="w-48">
                    <flux:label>{{ __('app.item_sales_year') }}</flux:label>
                    <flux:select wire:model.live="selectedYear" searchable>
                        @foreach($this->availableYears as $year)
                            <flux:select.option value="{{ $year }}">{{ $year }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>
        </div>
        <flux:separator variant="subtle" class="mt-4" />
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3" wire:loading.class="opacity-60" wire:target="selectedYear,reload">
        <flux:card class="border-t-4 border-teal-500">
            <flux:text>{{ __('app.item_yearly_sales_total') }}</flux:text>
            <flux:heading size="xl" class="mt-2 tabular-nums">
                {{ number_format($this->yearlySales['total'] ?? 0) }}
                <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
            </flux:heading>
        </flux:card>

        <flux:card class="border-t-4 border-emerald-500">
            <flux:text>{{ __('app.item_sales_chart_year', ['year' => $selectedYear]) }}</flux:text>
            <flux:heading size="xl" class="mt-2 tabular-nums">
                {{ number_format($this->selectedYearChart['total'] ?? 0) }}
                <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
            </flux:heading>
        </flux:card>
    </div>

    <flux:card wire:loading.class="opacity-60" wire:target="selectedYear,reload">
        <flux:heading size="lg" class="mb-4">{{ __('app.item_yearly_sales') }}</flux:heading>

        <flux:chart :value="$this->yearlySales['chart'] ?? []" class="h-80">
            <flux:chart.viewport class="min-h-[20rem]">
                <flux:chart.svg>
                    <flux:chart.bar field="sales" class="text-teal-500" radius="4" width="70%" />

                    <flux:chart.axis axis="x" field="year">
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
                <flux:chart.tooltip.heading field="year" />
                <flux:chart.tooltip.value field="sales" :label="__('app.sales')" :format="['useGrouping' => true]" />
            </flux:chart.tooltip>
        </flux:chart>

        <flux:separator variant="subtle" class="my-6" />

        <flux:heading size="md" class="mb-4">{{ __('app.item_yearly_sales_by_year') }}</flux:heading>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('app.item_sales_year') }}</flux:table.column>
                <flux:table.column>{{ __('app.sales') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach(array_reverse($this->yearlySales['chart'] ?? []) as $row)
                    <flux:table.row wire:key="item-yearly-sales-{{ $row['year'] }}">
                        <flux:table.cell>{{ $row['year'] }}</flux:table.cell>
                        <flux:table.cell class="tabular-nums">
                            {{ number_format($row['sales']) }}
                            <span class="text-sm text-zinc-500">{{ __('app.rial') }}</span>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
                <flux:table.row class="font-semibold">
                    <flux:table.cell>{{ __('app.item_yearly_sales_total') }}</flux:table.cell>
                    <flux:table.cell class="tabular-nums">
                        {{ number_format($this->yearlySales['total'] ?? 0) }}
                        <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
                    </flux:table.cell>
                </flux:table.row>
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <flux:card wire:loading.class="opacity-60" wire:target="selectedYear,reload">
        <flux:heading size="lg" class="mb-4">{{ __('app.item_sales_chart_year', ['year' => $selectedYear]) }}</flux:heading>

        <flux:chart :value="$this->selectedYearChart['months'] ?? []" class="h-80">
            <flux:chart.viewport class="min-h-[20rem]">
                <flux:chart.svg>
                    <flux:chart.bar field="sales" class="text-emerald-500" radius="4" width="70%" />

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

        <flux:separator variant="subtle" class="my-6" />

        <flux:heading size="md" class="mb-4">{{ __('app.item_monthly_sales_breakdown', ['year' => $selectedYear]) }}</flux:heading>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('app.item_sales_month') }}</flux:table.column>
                <flux:table.column>{{ __('app.sales') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach($this->selectedYearChart['months'] ?? [] as $index => $row)
                    <flux:table.row wire:key="item-monthly-sales-{{ $selectedYear }}-{{ $index }}">
                        <flux:table.cell>{{ $row['month'] }}</flux:table.cell>
                        <flux:table.cell class="tabular-nums">
                            {{ number_format($row['sales']) }}
                            <span class="text-sm text-zinc-500">{{ __('app.rial') }}</span>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
                <flux:table.row class="font-semibold">
                    <flux:table.cell>{{ __('app.item_sales_chart_year', ['year' => $selectedYear]) }}</flux:table.cell>
                    <flux:table.cell class="tabular-nums">
                        {{ number_format($this->selectedYearChart['total'] ?? 0) }}
                        <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
                    </flux:table.cell>
                </flux:table.row>
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <flux:card wire:loading.class="opacity-60" wire:target="selectedYear,reload">
        <flux:heading size="lg" class="mb-4">{{ __('app.item_top_products', ['year' => $selectedYear]) }}</flux:heading>

        <flux:table>
            <flux:table.columns>
                <flux:table.column class="w-12">#</flux:table.column>
                <flux:table.column>{{ __('app.product') }}</flux:table.column>
                <flux:table.column>{{ __('app.item_average_price') }}</flux:table.column>
                <flux:table.column>{{ __('app.quantity') }}</flux:table.column>
                <flux:table.column>{{ __('app.sales') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($this->topProducts as $product)
                    <flux:table.row wire:key="item-top-product-{{ $selectedYear }}-{{ $product['product_id'] }}">
                        <flux:table.cell class="tabular-nums">{{ $product['rank'] }}</flux:table.cell>
                        <flux:table.cell>{{ $product['name'] }}</flux:table.cell>
                        <flux:table.cell class="tabular-nums">
                            {{ number_format($product['average_price']) }}
                            <span class="text-sm text-zinc-500">{{ __('app.rial') }}</span>
                        </flux:table.cell>
                        <flux:table.cell class="tabular-nums">{{ number_format($product['quantity']) }}</flux:table.cell>
                        <flux:table.cell class="tabular-nums">
                            {{ number_format($product['sales']) }}
                            <span class="text-sm text-zinc-500">{{ __('app.rial') }}</span>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">{{ __('app.no_results') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
