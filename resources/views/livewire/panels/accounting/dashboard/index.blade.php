<div class="space-y-6">
    <flux:header>
        <flux:heading size="xl">{{ __('app.dashboard') }}</flux:heading>
    </flux:header>

    @can('accounting_profit_index')


        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <flux:card class="border-t-4 border-green-500">
                <div class="flex justify-between items-center">
                    <flux:heading size="lg">{{ __('app.total_annual_receipts') }}</flux:heading>
                    <flux:text size="2xl" class="font-black text-green-600 dark:text-green-400">
                        {{ number_format($this->stats['totalReceipts'] ?? 0) }} <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
                    </flux:text>
                </div>
            </flux:card>

            <flux:card class="border-t-4 border-red-500">
                <div class="flex justify-between items-center">
                    <flux:heading size="lg">{{ __('app.total_annual_expenses') }}</flux:heading>
                    <flux:text size="2xl" class="font-black text-red-600 dark:text-red-400">
                        {{ number_format($this->stats['totalExpenses'] ?? 0) }} <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
                    </flux:text>
                </div>
            </flux:card>

            <flux:card class="border-t-4 border-purple-500">
                <div class="flex justify-between items-center">
                    <flux:heading size="lg">{{ __('app.receipts_and_payments_diff') }}</flux:heading>
                    <flux:text size="2xl" class="font-black {{ ($this->stats['receiptsPaymentsDiff'] ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ number_format($this->stats['receiptsPaymentsDiff'] ?? 0) }} <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
                    </flux:text>
                </div>
            </flux:card>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <flux:card class="border-t-4 border-sky-500">
                <div class="flex justify-between items-center">
                    <flux:heading size="lg">{{ __('app.total_uncashed_amount') }} ({{ __('app.receipt_cheques') }})</flux:heading>
                    <flux:text size="2xl" class="font-black text-sky-600 dark:text-sky-400">
                        {{ number_format($this->stats['uncashedReceiptsSum'] ?? 0) }} <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
                    </flux:text>
                </div>
            </flux:card>

            <flux:card class="border-t-4 border-orange-500">
                <div class="flex justify-between items-center">
                    <flux:heading size="lg">{{ __('app.total_uncashed_amount') }} ({{ __('app.payment_cheques') }})</flux:heading>
                    <flux:text size="2xl" class="font-black text-orange-600 dark:text-orange-400">
                        {{ number_format($this->stats['uncashedPaymentsSum'] ?? 0) }} <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
                    </flux:text>
                </div>
            </flux:card>
        </div>

        <flux:card>
            <flux:heading size="lg" class="mb-6">{{ __('app.receipts_vs_expenses') }}</flux:heading>

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

                        <flux:chart.axis axis="y">
                            <flux:chart.axis.grid />
                            <flux:chart.axis.tick />
                        </flux:chart.axis>
                    </flux:chart.svg>
                </flux:chart.viewport>

                <div class="flex justify-center gap-6 pt-6">
                    <flux:chart.legend label="{{ __('app.receipts') }}">
                        <flux:chart.legend.indicator class="bg-green-500" />
                    </flux:chart.legend>

                    <flux:chart.legend label="{{ __('app.expenses') }}">
                        <flux:chart.legend.indicator class="bg-red-500" />
                    </flux:chart.legend>
                </div>
            </flux:chart>
        </flux:card>

    @endcan
</div>
