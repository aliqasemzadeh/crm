<?php

use App\Livewire\Forms\Accounting\InvoiceForm;
use App\Models\Sepidar\GNR\Party;
use App\Models\Sepidar\INV\Item;
use App\Models\Sepidar\SLS\PriceNoteItem;
use App\Services\Sepidar\InvoiceCreator;
use Flux\Flux;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

new #[Layout('layouts.panels.accounting')] class extends Component
{
    public InvoiceForm $form;

    public string $partySearch = '';

    public string $itemSearch = '';

    public int $itemSearchRow = -1;

    public function mount(): void
    {
        $this->authorize('accounting_invoice_create');

        $this->form->date = Jalalian::now()->format('Y/m/d');
        $this->form->sale_type_ref = 2;
        $this->form->items = [
            $this->emptyRow(),
        ];
    }

    public function addRow(): void
    {
        $this->form->items[] = $this->emptyRow();
    }

    public function removeRow(int $index): void
    {
        if (count($this->form->items) <= 1) {
            return;
        }

        unset($this->form->items[$index]);
        $this->form->items = array_values($this->form->items);
    }

    public function updatedFormCustomerPartyRef(): void
    {
        $this->partySearch = '';
        unset($this->selectedParty, $this->partyResults);
    }

    public function updatedFormItems(mixed $value, string $key): void
    {
        if (! str_ends_with($key, '.item_ref')) {
            return;
        }

        $index = (int) Str::before($key, '.');
        $itemRef = (int) ($this->form->items[$index]['item_ref'] ?? 0);

        if ($itemRef <= 0) {
            return;
        }

        $fee = PriceNoteItem::query()
            ->where('ItemRef', $itemRef)
            ->where('SaleTypeRef', $this->form->sale_type_ref)
            ->value('Fee');

        if ($fee === null) {
            $fee = PriceNoteItem::query()
                ->where('ItemRef', $itemRef)
                ->value('Fee');
        }

        if ($fee === null) {
            $item = Item::query()->find($itemRef);
            $fee = $item?->getLastSalePrice() ?? 0;
        }

        $this->form->items[$index]['fee'] = (int) $fee;
        $this->itemSearch = '';
        $this->itemSearchRow = -1;
        unset($this->itemResults, $this->selectedItems);
    }

    public function openItemSearch(int $index): void
    {
        $this->itemSearchRow = $index;
        $this->itemSearch = '';
        unset($this->itemResults);
    }

    public function clearParty(): void
    {
        $this->form->customer_party_ref = null;
        $this->partySearch = '';
        unset($this->selectedParty, $this->partyResults);
    }

    #[Computed]
    public function partyResults()
    {
        $term = trim($this->partySearch);

        if (Str::length($term) < 2) {
            return collect();
        }

        return Party::query()
            ->select(['PartyId', 'Name', 'LastName', 'Name_En', 'LastName_En'])
            ->where(function ($query) use ($term) {
                $query->where('Name', 'like', '%'.$term.'%')
                    ->orWhere('LastName', 'like', '%'.$term.'%')
                    ->orWhere('Name_En', 'like', '%'.$term.'%')
                    ->orWhere('LastName_En', 'like', '%'.$term.'%')
                    ->orWhereRaw("(ISNULL([Name], '') + ' ' + ISNULL([LastName], '')) LIKE ?", ['%'.$term.'%']);
            })
            ->orderBy('PartyId')
            ->limit(25)
            ->get();
    }

    #[Computed]
    public function selectedParty(): ?Party
    {
        if (! $this->form->customer_party_ref) {
            return null;
        }

        return Party::query()
            ->select(['PartyId', 'Name', 'LastName', 'Name_En', 'LastName_En'])
            ->find($this->form->customer_party_ref);
    }

    #[Computed]
    public function itemResults()
    {
        $term = trim($this->itemSearch);

        if (Str::length($term) < 2) {
            return collect();
        }

        return Item::query()
            ->select(['ItemID', 'Title', 'Code', 'IranCode'])
            ->where(function ($query) use ($term) {
                $query->where('Title', 'like', '%'.$term.'%')
                    ->orWhere('Code', 'like', '%'.$term.'%')
                    ->orWhere('IranCode', 'like', '%'.$term.'%');
            })
            ->orderBy('Title')
            ->limit(25)
            ->get();
    }

    #[Computed]
    public function selectedItems(): array
    {
        $ids = collect($this->form->items)
            ->pluck('item_ref')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return [];
        }

        return Item::query()
            ->select(['ItemID', 'Title', 'Code'])
            ->whereIn('ItemID', $ids)
            ->get()
            ->keyBy('ItemID')
            ->all();
    }

    #[Computed]
    public function totals(): array
    {
        $price = 0;
        $discount = 0;

        foreach ($this->form->items as $row) {
            $qty = (float) str_replace(',', '', (string) ($row['quantity'] ?? 0));
            $fee = (float) str_replace(',', '', (string) ($row['fee'] ?? 0));
            $rowDiscount = (float) str_replace(',', '', (string) ($row['discount'] ?? 0));
            $price += $qty * $fee;
            $discount += $rowDiscount;
        }

        return [
            'price' => $price,
            'discount' => $discount,
            'net' => $price - $discount,
        ];
    }

    public function save(InvoiceCreator $creator): void
    {
        $this->authorize('accounting_invoice_create');

        $this->form->validate();

        $payload = [
            'customer_party_ref' => (int) $this->form->customer_party_ref,
            'sale_type_ref' => (int) $this->form->sale_type_ref,
            'date' => $this->form->date,
            'description' => $this->form->description !== '' ? $this->form->description : null,
            'items' => collect($this->form->items)->map(function (array $row) {
                return [
                    'item_ref' => (int) $row['item_ref'],
                    'quantity' => (float) str_replace(',', '', (string) $row['quantity']),
                    'fee' => (float) str_replace(',', '', (string) $row['fee']),
                    'discount' => (float) str_replace(',', '', (string) ($row['discount'] ?? 0)),
                    'description' => ($row['description'] ?? '') !== '' ? $row['description'] : null,
                ];
            })->all(),
        ];

        try {
            $invoice = $creator->create($payload);
        } catch (\Throwable $e) {
            report($e);
            Flux::toast(__('app.invoice_create_failed'), variant: 'danger');

            return;
        }

        Flux::toast(__('app.invoice_created', ['number' => $invoice->Number]));

        $this->redirect(route('panels.accounting.invoice.index'), navigate: true);
    }

    private function emptyRow(): array
    {
        return [
            'item_ref' => null,
            'quantity' => 1,
            'fee' => 0,
            'discount' => 0,
            'description' => '',
        ];
    }

    public function partyName(?Party $party): string
    {
        if (! $party) {
            return '-';
        }

        return trim(implode(' ', array_filter([
            trim((string) ($party->Name ?? '')),
            trim((string) ($party->LastName ?? '')),
        ], static fn (string $part): bool => $part !== '')));
    }
};
?>

