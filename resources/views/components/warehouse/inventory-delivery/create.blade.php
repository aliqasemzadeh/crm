<?php

use App\Livewire\Forms\Warehouse\InventoryDeliveryForm;
use App\Models\Sepidar\GNR\Party;
use App\Models\Sepidar\INV\Item;
use App\Models\Sepidar\INV\ItemStockSummary;
use App\Services\Sepidar\InventoryDeliveryService;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public InventoryDeliveryForm $form;

    public string $itemSearch = '';

    public string $partySearch = '';

    public function mount(): void
    {
        $this->form->ensureDefaults();
    }

    public function addRow(): void
    {
        $this->form->items[] = $this->form->emptyRow();
    }

    public function removeRow(int $index): void
    {
        unset($this->form->items[$index]);
        $this->form->items = array_values($this->form->items);

        if ($this->form->items === []) {
            $this->form->items = [$this->form->emptyRow()];
        }
    }

    public function save(InventoryDeliveryService $service): void
    {
        $this->authorize('warehouse_inventory_delivery_create');

        $this->form->validate();

        try {
            $delivery = $service->create($this->form->payload());
        } catch (\Throwable $e) {
            report($e);
            Flux::toast(__('app.warehouse_delivery_create_failed'), variant: 'danger');

            return;
        }

        $this->form->reset();
        $this->form->ensureDefaults();
        $this->itemSearch = '';
        $this->partySearch = '';
        unset($this->itemResults, $this->partyResults, $this->selectedItems, $this->selectedParty, $this->stocks);

        $this->dispatch('panels.warehouse.inventory-delivery.index.render');
        Flux::modal('panels.warehouse.inventory-delivery.create.modal')->close();
        Flux::toast(__('app.warehouse_delivery_created', ['number' => $delivery->Number]));
    }

    #[Computed]
    public function stocks(): Collection
    {
        $fiscalYearRef = (int) config('sepidar.FiscalYearRef');
        $defaultStock = (int) config('sepidar.DefaultStockRef', 2);

        $refs = ItemStockSummary::query()
            ->where('FiscalYearRef', $fiscalYearRef)
            ->distinct()
            ->orderBy('StockRef')
            ->pluck('StockRef')
            ->map(fn ($ref) => (int) $ref)
            ->filter()
            ->values();

        if (! $refs->contains($defaultStock)) {
            $refs->prepend($defaultStock);
        }

        $titles = collect();

        try {
            $titles = collect(DB::connection('sqlsrv')->select(
                'SELECT StockID, Title FROM INV.Stock WHERE StockID IN ('.implode(',', $refs->all() ?: [0]).')'
            ))->pluck('Title', 'StockID');
        } catch (\Throwable) {
            // Stock table may be unavailable; fall back to numeric labels.
        }

        return $refs->mapWithKeys(function (int $ref) use ($titles) {
            $title = trim((string) ($titles[$ref] ?? ''));

            return [$ref => $title !== '' ? $title : __('app.warehouse_stock_label', ['id' => $ref])];
        });
    }

    #[Computed]
    public function itemResults(): Collection
    {
        $term = trim($this->itemSearch);

        if (Str::length($term) < 2) {
            return collect();
        }

        return Item::query()
            ->select(['ItemID', 'Title', 'Code'])
            ->where(function ($query) use ($term) {
                $query->where('Title', 'like', '%'.$term.'%')
                    ->orWhere('Code', 'like', '%'.$term.'%');
            })
            ->orderBy('Title')
            ->limit(20)
            ->get();
    }

    #[Computed]
    public function selectedItems(): Collection
    {
        $ids = collect($this->form->items)
            ->pluck('item_ref')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return collect();
        }

        return Item::query()
            ->select(['ItemID', 'Title', 'Code'])
            ->whereIn('ItemID', $ids)
            ->get()
            ->keyBy('ItemID');
    }

    #[Computed]
    public function partyResults(): Collection
    {
        $term = trim($this->partySearch);

        if (Str::length($term) < 3) {
            return collect();
        }

        return Party::query()
            ->select(['PartyId', 'Name', 'LastName'])
            ->whereNotNull('DLRef')
            ->where(function ($query) use ($term) {
                $query->where('Name', 'like', '%'.$term.'%')
                    ->orWhere('LastName', 'like', '%'.$term.'%')
                    ->orWhereRaw("(ISNULL([Name], '') + ' ' + ISNULL([LastName], '')) LIKE ?", ['%'.$term.'%']);
            })
            ->orderBy('PartyId')
            ->limit(25)
            ->get();
    }

    #[Computed]
    public function selectedParty(): ?Party
    {
        if (! $this->form->receiver_party_ref) {
            return null;
        }

        return Party::query()
            ->select(['PartyId', 'Name', 'LastName'])
            ->find($this->form->receiver_party_ref);
    }

    public function partyName(?Party $party): string
    {
        if (! $party) {
            return '';
        }

        return trim(implode(' ', array_filter([
            trim((string) ($party->Name ?? '')),
            trim((string) ($party->LastName ?? '')),
        ], static fn (string $part): bool => $part !== '')));
    }
};
?>

