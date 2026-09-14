<?php

use App\Services\Report\SiteYearlySalesService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.panels.report')] class extends Component
{
    public function mount(): void
    {
        $this->authorize('report_site_index');
    }

    public function reload(): void
    {
        SiteYearlySalesService::clearCache();
        unset($this->yearlySales);

        Flux::toast(__('app.dashboard_data_reloaded'));
    }

    /**
     * @return array{chart: list<array{year: string, sales: float}>, total: float}
     */
    #[Computed]
    public function yearlySales(): array
    {
        return app(SiteYearlySalesService::class)->yearlySales();
    }
};
?>

<x-slot name="title">
    {{ __('app.report_site_title') }}
</x-slot>

<div class="space-y-6">
    <div class="relative w-full">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.report_site_title') }}</flux:heading>
                <flux:subheading size="lg" class="mb-2">{{ __('app.report_site_subtitle') }}</flux:subheading>
            </div>
            <flux:button wire:click="reload" icon="arrow-path" size="sm" variant="subtle" wire:loading.attr="disabled">
                {{ __('app.reload') }}
            </flux:button>
        </div>
        <flux:separator variant="subtle" class="mt-4" />
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3" wire:loading.class="opacity-60" wire:target="reload">
        <flux:card class="border-t-4 border-teal-500">
            <flux:text>{{ __('app.site_yearly_sales_total') }}</flux:text>
            <flux:heading size="xl" class="mt-2 tabular-nums">
                {{ number_format($this->yearlySales['total'] ?? 0) }}
                <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
            </flux:heading>
        </flux:card>
    </div>

    <flux:card wire:loading.class="opacity-60" wire:target="reload">
        <flux:heading size="lg" class="mb-4">{{ __('app.site_yearly_sales') }}</flux:heading>

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
    </flux:card>
</div>
