<div class="space-y-6">
    <flux:header>
        <flux:heading size="xl">{{ __('app.receipts') }}</flux:heading>
    </flux:header>

    @php
        $monthly = $this->receiptStats['monthly'];
        $maxAmount = max($monthly);
        $minAmount = min($monthly);
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @foreach($monthly as $monthNumber => $amount)
            @php
                $colorClass = '';
                if ($amount > 0) {
                    if ($amount == $maxAmount) $colorClass = 'border-green-500 bg-green-50/50 dark:bg-green-900/20';
                    elseif ($amount == $minAmount) $colorClass = 'border-red-500 bg-red-50/50 dark:bg-red-900/20';
                }
            @endphp
            <flux:card class="flex flex-col items-center justify-center p-6 border-t-4 {{ $colorClass }}">
                <flux:heading size="lg" class="mb-2">
                    {{ __('app.jalali_months.' . $monthNumber) }}
                </flux:heading>
                <flux:text size="xl" class="font-bold text-zinc-800 dark:text-zinc-100">
                    {{ number_format($amount) }} <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
                </flux:text>
            </flux:card>
        @endforeach
    </div>

    <flux:card class="bg-zinc-50 dark:bg-zinc-900 border-t-4 border-zinc-500">
        <div class="flex justify-between items-center">
            <flux:heading size="lg">{{ __('app.total_annual_receipts') }}</flux:heading>
            <flux:text size="2xl" class="font-black text-zinc-900 dark:text-white">
                {{ number_format($this->receiptStats['total']) }} <span class="text-lg font-bold">{{ __('app.rial') }}</span>
            </flux:text>
        </div>
    </flux:card>

    <div class="mt-10">
        <flux:heading size="lg" class="mb-4">۵۰ دریافتی آخر</flux:heading>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('app.number') }}</flux:table.column>
                <flux:table.column>{{ __('app.description') }}</flux:table.column>
                <flux:table.column>{{ __('app.amount') }}</flux:table.column>
                <flux:table.column>{{ __('app.date') }}</flux:table.column>
            </flux:table.columns>

            @foreach ($this->recentReceipts as $receipt)
                <flux:table.row :key="$receipt->ReceiptHeaderId">
                    <flux:table.cell>{{ $receipt->Number }}</flux:table.cell>
                    <flux:table.cell>{{ $receipt->Description }}</flux:table.cell>
                    <flux:table.cell>{{ number_format($receipt->TotalAmount) }} {{ __('app.rial') }}</flux:table.cell>
                    <flux:table.cell>
                        {{ $receipt->Date ? \Morilog\Jalali\Jalalian::fromDateTime($receipt->Date)->format('%Y-%m-%d') : '-' }}
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table>
    </div>
</div>
