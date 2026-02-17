<div class="space-y-6 p-4">
    <div>
        <flux:heading size="lg">{{ __('app.invoice_details') }}</flux:heading>
        <flux:text class="mt-2">{{ __('app.invoice_number') }}: {{ $invoice->Number }}</flux:text>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-zinc-50 dark:bg-zinc-800 p-4 rounded-lg border border-zinc-200 dark:border-zinc-700">
        <div class="flex flex-col">
            <flux:text size="sm">{{ __('app.customer') }}</flux:text>
            <flux:heading size="sm">{{ $invoice->customer?->Name }} {{ $invoice->customer?->LastName }}</flux:heading>
        </div>
        <div class="flex flex-col">
            <flux:text size="sm">{{ __('app.date') }}</flux:text>
            <flux:heading size="sm">
                {{ $invoice->Date ? \Morilog\Jalali\Jalalian::fromDateTime($invoice->Date)->format('%Y-%m-%d') : '-' }}
            </flux:heading>
        </div>
        <div class="flex flex-col">
            <flux:text size="sm">{{ __('app.total_price') }}</flux:text>
            <flux:heading size="sm">{{ number_format($invoice->Price) }} {{ __('app.rial') }}</flux:heading>
        </div>
    </div>

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
                    <flux:table.cell>{{ $item->item?->Title }}</flux:table.cell>
                    <flux:table.cell>{{ number_format($item->Quantity) }}</flux:table.cell>
                    <flux:table.cell>{{ number_format($item->Fee) }}</flux:table.cell>
                    <flux:table.cell>{{ number_format($item->NetPrice) }}</flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>
