@php
    $stats = $this->stats;
    $yearCharts = $this->yearCharts;

    $percentBadge = function (?float $percent): array {
        if ($percent === null) {
            return ['label' => '—', 'class' => 'text-zinc-500'];
        }

        $formatted = number_format(abs($percent), 1).'%';

        if ($percent > 0) {
            return ['label' => '+'.$formatted, 'class' => 'text-green-600 dark:text-green-400'];
        }

        if ($percent < 0) {
            return ['label' => '-'.$formatted, 'class' => 'text-red-600 dark:text-red-400'];
        }

        return ['label' => $formatted, 'class' => 'text-zinc-500'];
    };

    $salesChange = $percentBadge($stats['salesChangePercent'] ?? null);
    $purchaseChange = $percentBadge($stats['purchaseChangePercent'] ?? null);
@endphp

<div class="space-y-6">
    <div class="relative w-full">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.dashboard') }}</flux:heading>
                <flux:text size="sm" class="mt-1">{{ __('app.accounting_dashboard_subtitle') }}</flux:text>
            </div>

            @can('accounting_profit_index')
                <div class="flex flex-wrap items-center gap-2">
                    <flux:button wire:click="reload" icon="arrow-path" size="sm" variant="subtle" wire:loading.attr="disabled">
                        {{ __('app.reload') }}
                    </flux:button>
                    <flux:select wire:model.live="selectedMonth" class="w-40">
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
            @endcan
        </div>
        <flux:separator variant="subtle" class="mt-4" />
    </div>

    @can('accounting_profit_index')
        <div
            class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5 md:gap-6"
            wire:loading.class="opacity-60"
            wire:target="fiscalYearRef,selectedMonth,reload"
        >
            <flux:card class="border-t-4 border-green-500 h-full transition hover:bg-zinc-50 dark:hover:bg-zinc-800">
                <flux:text>{{ __('app.dashboard_total_sales') }}</flux:text>
                <flux:heading size="xl" class="mt-2 tabular-nums text-green-600 dark:text-green-400">
                    {{ number_format($stats['totalSales'] ?? 0) }}
                    <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
                </flux:heading>
                <flux:text size="sm" class="mt-2 {{ $salesChange['class'] }}">{{ $salesChange['label'] }}</flux:text>
            </flux:card>

            <flux:card class="border-t-4 border-orange-500 h-full transition hover:bg-zinc-50 dark:hover:bg-zinc-800">
                <flux:text>{{ __('app.dashboard_total_purchases') }}</flux:text>
                <flux:heading size="xl" class="mt-2 tabular-nums text-orange-600 dark:text-orange-400">
                    {{ number_format($stats['totalPurchases'] ?? 0) }}
                    <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
                </flux:heading>
                <flux:text size="sm" class="mt-2 {{ $purchaseChange['class'] }}">{{ $purchaseChange['label'] }}</flux:text>
            </flux:card>

            <flux:card class="border-t-4 border-indigo-500 h-full transition hover:bg-zinc-50 dark:hover:bg-zinc-800">
                <flux:text>{{ __('app.dashboard_current_inventory') }}</flux:text>
                <flux:heading size="xl" class="mt-2 tabular-nums text-indigo-600 dark:text-indigo-400">
                    {{ number_format($stats['inventoryBalance'] ?? 0) }}
                    <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
                </flux:heading>
            </flux:card>

            <flux:card class="border-t-4 border-rose-500 h-full transition hover:bg-zinc-50 dark:hover:bg-zinc-800">
                <flux:text>{{ __('app.dashboard_total_debtor') }}</flux:text>
                <flux:heading size="xl" class="mt-2 tabular-nums text-rose-600 dark:text-rose-400">
                    {{ number_format($stats['debtorTotal'] ?? 0) }}
                    <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
                </flux:heading>
            </flux:card>

            <flux:card class="border-t-4 border-sky-500 h-full transition hover:bg-zinc-50 dark:hover:bg-zinc-800">
                <flux:text>{{ __('app.dashboard_total_creditor') }}</flux:text>
                <flux:heading size="xl" class="mt-2 tabular-nums text-sky-600 dark:text-sky-400">
                    {{ number_format($stats['creditorTotal'] ?? 0) }}
                    <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
                </flux:heading>
            </flux:card>
        </div>

        <div
            class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5 md:gap-6"
            wire:loading.class="opacity-60"
            wire:target="fiscalYearRef,selectedMonth,reload"
        >
            <a href="{{ route('panels.accounting.receipt-cheque.index') }}" class="block">
                <flux:card class="border-t-4 border-cyan-500 h-full transition hover:bg-zinc-50 dark:hover:bg-zinc-800">
                    <flux:text>{{ __('app.dashboard_receivable_cheques') }}</flux:text>
                    <flux:heading size="xl" class="mt-2 tabular-nums text-cyan-600 dark:text-cyan-400">
                        {{ number_format($stats['receivableCheques'] ?? 0) }}
                        <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
                    </flux:heading>
                </flux:card>
            </a>

            <flux:card class="border-t-4 border-blue-500 h-full transition hover:bg-zinc-50 dark:hover:bg-zinc-800">
                <flux:text>{{ __('app.dashboard_transferred_cheques') }}</flux:text>
                <flux:heading size="xl" class="mt-2 tabular-nums text-blue-600 dark:text-blue-400">
                    {{ number_format($stats['transferredCheques'] ?? 0) }}
                    <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
                </flux:heading>
            </flux:card>

            <a href="{{ route('panels.accounting.payment-cheque.index') }}" class="block">
                <flux:card class="border-t-4 border-amber-500 h-full transition hover:bg-zinc-50 dark:hover:bg-zinc-800">
                    <flux:text>{{ __('app.dashboard_payable_cheques') }}</flux:text>
                    <flux:heading size="xl" class="mt-2 tabular-nums text-amber-600 dark:text-amber-400">
                        {{ number_format($stats['payableCheques'] ?? 0) }}
                        <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
                    </flux:heading>
                </flux:card>
            </a>

            <flux:card class="border-t-4 border-purple-500 h-full transition hover:bg-zinc-50 dark:hover:bg-zinc-800">
                <flux:text>{{ __('app.dashboard_period_balance') }}</flux:text>
                <flux:heading size="xl" class="mt-2 tabular-nums {{ (($stats['periodBalance'] ?? 0) >= 0) ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ number_format($stats['periodBalance'] ?? 0) }}
                    <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
                </flux:heading>
            </flux:card>

            <flux:card class="border-t-4 border-violet-500 h-full transition hover:bg-zinc-50 dark:hover:bg-zinc-800">
                <flux:text>{{ __('app.dashboard_engaged_capital') }}</flux:text>
                <flux:heading size="xl" class="mt-2 tabular-nums text-violet-600 dark:text-violet-400">
                    {{ number_format($stats['engagedCapital'] ?? 0) }}
                    <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
                </flux:heading>
            </flux:card>
        </div>

        <div
            class="grid grid-cols-1 gap-6 xl:grid-cols-2"
            wire:loading.class="opacity-60"
            wire:target="fiscalYearRef,reload"
        >
            <flux:card>
                <div class="mb-4 flex items-center justify-between gap-3">
                    <flux:heading size="lg">{{ __('app.sales_by_month') }}</flux:heading>
                    <a href="{{ route('panels.accounting.invoice.index') }}" class="text-sm text-teal-600 hover:underline dark:text-teal-400">
                        {{ __('app.invoices') }}
                    </a>
                </div>

                <flux:chart :value="$yearCharts['salesChart'] ?? []" class="h-80">
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
                <flux:heading size="lg" class="mb-4">{{ __('app.sales_vs_purchases') }}</flux:heading>

                <flux:chart :value="$yearCharts['salesPurchasesChart'] ?? []" class="h-80">
                    <flux:chart.viewport class="min-h-[20rem]">
                        <flux:chart.svg>
                            <flux:chart.line field="sales" class="text-green-500" curve="none" />
                            <flux:chart.point field="sales" class="text-green-500" r="4" stroke-width="2" />

                            <flux:chart.line field="purchases" class="text-orange-500" curve="none" />
                            <flux:chart.point field="purchases" class="text-orange-500" r="4" stroke-width="2" />

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
                        <flux:chart.legend :label="__('app.sales')">
                            <flux:chart.legend.indicator class="bg-green-500" />
                        </flux:chart.legend>
                        <flux:chart.legend :label="__('app.purchases')">
                            <flux:chart.legend.indicator class="bg-orange-500" />
                        </flux:chart.legend>
                    </div>

                    <flux:chart.tooltip>
                        <flux:chart.tooltip.heading field="month" />
                        <flux:chart.tooltip.value field="sales" :label="__('app.sales')" :format="['useGrouping' => true]" />
                        <flux:chart.tooltip.value field="purchases" :label="__('app.purchases')" :format="['useGrouping' => true]" />
                    </flux:chart.tooltip>
                </flux:chart>
            </flux:card>
        </div>

        <flux:card wire:loading.class="opacity-60" wire:target="fiscalYearRef,reload">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <flux:heading size="lg">{{ __('app.debtor_creditor_by_month') }}</flux:heading>
                    <flux:text size="sm" class="mt-1">{{ __('app.debtor_creditor_by_month_description') }}</flux:text>
                </div>
                <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 dark:border-rose-900 dark:bg-rose-950/40">
                    <flux:text size="sm">{{ __('app.min_debtor_balance') }}</flux:text>
                    <flux:heading size="lg" class="mt-1 tabular-nums text-rose-600 dark:text-rose-400">
                        {{ number_format($yearCharts['minDebtorBalance'] ?? 0) }}
                        <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
                    </flux:heading>
                </div>
            </div>

            <flux:chart :value="$yearCharts['debtorCreditorChart'] ?? []" class="h-96">
                <flux:chart.viewport class="min-h-[24rem]">
                    <flux:chart.svg>
                        <flux:chart.line field="debtor" class="text-rose-500" curve="none" />
                        <flux:chart.point field="debtor" class="text-rose-500" r="4" stroke-width="2" />

                        <flux:chart.line field="creditor" class="text-sky-500" curve="none" />
                        <flux:chart.point field="creditor" class="text-sky-500" r="4" stroke-width="2" />

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
                    <flux:chart.legend :label="__('app.debtor')">
                        <flux:chart.legend.indicator class="bg-rose-500" />
                    </flux:chart.legend>
                    <flux:chart.legend :label="__('app.creditor')">
                        <flux:chart.legend.indicator class="bg-sky-500" />
                    </flux:chart.legend>
                </div>

                <flux:chart.tooltip>
                    <flux:chart.tooltip.heading field="month" />
                    <flux:chart.tooltip.value field="debtor" :label="__('app.debtor')" :format="['useGrouping' => true]" />
                    <flux:chart.tooltip.value field="creditor" :label="__('app.creditor')" :format="['useGrouping' => true]" />
                </flux:chart.tooltip>
            </flux:chart>
        </flux:card>
    @else
        <flux:card>
            <flux:text class="py-8 text-center">{{ __('app.no_permission') }}</flux:text>
        </flux:card>
    @endcan
</div>
