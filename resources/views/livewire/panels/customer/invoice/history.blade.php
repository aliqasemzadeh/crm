<x-slot name="title">
    {{ __('app.purchase_history') }} — {{ $this->customerName() }} - {{ config('app.name') }}
</x-slot>

<div class="space-y-6 p-4">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('app.purchase_history') }}</flux:heading>
            <flux:subheading size="lg" class="mt-1">{{ __('app.customer') }}: {{ $this->customerName() }}</flux:subheading>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-zinc-50 dark:bg-zinc-800 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 flex flex-col justify-between">
            <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">{{ __('app.invoices_count') }}</flux:text>
            <flux:heading size="lg" class="mt-2">{{ number_format($this->stats['total_count']) }}</flux:heading>
        </div>

        <div class="bg-zinc-50 dark:bg-zinc-800 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 flex flex-col justify-between">
            <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">{{ __('app.total_purchases') }}</flux:text>
            <flux:heading size="lg" class="mt-2 text-emerald-600 dark:text-emerald-400">
                {{ number_format($this->stats['total_amount']) }} {{ __('app.rial') }}
            </flux:heading>
        </div>

        <div class="bg-zinc-50 dark:bg-zinc-800 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 flex flex-col justify-between">
            <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">{{ __('app.last_purchase_date') }}</flux:text>
            <flux:heading size="lg" class="mt-2">
                {{ $this->stats['last_invoice']?->Date ? \Morilog\Jalali\Jalalian::fromDateTime($this->stats['last_invoice']->Date)->format('%Y-%m-%d') : '-' }}
            </flux:heading>
        </div>
    </div>

    <flux:card>
        <div class="mb-4">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="search"
                placeholder="{{ __('app.search') }}..."
                clearable
            />
        </div>

        <flux:table :paginate="$this->invoices">
            <flux:table.columns class="bg-white dark:bg-zinc-900">
                <flux:table.column>{{ __('app.invoice_number') }}</flux:table.column>
                <flux:table.column>{{ __('app.date') }}</flux:table.column>
                <flux:table.column>{{ __('app.items_count') }}</flux:table.column>
                <flux:table.column>{{ __('app.total_price') }}</flux:table.column>
                <flux:table.column align="end">{{ __('app.options') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($this->invoices as $invoice)
                    <flux:table.row :key="$invoice->InvoiceId">
                        <flux:table.cell class="font-medium whitespace-nowrap">{{ $invoice->Number }}</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">
                            {{ $invoice->Date ? \Morilog\Jalali\Jalalian::fromDateTime($invoice->Date)->format('%Y-%m-%d') : '-' }}
                        </flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">
                            {{ $invoice->items->count() }}
                        </flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap font-medium text-emerald-600 dark:text-emerald-400">
                            {{ number_format($invoice->Price) }} {{ __('app.rial') }}
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:tooltip content="{{ __('app.view_invoice') }}">
                                <flux:button
                                    size="xs"
                                    variant="primary"
                                    color="blue"
                                    icon="eye"
                                    icon:variant="outline"
                                    href="{{ \Illuminate\Support\Facades\URL::signedRoute('panels.customer.invoice.view', ['invoiceId' => $invoice->InvoiceId]) }}"
                                    wire:navigate
                                />
                            </flux:tooltip>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center py-8 text-zinc-500">
                            {{ __('app.no_invoices_found') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
