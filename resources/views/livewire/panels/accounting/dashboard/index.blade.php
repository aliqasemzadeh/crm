<div class="space-y-6">
    <div class="relative w-full">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <flux:heading size="xl">{{ __('app.dashboard') }}</flux:heading>

            @can('accounting_profit_index')
                <div class="flex items-center gap-2">
                    <flux:button wire:click="reload" icon="arrow-path" size="sm" variant="subtle" wire:loading.attr="disabled">
                        {{ __('app.reload') }}
                    </flux:button>
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
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6" wire:loading.class="opacity-60" wire:target="fiscalYearRef,reload">
            <flux:card class="border-t-4 border-green-500">
                <div class="flex justify-between items-center">
                    <flux:heading size="lg">{{ __('app.total_annual_receipts') }}</flux:heading>
                    <flux:text size="2xl" class="font-black text-green-600 dark:text-green-400 tabular-nums">
                        {{ number_format($this->stats['totalReceipts'] ?? 0) }} <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
                    </flux:text>
                </div>
            </flux:card>

            <flux:card class="border-t-4 border-red-500">
                <div class="flex justify-between items-center">
                    <flux:heading size="lg">{{ __('app.total_annual_expenses') }}</flux:heading>
                    <flux:text size="2xl" class="font-black text-red-600 dark:text-red-400 tabular-nums">
                        {{ number_format($this->stats['totalExpenses'] ?? 0) }} <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
                    </flux:text>
                </div>
            </flux:card>

            <flux:card class="border-t-4 border-purple-500">
                <div class="flex justify-between items-center">
                    <flux:heading size="lg">{{ __('app.receipts_and_payments_diff') }}</flux:heading>
                    <flux:text size="2xl" class="font-black tabular-nums {{ ($this->stats['receiptsPaymentsDiff'] ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ number_format($this->stats['receiptsPaymentsDiff'] ?? 0) }} <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
                    </flux:text>
                </div>
            </flux:card>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6" wire:loading.class="opacity-60" wire:target="fiscalYearRef,reload">
            <flux:card class="border-t-4 border-sky-500">
                <div class="flex justify-between items-center gap-4">
                    <flux:heading size="lg">{{ __('app.total_uncashed_amount') }} ({{ __('app.receipt_cheques') }})</flux:heading>
                    <flux:text size="2xl" class="font-black text-sky-600 dark:text-sky-400 tabular-nums shrink-0">
                        {{ number_format($this->stats['uncashedReceiptsSum'] ?? 0) }} <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
                    </flux:text>
                </div>
            </flux:card>

            <flux:card class="border-t-4 border-orange-500">
                <div class="flex justify-between items-center gap-4">
                    <flux:heading size="lg">{{ __('app.total_uncashed_amount') }} ({{ __('app.payment_cheques') }})</flux:heading>
                    <flux:text size="2xl" class="font-black text-orange-600 dark:text-orange-400 tabular-nums shrink-0">
                        {{ number_format($this->stats['uncashedPaymentsSum'] ?? 0) }} <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
                    </flux:text>
                </div>
            </flux:card>
        </div>

        <flux:card wire:loading.class="opacity-60" wire:target="fiscalYearRef,reload">
            <div class="mb-6">
                <flux:heading size="lg">{{ __('app.receipts_vs_expenses') }}</flux:heading>
                <flux:text size="sm" class="mt-1">{{ __('app.click_month_for_details') }}</flux:text>
            </div>

            <div
                x-data="{
                    months: @js(array_column($this->stats['chartData'] ?? [], 'monthNumber')),
                    monthFromClick(event) {
                        const points = Array.from(
                            $el.querySelectorAll('[data-point-group][data-series=\'receipts\'] [data-point]')
                        );
                        if (! points.length) return null;
                        let best = null;
                        let bestDistance = Infinity;
                        points.forEach((point, index) => {
                            const box = point.getBoundingClientRect();
                            const distance = Math.abs(event.clientX - (box.left + box.width / 2));
                            if (distance < bestDistance) {
                                bestDistance = distance;
                                best = index;
                            }
                        });
                        return this.months[best] ?? best + 1;
                    },
                }"
                @click="(month => month && $wire.openMonthBreakdown(month))(monthFromClick($event))"
                class="cursor-pointer"
            >
                <flux:chart :value="$this->stats['chartData'] ?? []" class="h-80">
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

                    <div class="flex justify-center gap-6 pt-6">
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
                        <flux:chart.tooltip.value field="diff" :label="__('app.receipts_and_payments_diff')" :format="['useGrouping' => true]" />
                    </flux:chart.tooltip>
                </flux:chart>
            </div>
        </flux:card>

        <flux:modal name="panels.accounting.dashboard.month-breakdown.modal" flyout position="right" class="md:w-96">
            @php($breakdown = $this->monthBreakdown)
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        {{ __('app.month_breakdown') }}
                        @if($breakdown)
                            — {{ $breakdown['month'] }}
                        @endif
                    </flux:heading>
                    <flux:text class="mt-2">{{ __('app.month_breakdown_description') }}</flux:text>
                </div>

                @if($breakdown)
                    <flux:card class="border-t-4 border-green-500">
                        <flux:text>{{ __('app.monthly_receipts') }}</flux:text>
                        <flux:heading size="lg" class="mt-1 tabular-nums">
                            {{ number_format($breakdown['receipts'] ?? 0) }} {{ __('app.rial') }}
                        </flux:heading>
                    </flux:card>

                    <flux:card class="border-t-4 border-red-500">
                        <flux:text>{{ __('app.monthly_expenses') }}</flux:text>
                        <flux:heading size="lg" class="mt-1 tabular-nums">
                            {{ number_format($breakdown['expenses'] ?? 0) }} {{ __('app.rial') }}
                        </flux:heading>
                    </flux:card>

                    <flux:card class="border-t-4 border-purple-500">
                        <flux:text>{{ __('app.receipts_and_payments_diff') }}</flux:text>
                        <flux:heading size="lg" class="mt-1 tabular-nums {{ ($breakdown['diff'] ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ number_format($breakdown['diff'] ?? 0) }} {{ __('app.rial') }}
                        </flux:heading>
                    </flux:card>

                    <flux:card class="border-t-4 border-red-500">
                        <flux:text>{{ __('app.largest_payment_reason') }}</flux:text>
                        <flux:heading size="lg" class="mt-1">
                            {{ $breakdown['topPayment']['description'] ?? '-' }}
                        </flux:heading>
                        <flux:text class="mt-1 tabular-nums">
                            {{ number_format($breakdown['topPayment']['amount'] ?? 0) }} {{ __('app.rial') }}
                        </flux:text>
                        @if(! empty($breakdown['largestPayment']))
                            <flux:separator variant="subtle" class="my-3" />
                            <flux:text size="sm">
                                {{ __('app.number') }}: {{ $breakdown['largestPayment']['number'] }} ·
                                {{ $breakdown['largestPayment']['date'] }} ·
                                {{ number_format($breakdown['largestPayment']['amount']) }} {{ __('app.rial') }}
                            </flux:text>
                        @endif
                    </flux:card>

                    <flux:card class="border-t-4 border-green-500">
                        <flux:text>{{ __('app.largest_receipt_source') }}</flux:text>
                        <flux:heading size="lg" class="mt-1">
                            {{ $breakdown['topReceipt']['description'] ?? '-' }}
                        </flux:heading>
                        <flux:text class="mt-1 tabular-nums">
                            {{ number_format($breakdown['topReceipt']['amount'] ?? 0) }} {{ __('app.rial') }}
                        </flux:text>
                        @if(! empty($breakdown['largestReceipt']))
                            <flux:separator variant="subtle" class="my-3" />
                            <flux:text size="sm">
                                {{ __('app.number') }}: {{ $breakdown['largestReceipt']['number'] }} ·
                                {{ $breakdown['largestReceipt']['date'] }} ·
                                {{ number_format($breakdown['largestReceipt']['amount']) }} {{ __('app.rial') }}
                            </flux:text>
                        @endif
                    </flux:card>

                    <flux:button
                        href="{{ route('panels.accounting.payment-header.index') }}"
                        variant="primary"
                        color="red"
                        class="w-full"
                        wire:navigate
                    >
                        {{ __('app.view_payments') }}
                    </flux:button>

                    <flux:button
                        href="{{ route('panels.accounting.receipt-header.index') }}"
                        variant="primary"
                        color="green"
                        class="w-full"
                        wire:navigate
                    >
                        {{ __('app.view_receipts') }}
                    </flux:button>
                @else
                    <flux:text class="py-10 text-center">{{ __('app.no_data') }}</flux:text>
                @endif
            </div>
        </flux:modal>
    @endcan
</div>