<flux:modal name="panels.warehouse.inventory-delivery.create.modal" flyout position="right" class="md:w-[32rem]">
    <form wire:submit="save" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.create_warehouse_delivery') }}</flux:heading>
            <flux:subheading>{{ __('app.warehouse_delivery_form_description') }}</flux:subheading>
        </div>

        <flux:select wire:model="form.stock_ref" searchable label="{{ __('app.warehouse_stock') }}">
            @foreach ($this->stocks as $stockId => $stockTitle)
                <flux:select.option value="{{ $stockId }}" wire:key="create-stock-{{ $stockId }}">{{ $stockTitle }}</flux:select.option>
            @endforeach
        </flux:select>

        <div class="space-y-2">
            <flux:label>{{ __('app.warehouse_delivery_receiver') }}</flux:label>
            @if ($this->selectedParty)
                <div class="flex items-center justify-between gap-2 rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                    <flux:text>{{ $this->partyName($this->selectedParty) }}</flux:text>
                    <flux:button type="button" size="xs" variant="ghost" color="red" icon="x-mark" wire:click="$set('form.receiver_party_ref', null)" />
                </div>
            @else
                <flux:select variant="combobox" :filter="false" wire:model="form.receiver_party_ref" placeholder="{{ __('app.search_customer') }}">
                    <x-slot name="input">
                        <flux:select.input wire:model.live.debounce.300ms="partySearch" placeholder="{{ __('app.search_customer') }}" />
                    </x-slot>
                    @foreach ($this->partyResults as $party)
                        <flux:select.option value="{{ $party->PartyId }}" wire:key="create-party-{{ $party->PartyId }}">
                            {{ $this->partyName($party) }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            @endif
            <flux:error name="form.receiver_party_ref" />
        </div>

        <x-date-select wire:model="form.date" :label="__('app.date')" :required="true" />

        <flux:textarea wire:model="form.description" label="{{ __('app.description') }}" rows="2" />

        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <flux:heading size="sm">{{ __('app.items') }}</flux:heading>
                <flux:button type="button" size="xs" variant="primary" color="teal" icon="plus" wire:click="addRow">
                    {{ __('app.insert_row') }}
                </flux:button>
            </div>

            @foreach ($form->items as $index => $row)
                <div class="space-y-2 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700" wire:key="create-delivery-row-{{ $index }}">
                    <flux:select
                        variant="combobox"
                        :filter="false"
                        wire:model="form.items.{{ $index }}.item_ref"
                        placeholder="{{ __('app.search_item') }}"
                        label="{{ __('app.item') }}"
                    >
                        <x-slot name="input">
                            <flux:select.input wire:model.live.debounce.300ms="itemSearch" placeholder="{{ __('app.search_item') }}" />
                        </x-slot>

                        @php($selected = $row['item_ref'] ? $this->selectedItems->get((int) $row['item_ref']) : null)
                        @if ($selected)
                            <flux:select.option value="{{ $selected->ItemID }}" wire:key="create-selected-item-{{ $selected->ItemID }}">
                                {{ $selected->Title }} ({{ $selected->Code }})
                            </flux:select.option>
                        @endif

                        @foreach ($this->itemResults as $item)
                            @if (! $selected || (int) $selected->ItemID !== (int) $item->ItemID)
                                <flux:select.option value="{{ $item->ItemID }}" wire:key="create-item-{{ $index }}-{{ $item->ItemID }}">
                                    {{ $item->Title }} ({{ $item->Code }})
                                </flux:select.option>
                            @endif
                        @endforeach
                    </flux:select>

                    <div class="grid grid-cols-2 gap-2">
                        <flux:input type="number" step="any" min="0" wire:model="form.items.{{ $index }}.quantity" label="{{ __('app.quantity') }}" />
                        <div class="flex items-end">
                            <flux:tooltip content="{{ __('app.remove_row') }}">
                                <flux:button type="button" size="sm" variant="primary" color="red" icon="trash" icon:variant="outline" class="w-full" wire:click="removeRow({{ $index }})" />
                            </flux:tooltip>
                        </div>
                    </div>
                    <flux:error name="form.items.{{ $index }}.item_ref" />
                    <flux:error name="form.items.{{ $index }}.quantity" />
                </div>
            @endforeach
            <flux:error name="form.items" />
        </div>

        <flux:button type="submit" variant="primary" color="orange" class="w-full">{{ __('app.save') }}</flux:button>
    </form>
</flux:modal>
