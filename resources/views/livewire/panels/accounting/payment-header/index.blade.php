<div class="space-y-6">
    <flux:header>
        <flux:heading size="xl">{{ __('app.expenses') }}</flux:heading>
    </flux:header>

    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @foreach($expenses['monthly'] as $monthNumber => $amount)
            <flux:card class="flex flex-col items-center justify-center p-6">
                <flux:heading size="lg" class="mb-2">
                    {{ __('app.jalali_months.' . $monthNumber) }}
                </flux:heading>
                <flux:text size="xl" class="font-bold text-zinc-800 dark:text-zinc-100">
                    {{ number_format($amount) }} <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') ?? 'ریال' }}</span>
                </flux:text>
            </flux:card>
        @endforeach
    </div>

    <flux:card class="bg-zinc-50 dark:bg-zinc-900 border-t-4 border-zinc-500">
        <div class="flex justify-between items-center">
            <flux:heading size="lg">{{ __('app.total_annual_expenses') }}</flux:heading>
            <flux:text size="2xl" class="font-black text-zinc-900 dark:text-white">
                {{ number_format($expenses['total']) }} <span class="text-lg font-bold">{{ __('app.rial') ?? 'ریال' }}</span>
            </flux:text>
        </div>
    </flux:card>
</div>
