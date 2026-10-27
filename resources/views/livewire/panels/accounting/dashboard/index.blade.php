@php
    $stats = $this->stats;
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

    $cards = [
        [
            'label' => __('app.dashboard_total_sales'),
            'value' => $stats['totalSales'] ?? 0,
            'color' => 'border-green-500',
            'text' => 'text-green-600 dark:text-green-400',
            'badge' => null,
        ],
        [
            'label' => __('app.dashboard_sales_change_percent'),
            'value' => null,
            'display' => $salesChange['label'],
            'color' => 'border-emerald-500',
            'text' => $salesChange['class'],
            'badge' => null,
            'is_percent' => true,
        ],
        [
            'label' => __('app.dashboard_total_purchases'),
            'value' => $stats['totalPurchases'] ?? 0,
            'color' => 'border-orange-500',
            'text' => 'text-orange-600 dark:text-orange-400',
            'badge' => null,
        ],
        [
            'label' => __('app.dashboard_purchase_change_percent'),
            'value' => null,
            'display' => $purchaseChange['label'],
            'color' => 'border-amber-500',
            'text' => $purchaseChange['class'],
            'badge' => null,
            'is_percent' => true,
        ],
        [
            'label' => __('app.dashboard_current_inventory'),
            'value' => $stats['inventoryBalance'] ?? 0,
            'color' => 'border-indigo-500',
            'text' => 'text-indigo-600 dark:text-indigo-400',
            'badge' => null,
        ],
        [
            'label' => __('app.dashboard_total_creditor'),
            'value' => $stats['creditorTotal'] ?? 0,
            'color' => 'border-sky-500',
            'text' => 'text-sky-600 dark:text-sky-400',
            'badge' => null,
        ],
        [
            'label' => __('app.dashboard_total_debtor'),
            'value' => $stats['debtorTotal'] ?? 0,
            'color' => 'border-rose-500',
            'text' => 'text-rose-600 dark:text-rose-400',
            'badge' => null,
        ],
        [
            'label' => __('app.dashboard_receivable_cheques'),
            'value' => $stats['receivableCheques'] ?? 0,
            'color' => 'border-cyan-500',
            'text' => 'text-cyan-600 dark:text-cyan-400',
            'badge' => null,
        ],
        [
            'label' => __('app.dashboard_transferred_cheques'),
            'value' => $stats['transferredCheques'] ?? 0,
            'color' => 'border-blue-500',
            'text' => 'text-blue-600 dark:text-blue-400',
            'badge' => null,
        ],
        [
            'label' => __('app.dashboard_period_balance'),
            'value' => $stats['periodBalance'] ?? 0,
            'color' => 'border-purple-500',
            'text' => (($stats['periodBalance'] ?? 0) >= 0)
                ? 'text-green-600 dark:text-green-400'
                : 'text-red-600 dark:text-red-400',
            'badge' => null,
        ],
        [
            'label' => __('app.dashboard_fixed_costs'),
            'value' => $stats['fixedCosts'] ?? 0,
            'color' => 'border-red-500',
            'text' => 'text-red-600 dark:text-red-400',
            'badge' => null,
        ],
        [
            'label' => __('app.dashboard_variable_costs'),
            'value' => $stats['variableCosts'] ?? 0,
            'color' => 'border-pink-500',
            'text' => 'text-pink-600 dark:text-pink-400',
            'badge' => null,
        ],
        [
            'label' => __('app.dashboard_engaged_capital'),
            'value' => $stats['engagedCapital'] ?? 0,
            'color' => 'border-violet-500',
            'text' => 'text-violet-600 dark:text-violet-400',
            'badge' => null,
        ],
        [
            'label' => __('app.dashboard_operating_profit'),
            'value' => $stats['operatingProfit'] ?? 0,
            'color' => 'border-teal-500',
            'text' => (($stats['operatingProfit'] ?? 0) >= 0)
                ? 'text-teal-600 dark:text-teal-400'
                : 'text-red-600 dark:text-red-400',
            'badge' => null,
        ],
        [
            'label' => __('app.dashboard_non_operating_profit'),
            'value' => $stats['nonOperatingProfit'] ?? 0,
            'color' => 'border-lime-500',
            'text' => (($stats['nonOperatingProfit'] ?? 0) >= 0)
                ? 'text-lime-600 dark:text-lime-400'
                : 'text-red-600 dark:text-red-400',
            'badge' => null,
        ],
    ];
@endphp

<div class="space-y-6">
    <div class="relative w-full">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ __('app.dashboard') }}</flux:heading>
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
            class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-5"
            wire:loading.class="opacity-60"
            wire:target="fiscalYearRef,selectedMonth,reload"
        >
            @foreach($cards as $card)
                <flux:card class="border-t-4 {{ $card['color'] }} h-full">
                    <flux:text>{{ $card['label'] }}</flux:text>
                    <flux:heading size="xl" class="mt-2 tabular-nums {{ $card['text'] }}">
                        @if(! empty($card['is_percent']))
                            {{ $card['display'] }}
                        @else
                            {{ number_format($card['value'] ?? 0) }}
                            <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
                        @endif
                    </flux:heading>
                </flux:card>
            @endforeach
        </div>
    @else
        <flux:card>
            <flux:text class="py-8 text-center">{{ __('app.no_permission') }}</flux:text>
        </flux:card>
    @endcan
</div>
