<?php

use App\Models\Sepidar\SLS\Invoice;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

new #[Layout('layouts.panels.sale')] class extends Component
{
    public Invoice $invoice;

    public function mount(Invoice $invoice): void
    {
        $this->authorize('sales_invoice_print');

        $this->invoice = $invoice->load(['items.item', 'creator', 'customer']);
    }

    public function partyName(): string
    {
        $party = $this->invoice->customer;

        if (! $party) {
            return (string) ($this->invoice->CustomerRealName ?? '-');
        }

        return trim(implode(' ', array_filter([
            trim((string) ($party->Name ?? '')),
            trim((string) ($party->LastName ?? '')),
        ], static fn (string $part): bool => $part !== ''))) ?: (string) ($this->invoice->CustomerRealName ?? '-');
    }
};
?>

<x-slot name="title">
    {{ __('app.print') }} #{{ $invoice->Number }}
</x-slot>

<div class="print:p-0">
    <div class="mb-4 flex items-center justify-between gap-2 print:hidden">
        <flux:button variant="ghost" href="{{ route('panels.sale.invoice.view', $invoice->InvoiceId) }}" wire:navigate icon="arrow-right">
            {{ __('app.back') }}
        </flux:button>
        <flux:button variant="primary" color="sky" icon="printer" x-on:click="window.print()">
            {{ __('app.print') }}
        </flux:button>
    </div>

    <div class="mx-auto max-w-4xl bg-white p-8 text-black print:max-w-none print:p-0">
        <div class="mb-8 flex items-start justify-between border-b border-zinc-300 pb-4">
            <div>
                <h1 class="text-2xl font-bold">{{ __('app.sales_invoice') }}</h1>
                <p class="mt-1 text-sm text-zinc-600">{{ __('app.invoice_number') }}: {{ $invoice->Number }}</p>
            </div>
            <div class="text-sm space-y-1 text-left">
                <div>{{ __('app.date') }}: {{ $invoice->Date ? Jalalian::fromDateTime($invoice->Date)->format('Y/m/d') : '-' }}</div>
                <div>{{ __('app.issuer') }}: {{ $invoice->creator?->Name ?? '-' }}</div>
                <div>{{ __('app.sale_type') }}: {{ (int) $invoice->SaleTypeRef === 1 ? __('app.official') : __('app.unofficial') }}</div>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-2 gap-4 text-sm">
            <div>
                <div class="text-zinc-500">{{ __('app.customer') }}</div>
                <div class="font-semibold">{{ $this->partyName() }}</div>
            </div>
            @if (filled($invoice->Description))
                <div>
                    <div class="text-zinc-500">{{ __('app.description') }}</div>
                    <div>{{ $invoice->Description }}</div>
                </div>
            @endif
        </div>

        <table class="mb-6 w-full border-collapse text-sm">
            <thead>
                <tr class="border-b-2 border-zinc-800">
                    <th class="py-2 text-right font-semibold">#</th>
                    <th class="py-2 text-right font-semibold">{{ __('app.item') }}</th>
                    <th class="py-2 text-right font-semibold">{{ __('app.quantity') }}</th>
                    <th class="py-2 text-right font-semibold">{{ __('app.fee') }}</th>
                    <th class="py-2 text-right font-semibold">{{ __('app.discount') }}</th>
                    <th class="py-2 text-right font-semibold">{{ __('app.line_total') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $index => $item)
                    <tr class="border-b border-zinc-200">
                        <td class="py-2">{{ $index + 1 }}</td>
                        <td class="py-2">
                            {{ $item->item?->Title ?? '-' }}
                            @if (filled($item->Description))
                                <div class="text-xs text-zinc-500">{{ $item->Description }}</div>
                            @endif
                        </td>
                        <td class="py-2 tabular-nums">{{ number_format($item->Quantity) }}</td>
                        <td class="py-2 tabular-nums">{{ number_format($item->Fee) }}</td>
                        <td class="py-2 tabular-nums">{{ number_format($item->Discount) }}</td>
                        <td class="py-2 tabular-nums font-medium">{{ number_format($item->NetPrice) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="ms-auto w-64 space-y-2 text-sm">
            <div class="flex justify-between"><span>{{ __('app.price') }}</span><span class="tabular-nums">{{ number_format($invoice->Price) }}</span></div>
            <div class="flex justify-between"><span>{{ __('app.discount') }}</span><span class="tabular-nums">{{ number_format($invoice->Discount) }}</span></div>
            <div class="flex justify-between border-t border-zinc-800 pt-2 text-base font-bold">
                <span>{{ __('app.net_amount') }}</span>
                <span class="tabular-nums">{{ number_format($invoice->NetPrice) }}</span>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        .panel-shell-sidebar,
        [data-flux-sidebar],
        nav,
        aside {
            display: none !important;
        }

        body {
            background: white !important;
        }

        main, .print\:p-0 {
            margin: 0 !important;
            padding: 0 !important;
            max-width: none !important;
        }
    }
</style>
