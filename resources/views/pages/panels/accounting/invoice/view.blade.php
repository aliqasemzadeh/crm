<?php

use App\Models\Sepidar\SLS\Invoice;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

new #[Layout('layouts.panels.accounting')] class extends Component
{
    public Invoice $invoice;

    public function mount(Invoice $invoice): void
    {
        $this->authorize('accounting_invoice_index');

        $this->invoice = $invoice->load(['items.item.image', 'creator', 'modifier', 'customer']);
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
    {{ __('app.invoice_details') }} #{{ $invoice->Number }}
</x-slot>

<div>
    <div class="relative mb-6 w-full">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.invoice_details') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.invoice_number') }}: {{ $invoice->Number }}</flux:subheading>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @can('accounting_invoice_edit')
                    <flux:button
                        variant="primary"
                        color="orange"
                        icon="pencil"
                        href="{{ route('panels.accounting.invoice.edit', $invoice->InvoiceId) }}"
                        wire:navigate
                    >
                        {{ __('app.edit') }}
                    </flux:button>
                @endcan
                <flux:button
                    variant="primary"
                    color="sky"
                    icon="printer"
                    href="{{ route('panels.accounting.invoice.print', $invoice->InvoiceId) }}"
                    wire:navigate
                >
                    {{ __('app.print') }}
                </flux:button>
                <flux:button variant="ghost" href="{{ route('panels.accounting.invoice.index') }}" wire:navigate icon="arrow-right">
                    {{ __('app.back') }}
                </flux:button>
            </div>
        </div>
        <flux:separator variant="subtle" />
    </div>

    <div class="mx-auto max-w-5xl rounded-xl border border-zinc-300 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900 overflow-hidden">
        <div class="border-b border-zinc-200 bg-zinc-50 px-6 py-5 dark:border-zinc-700 dark:bg-zinc-800/60">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <flux:heading size="xl">{{ __('app.sales_invoice') }}</flux:heading>
                    <flux:text class="mt-1">{{ __('app.invoice_number') }}: {{ $invoice->Number }}</flux:text>
                </div>
                <div class="text-sm space-y-1">
                    <div class="flex gap-2 justify-between">
                        <span class="text-zinc-500">{{ __('app.issuer') }}:</span>
                        <span class="font-medium">{{ $invoice->creator?->Name ?? '-' }}</span>
                    </div>
                    <div class="flex gap-2 justify-between">
                        <span class="text-zinc-500">{{ __('app.date') }}:</span>
                        <span class="font-medium">
                            {{ $invoice->Date ? Jalalian::fromDateTime($invoice->Date)->format('Y/m/d') : '-' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6 space-y-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <flux:text size="sm">{{ __('app.customer') }}</flux:text>
                    <flux:heading size="sm">{{ $this->partyName() }}</flux:heading>
                </div>
                <div>
                    <flux:text size="sm">{{ __('app.sale_type') }}</flux:text>
                    <flux:heading size="sm">
                        {{ (int) $invoice->SaleTypeRef === 1 ? __('app.official') : __('app.unofficial') }}
                    </flux:heading>
                </div>
                <div>
                    <flux:text size="sm">{{ __('app.last_modifier') }}</flux:text>
                    <flux:heading size="sm">{{ $invoice->modifier?->Name ?? '-' }}</flux:heading>
                </div>
                @if (filled($invoice->Description))
                    <div class="md:col-span-3">
                        <flux:text size="sm">{{ __('app.description') }}</flux:text>
                        <flux:heading size="sm">{{ $invoice->Description }}</flux:heading>
                    </div>
                @endif
            </div>

            <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                <table class="w-full min-w-[640px] text-sm">
                    <thead class="bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                        <tr>
                            <th class="px-3 py-2 text-right font-medium w-10">#</th>
                            <th class="px-3 py-2 text-right font-medium">{{ __('app.item') }}</th>
                            <th class="px-3 py-2 text-right font-medium">{{ __('app.quantity') }}</th>
                            <th class="px-3 py-2 text-right font-medium">{{ __('app.fee') }}</th>
                            <th class="px-3 py-2 text-right font-medium">{{ __('app.discount') }}</th>
                            <th class="px-3 py-2 text-right font-medium">{{ __('app.line_total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($invoice->items as $index => $item)
                            <tr>
                                <td class="px-3 py-3 text-zinc-500">{{ $index + 1 }}</td>
                                <td class="px-3 py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($item->item?->image?->Thumbnail)
                                            <img
                                                src="data:image/jpeg;base64,{{ base64_encode($item->item->image->Thumbnail) }}"
                                                alt=""
                                                class="size-12 rounded-md object-cover shadow-sm"
                                            >
                                        @else
                                            <div class="flex size-12 items-center justify-center rounded-md bg-zinc-100 dark:bg-zinc-800">
                                                <flux:icon name="package" class="size-5 text-zinc-400" />
                                            </div>
                                        @endif
                                        <div>
                                            <div class="font-medium">{{ $item->item?->Title ?? '-' }}</div>
                                            <div class="text-xs text-zinc-500">{{ $item->item?->Code }}</div>
                                            @if (filled($item->Description))
                                                <div class="text-xs text-zinc-500 mt-1">{{ $item->Description }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-3 tabular-nums">{{ number_format($item->Quantity) }}</td>
                                <td class="px-3 py-3 tabular-nums">{{ number_format($item->Fee) }}</td>
                                <td class="px-3 py-3 tabular-nums">{{ number_format($item->Discount) }}</td>
                                <td class="px-3 py-3 font-semibold tabular-nums">{{ number_format($item->NetPrice) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="ms-auto w-full max-w-sm space-y-2 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-zinc-500">{{ __('app.price') }}</span>
                    <span class="font-medium tabular-nums">{{ number_format($invoice->Price) }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-zinc-500">{{ __('app.discount') }}</span>
                    <span class="font-medium tabular-nums">{{ number_format($invoice->Discount) }}</span>
                </div>
                <flux:separator variant="subtle" />
                <div class="flex items-center justify-between">
                    <flux:heading size="sm">{{ __('app.net_amount') }}</flux:heading>
                    <flux:heading size="lg" class="tabular-nums">{{ number_format($invoice->NetPrice) }}</flux:heading>
                </div>
            </div>
        </div>
    </div>
</div>
