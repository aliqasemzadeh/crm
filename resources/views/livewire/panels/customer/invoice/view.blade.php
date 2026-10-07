<x-slot name="title">
    {{ __('app.invoice_details') }} #{{ $invoice->Number }} - {{ config('app.name') }}
</x-slot>

<div class="space-y-6 p-4">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('app.invoice_details') }}</flux:heading>
            <flux:subheading size="lg" class="mt-1">{{ __('app.invoice_number') }}: {{ $invoice->Number }}</flux:subheading>
        </div>

        @if($invoice->CustomerPartyRef)
            <flux:button
                variant="primary"
                color="teal"
                icon="history"
                icon:variant="outline"
                href="{{ \Illuminate\Support\Facades\URL::signedRoute('panels.customer.invoice.history', ['partyId' => $invoice->CustomerPartyRef]) }}"
                wire:navigate
            >
                {{ __('app.purchase_history') }}
            </flux:button>
        @endif
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 bg-zinc-50 dark:bg-zinc-800 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700">
        <div class="flex flex-col">
            <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">{{ __('app.customer') }}</flux:text>
            <flux:heading size="sm" class="mt-1">{{ $invoice->customer?->Name }} {{ $invoice->customer?->LastName }}</flux:heading>
        </div>
        <div class="flex flex-col">
            <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">{{ __('app.date') }}</flux:text>
            <flux:heading size="sm" class="mt-1">
                {{ $invoice->Date ? \Morilog\Jalali\Jalalian::fromDateTime($invoice->Date)->format('%Y-%m-%d') : '-' }}
            </flux:heading>
        </div>
        <div class="flex flex-col">
            <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">{{ __('app.total_price') }}</flux:text>
            <flux:heading size="sm" class="mt-1 text-emerald-600 dark:text-emerald-400">{{ number_format($invoice->Price) }} {{ __('app.rial') }}</flux:heading>
        </div>
    </div>

    <flux:card>
        <flux:table>
            <flux:table.columns class="bg-white dark:bg-zinc-900">
                <flux:table.column>{{ __('app.item_name') }}</flux:table.column>
                <flux:table.column>{{ __('app.quantity') }}</flux:table.column>
                <flux:table.column>{{ __('app.fee') }}</flux:table.column>
                <flux:table.column>{{ __('app.total') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach($invoice->items as $item)
                    <flux:table.row>
                        <flux:table.cell>{{ $item->item?->Title ?? '-' }}</flux:table.cell>
                        <flux:table.cell>{{ number_format($item->Quantity) }}</flux:table.cell>
                        <flux:table.cell>{{ number_format($item->Fee) }} {{ __('app.rial') }}</flux:table.cell>
                        <flux:table.cell>{{ number_format($item->NetPrice) }} {{ __('app.rial') }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
