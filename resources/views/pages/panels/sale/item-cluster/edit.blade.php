<?php

use App\Models\Sepidar\INV\Item;
use App\Models\Sepidar\Local\INV\Cluster;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

return new #[Layout('layouts.panels.sale')] class extends Component
{
    public Cluster $cluster;

    public string $title = '';

    public string $description = '';

    public bool $is_active = true;

    public string $search = '';

    public mixed $selectedItemId = null;

    public function mount(Cluster $cluster): void
    {
        $this->authorize('sales_item_cluster_edit');

        $this->cluster = $cluster;
        $this->title = (string) $cluster->title;
        $this->description = (string) ($cluster->description ?? '');
        $this->is_active = (bool) $cluster->is_active;
    }

    public function saveMeta(): void
    {
        $this->authorize('sales_item_cluster_edit');

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ], [], [
            'title' => __('app.title'),
            'description' => __('app.description'),
            'is_active' => __('app.active'),
        ]);

        $this->cluster->update([
            'title' => trim($validated['title']),
            'description' => trim((string) ($validated['description'] ?? '')) !== ''
                ? trim((string) $validated['description'])
                : null,
            'is_active' => (bool) $validated['is_active'],
        ]);

        Flux::toast(__('app.saved_successfully', ['name' => __('app.item_cluster')]));
    }

    public function updatedSelectedItemId(mixed $value): void
    {
        if (! $value) {
            return;
        }

        $this->authorize('sales_item_cluster_edit');

        $itemId = (int) $value;
        $refs = collect($this->cluster->item_refs ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($itemId < 1 || $refs->contains($itemId)) {
            $this->selectedItemId = null;

            return;
        }

        if (! Item::query()->where('ItemID', $itemId)->exists()) {
            $this->selectedItemId = null;

            return;
        }

        $refs->push($itemId);
        $this->cluster->update(['item_refs' => $refs->all()]);
        $this->cluster->syncAvailableItemRefs();
        $this->cluster->refresh();

        $this->selectedItemId = null;
        $this->search = '';
        unset($this->searchItems, $this->items);

        Flux::toast(__('app.saved_successfully', ['name' => __('app.items')]));
    }

    public function removeItem(int $itemId): void
    {
        $this->authorize('sales_item_cluster_edit');

        $refs = collect($this->cluster->item_refs ?? [])
            ->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => $id === $itemId)
            ->values()
            ->all();

        $this->cluster->update(['item_refs' => $refs]);
        $this->cluster->syncAvailableItemRefs();
        $this->cluster->refresh();
        unset($this->items);

        Flux::toast(__('app.deleted_successfully', ['name' => __('app.items')]));
    }

    #[Computed]
    public function items()
    {
        $refs = collect($this->cluster->item_refs ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($refs->isEmpty()) {
            return collect();
        }

        $items = Item::query()
            ->whereIn('ItemID', $refs->all())
            ->get(['ItemID', 'Title', 'Code'])
            ->keyBy('ItemID');

        return $refs->map(function (int $itemId) use ($items) {
            $item = $items->get($itemId);

            return [
                'id' => $itemId,
                'title' => (string) ($item?->Title ?? __('app.not_specified')),
                'code' => (string) ($item?->Code ?? ''),
            ];
        });
    }

    #[Computed]
    public function searchItems()
    {
        if (mb_strlen($this->search) < 2) {
            return collect();
        }

        $selected = collect($this->cluster->item_refs ?? [])
            ->map(fn ($id) => (int) $id)
            ->all();

        return Item::query()
            ->with('image')
            ->where(function ($query) {
                $query->where('Title', 'like', '%'.$this->search.'%')
                    ->orWhere('Title_En', 'like', '%'.$this->search.'%')
                    ->orWhere('Code', 'like', '%'.$this->search.'%')
                    ->orWhere('IranCode', 'like', '%'.$this->search.'%');
            })
            ->when($selected !== [], fn ($query) => $query->whereNotIn('ItemID', $selected))
            ->orderBy('Title')
            ->limit(15)
            ->get();
    }
};
?>

<x-slot name="title">
    {{ __('app.sales') }} — {{ __('app.edit_item_cluster') }}
</x-slot>

<div>
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between gap-3">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.edit_item_cluster') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ $cluster->title }}</flux:subheading>
            </div>
            <flux:button variant="ghost" href="{{ route('panels.sale.item-cluster.index') }}" wire:navigate>
                {{ __('app.back') }}
            </flux:button>
        </div>
        <flux:separator variant="subtle" />
    </div>

    <div class="space-y-6 max-w-4xl">
        <form wire:submit="saveMeta" class="space-y-4">
            <flux:card class="space-y-4">
                <flux:input wire:model="title" label="{{ __('app.title') }}" />
                <flux:textarea wire:model="description" label="{{ __('app.description') }}" rows="3" />
                <flux:field variant="inline">
                    <flux:label>{{ __('app.active') }}</flux:label>
                    <flux:switch wire:model="is_active" />
                    <flux:error name="is_active" />
                </flux:field>
                <flux:button type="submit" variant="primary" color="orange" class="w-full">
                    {{ __('app.save') }}
                </flux:button>
            </flux:card>
        </form>

        <flux:card class="space-y-4">
            <flux:heading size="lg">{{ __('app.items') }}</flux:heading>

            <flux:select
                wire:model.live="selectedItemId"
                variant="combobox"
                :filter="false"
                :placeholder="__('app.search_placeholder')"
                label="{{ __('app.add_item') }}"
            >
                <x-slot name="input">
                    <flux:select.input wire:model.live.debounce.400ms="search" />
                </x-slot>

                @foreach ($this->searchItems as $item)
                    <flux:select.option value="{{ $item->ItemID }}" wire:key="cluster-edit-search-{{ $item->ItemID }}">
                        <div class="flex items-center gap-2">
                            @if($item->image?->Thumbnail || $item->image?->Image)
                                <img src="data:image/jpeg;base64,{{ base64_encode($item->image?->Thumbnail ?? $item->image?->Image) }}" class="size-6 rounded object-cover" />
                            @else
                                <flux:icon.package variant="mini" class="text-zinc-400" />
                            @endif
                            <div>
                                <div class="text-sm">{{ $item->Title }}</div>
                                <div class="text-xs text-zinc-500">{{ $item->Code }}</div>
                            </div>
                        </div>
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('app.code') }}</flux:table.column>
                    <flux:table.column>{{ __('app.title') }}</flux:table.column>
                    <flux:table.column>{{ __('app.options') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->items as $row)
                        <flux:table.row wire:key="cluster-edit-row-{{ $row['id'] }}">
                            <flux:table.cell>{{ $row['code'] }}</flux:table.cell>
                            <flux:table.cell>{{ $row['title'] }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:tooltip content="{{ __('app.delete') }}">
                                    <flux:button
                                        size="xs"
                                        variant="primary"
                                        color="red"
                                        icon="trash"
                                        icon:variant="outline"
                                        wire:click="removeItem({{ $row['id'] }})"
                                        wire:confirm="{{ __('app.are_you_sure') }}"
                                    />
                                </flux:tooltip>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="3" class="text-center text-zinc-500">
                                {{ __('app.no_results') }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    </div>
</div>
