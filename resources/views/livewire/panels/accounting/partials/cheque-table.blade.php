<div class="mb-6">
    <flux:field>
        <flux:label>{{ __('app.search') }}</flux:label>
        <flux:input wire:model.live.debounce.500ms="search" type="text" placeholder="{{ $searchPlaceholder }}" />
    </flux:field>
</div>

<flux:table :paginate="$cheques">
    <flux:table.columns>
        <flux:table.column class="w-1 whitespace-nowrap">{{ __('app.options') }}</flux:table.column>
        <flux:table.column>{{ __('app.number') }}</flux:table.column>
        <flux:table.column>{{ __('app.customer') }}</flux:table.column>
        <flux:table.column>{{ __('app.amount') }}</flux:table.column>
        <flux:table.column sortable :sorted="$sortBy === 'Date'" :direction="$sortDirection" wire:click="sort('Date')">{{ __('app.date') }}</flux:table.column>
        <flux:table.column>{{ __('app.status') }}</flux:table.column>
        <flux:table.column>{{ __('app.cheque_bank_account') }}</flux:table.column>
        <flux:table.column>{{ __('app.cheque_account_balance') }}</flux:table.column>
    </flux:table.columns>

    @foreach ($cheques as $cheque)
        @php
            $tone = $this->chequeTone($cheque);
            $id = $cheque->{$idField};
            $rowClass = match ($tone) {
                'green' => 'bg-green-100 dark:bg-green-950/40',
                'red' => 'bg-red-100 dark:bg-red-950/40',
                default => '',
            };
            $amountClass = match ($tone) {
                'green' => 'text-green-700 dark:text-green-300 font-semibold',
                'red' => 'text-red-700 dark:text-red-300 font-semibold',
                default => '',
            };
            $balance = $this->chequeAccountBalance($cheque);
        @endphp
        <flux:table.row :key="$id" class="{{ $rowClass }}">
            <flux:table.cell class="w-1 whitespace-nowrap">
                <flux:tooltip content="{{ __('app.view') }}">
                    <flux:button size="xs" variant="primary" color="sky" icon="eye" icon:variant="outline" wire:click="$dispatch('{{ $viewEvent }}', { id: '{{ $id }}' })" />
                </flux:tooltip>
            </flux:table.cell>
            <flux:table.cell class="whitespace-nowrap">{{ $cheque->Number }}</flux:table.cell>
            <flux:table.cell class="whitespace-nowrap">{{ $cheque->dl->Title ?? $cheque->DlRef }}</flux:table.cell>
            <flux:table.cell class="whitespace-nowrap {{ $amountClass }}">{{ number_format($cheque->Amount) }}</flux:table.cell>
            <flux:table.cell class="whitespace-nowrap">
                {{ $cheque->Date ? \Morilog\Jalali\Jalalian::fromDateTime($cheque->Date)->format('%Y-%m-%d') : '-' }}
            </flux:table.cell>
            <flux:table.cell class="whitespace-nowrap">
                <flux:badge size="sm" color="{{ $tone === 'green' ? 'green' : ($tone === 'red' ? 'red' : 'amber') }}">
                    {{ $this->chequeStatusLabel($cheque) }}
                </flux:badge>
            </flux:table.cell>
            <flux:table.cell class="whitespace-nowrap">{{ $this->chequeAccountLabel($cheque) }}</flux:table.cell>
            <flux:table.cell class="whitespace-nowrap">
                {{ $balance === null ? '—' : number_format($balance).' '.__('app.rial') }}
            </flux:table.cell>
        </flux:table.row>
    @endforeach
</flux:table>