<x-slot name="title">
    {{ __('app.create_invoice') }}
</x-slot>

<div>
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between gap-4">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.create_invoice') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.invoice_create_description') }}</flux:subheading>
            </div>

            <flux:button variant="ghost" href="{{ route('panels.accounting.invoice.index') }}" wire:navigate icon="arrow-right">
                {{ __('app.back') }}
            </flux:button>
        </div>

        <flux:separator variant="subtle" />
    </div>

    <form wire:submit="save" class="space-y-6">
        <flux:card class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <flux:label>{{ __('app.customer') }}</flux:label>

                    @if ($this->selectedParty)
                        <div class="flex items-center justify-between gap-2 rounded-lg border border-zinc-200 dark:border-zinc-700 px-3 py-2">
                            <flux:text>{{ $this->partyName($this->selectedParty) }}</flux:text>
                            <flux:button type="button" size="xs" variant="ghost" color="red" icon="x-mark" wire:click="clearParty" />
                        </div>
                    @else
                        <flux:select variant="combobox" :filter="false" wire:model.live="form.customer_party_ref" placeholder="{{ __('app.search_customer') }}">
                            <x-slot name="input">
                                <flux:select.input wire:model.live.debounce.400ms="partySearch" placeholder="{{ __('app.search_customer') }}" />
                            </x-slot>

                            @foreach ($this->partyResults as $party)
                                <flux:select.option value="{{ $party->PartyId }}" wire:key="party-{{ $party->PartyId }}">
                                    {{ $this->partyName($party) }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    @endif

                    @error('form.customer_party_ref')
                        <flux:text class="text-red-500">{{ $message }}</flux:text>
                    @enderror
                </div>

                <flux:select wire:model="form.sale_type_ref" label="{{ __('app.sale_type') }}" searchable>
                    <flux:select.option value="1">{{ __('app.official') }}</flux:select.option>
                    <flux:select.option value="2">{{ __('app.unofficial') }}</flux:select.option>
                </flux:select>

                <x-date-select wire:model="form.date" :label="__('app.date')" :required="true" />

                <flux:textarea wire:model="form.description" label="{{ __('app.description') }}" rows="3" class="md:col-span-1" />
            </div>
        </flux:card>

        <flux:card class="space-y-4">
            <div class="flex items-center justify-between gap-4">
                <flux:heading size="lg">{{ __('app.items') }}</flux:heading>

                <flux:tooltip content="{{ __('app.add_invoice_item') }}">
                    <flux:button type="button" size="xs" variant="primary" color="teal" icon="plus" icon:variant="outline" wire:click="addRow" />
                </flux:tooltip>
            </div>

            <div class="space-y-4">
                @foreach ($form->items as $index => $row)
                    @php
                        $selectedItem = $row['item_ref'] ? ($this->selectedItems[$row['item_ref']] ?? null) : null;
                        $qty = (float) str_replace(',', '', (string) ($row['quantity'] ?? 0));
                        $fee = (float) str_replace(',', '', (string) ($row['fee'] ?? 0));
                        $discount = (float) str_replace(',', '', (string) ($row['discount'] ?? 0));
                        $lineTotal = ($qty * $fee) - $discount;
                    @endphp

                    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 space-y-3" wire:key="invoice-row-{{ $index }}">
                        <div class="flex items-center justify-between gap-2">
                            <flux:text class="font-medium">#{{ $index + 1 }}</flux:text>

                            <flux:tooltip content="{{ __('app.remove') }}">
                                <flux:button
                                    type="button"
                                    size="xs"
                                    variant="primary"
                                    color="red"
                                    icon="trash"
                                    icon:variant="outline"
                                    wire:click="removeRow({{ $index }})"
                                    :disabled="count($form->items) <= 1"
                                />
                            </flux:tooltip>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
                            <div class="md:col-span-2 space-y-2">
                                <flux:label>{{ __('app.item') }}</flux:label>

                                @if ($selectedItem)
                                    <div class="flex items-center justify-between gap-2 rounded-lg border border-zinc-200 dark:border-zinc-700 px-3 py-2">
                                        <flux:text>{{ $selectedItem->Title }}</flux:text>
                                        <flux:button type="button" size="xs" variant="ghost" wire:click="openItemSearch({{ $index }})">
                                            {{ __('app.change') ?? 'تغییر' }}
                                        </flux:button>
                                    </div>
                                @endif

                                @if (! $selectedItem || $itemSearchRow === $index)
                                    <flux:select variant="combobox" :filter="false" wire:model.live="form.items.{{ $index }}.item_ref" placeholder="{{ __('app.search_item') }}">
                                        <x-slot name="input">
                                            <flux:select.input
                                                wire:model.live.debounce.400ms="itemSearch"
                                                wire:focus="openItemSearch({{ $index }})"
                                                placeholder="{{ __('app.search_item') }}"
                                            />
                                        </x-slot>

                                        @foreach ($this->itemResults as $item)
                                            <flux:select.option value="{{ $item->ItemID }}" wire:key="item-{{ $index }}-{{ $item->ItemID }}">
                                                {{ $item->Title }} @if($item->Code) ({{ $item->Code }}) @endif
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>
                                @endif

                                @error("form.items.$index.item_ref")
                                    <flux:text class="text-red-500">{{ $message }}</flux:text>
                                @enderror
                            </div>

                            <flux:input wire:model.blur="form.items.{{ $index }}.quantity" label="{{ __('app.quantity') }}" type="number" step="any" min="0" />
                            <flux:input wire:model.blur="form.items.{{ $index }}.fee" label="{{ __('app.fee') }}" type="number" step="any" min="0" />
                            <flux:input wire:model.blur="form.items.{{ $index }}.discount" label="{{ __('app.discount') }}" type="number" step="any" min="0" />
                            <div class="flex flex-col justify-end">
                                <flux:text size="sm">{{ __('app.line_total') }}</flux:text>
                                <flux:heading size="sm">{{ number_format($lineTotal) }}</flux:heading>
                            </div>
                        </div>

                        <flux:input wire:model.blur="form.items.{{ $index }}.description" label="{{ __('app.description') }}" />
                    </div>
                @endforeach
            </div>

            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 border-t border-zinc-200 dark:border-zinc-700 pt-4">
                <div class="space-y-1">
                    <flux:text>{{ __('app.price') }}: {{ number_format($this->totals['price']) }}</flux:text>
                    <flux:text>{{ __('app.discount') }}: {{ number_format($this->totals['discount']) }}</flux:text>
                    <flux:heading size="lg">{{ __('app.net_amount') }}: {{ number_format($this->totals['net']) }}</flux:heading>
                </div>

                <flux:button type="submit" variant="primary" color="orange" class="w-full md:w-auto" icon="save" wire:loading.attr="disabled">
                    {{ __('app.save') }}
                </flux:button>
            </div>
        </flux:card>
    </form>
</div>
