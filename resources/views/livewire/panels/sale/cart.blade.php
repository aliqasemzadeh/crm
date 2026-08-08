<?php

use App\Models\Sepidar\INV\Item;
use App\Services\Sale\SaleCart;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    #[On('panels.sale.cart.updated')]
    public function refreshCart(): void
    {
        unset($this->lines, $this->count);
    }

    public function open(SaleCart $cart): void
    {
        $this->authorize('sales_invoice_create');

        unset($this->lines, $this->count);
        Flux::modal('panels.sale.cart.modal')->show();
    }

    public function increment(int $itemId, SaleCart $cart): void
    {
        $this->authorize('sales_invoice_create');

        $cart->increment($itemId);
        unset($this->lines, $this->count);
        $this->dispatch('panels.sale.cart.updated');
        Flux::toast(__('app.quantity_increased'));
    }

    public function decrement(int $itemId, SaleCart $cart): void
    {
        $this->authorize('sales_invoice_create');

        $cart->decrement($itemId);
        unset($this->lines, $this->count);
        $this->dispatch('panels.sale.cart.updated');
        Flux::toast(__('app.quantity_decreased'));
    }

    public function remove(int $itemId, SaleCart $cart): void
    {
        $this->authorize('sales_invoice_create');

        $cart->remove($itemId);
        unset($this->lines, $this->count);
        $this->dispatch('panels.sale.cart.updated');
        Flux::toast(__('app.item_removed_from_cart'));
    }

    public function clear(SaleCart $cart): void
    {
        $this->authorize('sales_invoice_create');

        $cart->clear();
        unset($this->lines, $this->count);
        $this->dispatch('panels.sale.cart.updated');
        Flux::toast(__('app.cart_is_empty'));
    }

    public function createInvoice(SaleCart $cart): void
    {
        $this->authorize('sales_invoice_create');

        if ($cart->isEmpty()) {
            Flux::toast(__('app.cart_is_empty'), variant: 'danger');

            return;
        }

        Flux::modal('panels.sale.cart.modal')->close();

        $this->redirect(route('panels.sale.invoice.create'), navigate: true);
    }

    #[Computed]
    public function count(): int
    {
        return app(SaleCart::class)->count();
    }

    #[Computed]
    public function lines()
    {
        $cart = app(SaleCart::class);
        $quantities = $cart->all();

        if ($quantities === []) {
            return collect();
        }

        $items = Item::query()
            ->with('image')
            ->whereIn('ItemID', array_keys($quantities))
            ->get()
            ->keyBy('ItemID');

        return collect($quantities)
            ->map(function (int $quantity, int $itemId) use ($items) {
                $item = $items->get($itemId);

                if (! $item) {
                    return null;
                }

                return [
                    'id' => $itemId,
                    'title' => $item->Title,
                    'code' => $item->Code,
                    'quantity' => $quantity,
                    'thumbnail' => $item->image?->Thumbnail,
                ];
            })
            ->filter()
            ->values();
    }
};
?>

<div class="shrink-0">
    <flux:tooltip content="{{ __('app.shopping_cart') }}">
        <flux:button
            variant="filled"
            color="teal"
            icon="shopping-cart"
            wire:click="open"
            class="relative"
        >
            @if ($this->count > 0)
                <span class="absolute -top-1.5 -left-1.5 inline-flex min-w-5 items-center justify-center rounded-full bg-rose-600 px-1.5 py-0.5 text-[10px] font-bold text-white">
                    {{ $this->count }}
                </span>
            @endif
        </flux:button>
    </flux:tooltip>

    <flux:modal name="panels.sale.cart.modal" class="md:w-96" flyout position="right">
        <div class="space-y-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <flux:heading size="lg">{{ __('app.shopping_cart') }}</flux:heading>
                    <flux:text class="mt-2">{{ __('app.shopping_cart_description') }}</flux:text>
                </div>
                @if ($this->count > 0)
                    <flux:tooltip content="{{ __('app.clear') }}">
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

            @if ($this->lines->isEmpty())
                <flux:callout variant="secondary" icon="shopping-cart">
                    {{ __('app.cart_is_empty_description') }}
                </flux:callout>
            @else
                <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($this->lines as $line)
                        <li wire:key="sale-cart-{{ $line['id'] }}" class="flex gap-3 py-3">
                            <div class="shrink-0">
                                @if ($line['thumbnail'])
                                    <img
                                        src="data:image/jpeg;base64,{{ base64_encode($line['thumbnail']) }}"
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
                    {{ __('app.create_invoice_from_cart') }}
                </flux:button>
            @endif
        </div>
    </flux:modal>
</div>
