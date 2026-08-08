<?php

use App\Models\Sepidar\INV\Item;
use App\Services\Sale\SaleTemporaryInvoice;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public int $count = 0;

    /** @var list<array{id: int, title: string, code: string, quantity: int, thumbnail: ?string}> */
    public array $lines = [];

    public function mount(SaleTemporaryInvoice $draft): void
    {
        $this->syncState($draft);
    }

    #[On('panels.sale.temporary-invoice.updated')]
    public function refreshTemporaryInvoice(SaleTemporaryInvoice $draft): void
    {
        $this->syncState($draft);
    }

    public function open(SaleTemporaryInvoice $draft): void
    {
        $this->authorize('sales_invoice_create');

        $this->syncState($draft);
        Flux::modal('panels.sale.temporary-invoice.modal')->show();
    }

    public function increment(int $itemId, SaleTemporaryInvoice $draft): void
    {
        $this->authorize('sales_invoice_create');

        $draft->increment($itemId);
        $this->syncState($draft);
    }

    public function decrement(int $itemId, SaleTemporaryInvoice $draft): void
    {
        $this->authorize('sales_invoice_create');

        $draft->decrement($itemId);
        $this->syncState($draft);
    }

    public function remove(int $itemId, SaleTemporaryInvoice $draft): void
    {
        $this->authorize('sales_invoice_create');

        $draft->remove($itemId);
        $this->syncState($draft);
        Flux::toast(__('app.item_removed_from_temporary_invoice'));
    }

    public function clear(SaleTemporaryInvoice $draft): void
    {
        $this->authorize('sales_invoice_create');

        $draft->clear();
        $this->syncState($draft);
        Flux::toast(__('app.temporary_invoice_empty'));
    }

    public function createInvoice(SaleTemporaryInvoice $draft): void
    {
        $this->authorize('sales_invoice_create');

        if ($draft->isEmpty()) {
            Flux::toast(__('app.temporary_invoice_empty'), variant: 'danger');

            return;
        }

        Flux::modal('panels.sale.temporary-invoice.modal')->close();

        $this->redirect(route('panels.sale.invoice.create'), navigate: true);
    }

    protected function syncState(SaleTemporaryInvoice $draft): void
    {
        $quantities = $draft->all();
        $this->count = (int) array_sum($quantities);

        if ($quantities === []) {
            $this->lines = [];

            return;
        }

        $items = Item::query()
            ->with('image')
            ->whereIn('ItemID', array_keys($quantities))
            ->get()
            ->keyBy('ItemID');

        $this->lines = collect($quantities)
            ->map(function (int $quantity, int $itemId) use ($items) {
                $item = $items->get($itemId);

                if (! $item) {
                    return null;
                }

                return [
                    'id' => $itemId,
                    'title' => (string) $item->Title,
                    'code' => (string) $item->Code,
                    'quantity' => $quantity,
                    // Binary thumbnails cannot live in Livewire public state (JSON/UTF-8).
                    'thumbnail' => $item->image?->Thumbnail
                        ? base64_encode($item->image->Thumbnail)
                        : null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
};
?>

<div class="shrink-0">
    <flux:tooltip content="{{ __('app.temporary_invoice') }}">
        <flux:button
            variant="filled"
            color="teal"
            icon="file-text"
            wire:click="open"
            class="relative"
        >
            @if ($count > 0)
                <span class="absolute -top-1.5 -left-1.5 inline-flex min-w-5 items-center justify-center rounded-full bg-rose-600 px-1.5 py-0.5 text-[10px] font-bold text-white">
                    {{ $count }}
                </span>
            @endif
        </flux:button>
    </flux:tooltip>

    <flux:modal name="panels.sale.temporary-invoice.modal" class="md:w-96" flyout position="right">
        <div class="space-y-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <flux:heading size="lg">{{ __('app.temporary_invoice') }}</flux:heading>
                    <flux:text class="mt-2">{{ __('app.temporary_invoice_description') }}</flux:text>
                </div>
                @if ($count > 0)
                    <flux:tooltip content="{{ __('app.clear_temporary_invoice') }}">
                        <flux:button
                            size="xs"
                            variant="primary"
                            color="red"
                            icon="trash"
                            icon:variant="outline"
                            wire:click="clear"
                            wire:confirm="{{ __('app.are_you_sure') }}"
                        />
                    </flux:tooltip>
                @endif
            </div>

            @if ($lines === [])
                <flux:callout variant="secondary" icon="file-text">
                    {{ __('app.temporary_invoice_empty_description') }}
                </flux:callout>
            @else
                <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($lines as $line)
                        <li wire:key="temp-invoice-{{ $line['id'] }}-{{ $line['quantity'] }}" class="flex gap-3 py-3">
                            <div class="shrink-0">
                                @if ($line['thumbnail'])
                                    <img
                                        src="data:image/jpeg;base64,{{ $line['thumbnail'] }}"
                                        alt="{{ $line['title'] }}"
                                        class="size-12 rounded-md object-cover shadow-sm"
                                    >
                                @else
                                    <div class="flex size-12 items-center justify-center rounded-md bg-zinc-100 dark:bg-zinc-800">
                                        <flux:icon name="package" class="size-5 text-zinc-400" />
                                    </div>
                                @endif
                            </div>

                            <div class="min-w-0 flex-1 space-y-2">
                                <div>
                                    <div class="truncate font-medium text-zinc-900 dark:text-zinc-100">{{ $line['title'] }}</div>
                                    <div class="text-xs text-zinc-500">{{ $line['code'] }}</div>
                                </div>

                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-1">
                                        <flux:tooltip content="{{ __('app.decrease_quantity') }}">
                                            <flux:button
                                                size="xs"
                                                variant="filled"
                                                color="zinc"
                                                icon="minus"
                                                wire:click="decrement({{ $line['id'] }})"
                                            />
                                        </flux:tooltip>
                                        <span class="min-w-8 text-center text-sm font-semibold tabular-nums">{{ $line['quantity'] }}</span>
                                        <flux:tooltip content="{{ __('app.increase_quantity') }}">
                                            <flux:button
                                                size="xs"
                                                variant="filled"
                                                color="zinc"
                                                icon="plus"
                                                wire:click="increment({{ $line['id'] }})"
                                            />
                                        </flux:tooltip>
                                    </div>

                                    <flux:tooltip content="{{ __('app.remove_item') }}">
                                        <flux:button
                                            size="xs"
                                            variant="primary"
                                            color="red"
                                            icon="trash"
                                            icon:variant="outline"
                                            wire:click="remove({{ $line['id'] }})"
                                        />
                                    </flux:tooltip>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <flux:button
                    variant="primary"
                    color="teal"
                    class="w-full"
                    icon="file-text"
                    wire:click="createInvoice"
                >
                    {{ __('app.create_invoice_from_temporary') }}
                </flux:button>
            @endif
        </div>
    </flux:modal>
</div>
