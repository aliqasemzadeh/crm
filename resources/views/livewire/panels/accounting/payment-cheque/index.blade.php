<x-slot name="title">
    {{ __('app.payment_cheques') }}
</x-slot>

<div>
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.payment_cheques') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.payment_cheques_description') }}</flux:subheading>
            </div>
        </div>

        <flux:separator variant="subtle" />
    </div>

    <div class="space-y-6 mb-10">
        @php
            $monthly = $this->chequeStats['monthly'];
            $maxAmount = max($monthly);
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($monthly as $monthNumber => $amount)
                @php
                    $colorClass = '';
                    if ($amount > 0 && $amount == $maxAmount) {
                        $colorClass = 'border-green-500 bg-green-50/50 dark:bg-green-900/20';
                    }
                @endphp
                <flux:card class="flex flex-col items-center justify-center p-6 border-t-4 {{ $colorClass }}">
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
                <flux:heading size="lg">{{ __('app.total_annual_payment_cheques') }}</flux:heading>
                <flux:text size="2xl" class="font-black text-zinc-900 dark:text-white">
                    {{ number_format($this->chequeStats['total']) }} <span class="text-lg font-bold">{{ __('app.rial') ?? 'ریال' }}</span>
                </flux:text>
            </div>
        </flux:card>
    </div>

    <div class="mb-6">
        <flux:field>
            <flux:label>{{ __('app.search') }}</flux:label>
            <flux:input wire:model.live.debounce.500ms="search" type="text" placeholder="{{ __('app.search_in_payment_cheques') }}" />
        </flux:field>
    </div>

    <div class="mb-10">
        <flux:heading size="lg" class="mb-4">۵۰ چک آخر</flux:heading>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('app.number') }}</flux:table.column>
                <flux:table.column>{{ __('app.customer') }}</flux:table.column>
                <flux:table.column>{{ __('app.amount') }}</flux:table.column>
                <flux:table.column>{{ __('app.date') }}</flux:table.column>
            </flux:table.columns>

            @foreach ($this->recentCheques as $cheque)
                <flux:table.row :key="$cheque->PaymentChequeId">
                    <flux:table.cell>{{ $cheque->Number }}</flux:table.cell>
                    <flux:table.cell>{{ $cheque->dl->Title ?? $cheque->DlRef }}</flux:table.cell>
                    <flux:table.cell>{{ number_format($cheque->Amount) }} {{ __('app.rial') }}</flux:table.cell>
                    <flux:table.cell>
                        {{ $cheque->Date ? \Morilog\Jalali\Jalalian::fromDateTime($cheque->Date)->format('%Y-%m-%d') : '-' }}
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table>
    </div>

    <livewire:panels.accounting.payment-cheque.view />

    <flux:table :paginate="$this->cheques">
        <flux:table.columns>
            <flux:table.column class="w-1 whitespace-nowrap">{{ __('app.options') }}</flux:table.column>
            <flux:table.column>{{ __('app.number') }}</flux:table.column>
            <flux:table.column>{{ __('app.customer') }}</flux:table.column>
            <flux:table.column>{{ __('app.amount') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'Date'" :direction="$sortDirection" wire:click="sort('Date')">{{ __('app.date') }}</flux:table.column>
        </flux:table.columns>

        @foreach ($this->cheques as $cheque)
            <flux:table.row :key="$cheque->PaymentChequeId">
                <flux:table.cell class="w-1 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                        <flux:button size="xs" variant="primary" color="sky" wire:click="$dispatch('panels.accounting.payment-cheque.view.assign-data', { id: '{{ $cheque->PaymentChequeId }}' })">{{ __('app.view') }}</flux:button>
                    </div>
                </flux:table.cell>
                <flux:table.cell class="whitespace-nowrap">
                    {{ $cheque->Number }}
                </flux:table.cell>
                <flux:table.cell class="whitespace-nowrap">
                    {{ $cheque->dl->Title ?? $cheque->DlRef }}
                </flux:table.cell>
                <flux:table.cell class="whitespace-nowrap">
                    {{ number_format($cheque->Amount) }}
                </flux:table.cell>
                <flux:table.cell class="whitespace-nowrap">
                    {{ $cheque->Date ? \Morilog\Jalali\Jalalian::fromDateTime($cheque->Date)->format('%Y-%m-%d') : '-' }}
                </flux:table.cell>
            </flux:table.row>
        @endforeach
    </flux:table>
</div>
