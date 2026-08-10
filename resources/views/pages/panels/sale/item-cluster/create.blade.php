<?php

use App\Livewire\Forms\Sale\ItemClusterForm;
use App\Models\Sepidar\INV\Item;
use App\Models\Sepidar\Local\INV\Cluster;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

return new #[Layout('layouts.panels.sale')] class extends Component
{
    public ItemClusterForm $form;

    public string $search = '';

    public mixed $selectedItemId = null;

    /** @var array<int, array{id: int, title: string, code: string}> */
    public array $selectedItems = [];

    public function mount(): void
    {
        $this->authorize('sales_item_cluster_create');

        $this->form->reset();
        $this->form->is_active = true;
        $this->form->item_refs = [];

        $pending = session()->pull('sale.item_cluster.pending_item_refs', []);
        $refs = collect($pending)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->form->item_refs = $refs;
        $this->hydrateSelectedItems();
    }

    public function updatedSelectedItemId(mixed $value): void
    {
        if (! $value) {
            return;
        }

        $itemId = (int) $value;
        if ($itemId < 1 || in_array($itemId, $this->form->item_refs, true)) {
            $this->selectedItemId = null;

            return;
        }

        $item = Item::query()->where('ItemID', $itemId)->first(['ItemID', 'Title', 'Code']);
        if (! $item) {
            $this->selectedItemId = null;

            return;
        }

        $this->form->item_refs[] = $itemId;
        $this->selectedItems[$itemId] = [
            'id' => $itemId,
            'title' => (string) $item->Title,
            'code' => (string) $item->Code,
        ];

        $this->selectedItemId = null;
        $this->search = '';
        unset($this->searchItems);
    }

    public function removeItem(int $itemId): void
    {
        $this->form->item_refs = array_values(array_filter(
            $this->form->item_refs,
            fn ($id) => (int) $id !== $itemId
        ));
        unset($this->selectedItems[$itemId]);
    }

    public function save()
    {
        $this->authorize('sales_item_cluster_create');

        $this->form->validate();
        $payload = $this->form->toPayload();
        $payload['created_by'] = auth()->id();

        $cluster = Cluster::query()->create($payload);

        Flux::toast(__('app.saved_successfully', ['name' => __('app.item_cluster')]));

        return $this->redirect(route('panels.sale.item-cluster.edit', $cluster), navigate: true);
    }

    #[Computed]
    public function searchItems()
    {
        if (mb_strlen($this->search) < 2) {
            return collect();
        }

        $selected = $this->form->item_refs;

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

    private function hydrateSelectedItems(): void
    {
        $this->selectedItems = [];

        if ($this->form->item_refs === []) {
            return;
        }

        $items = Item::query()
            ->whereIn('ItemID', $this->form->item_refs)
            ->get(['ItemID', 'Title', 'Code'])
            ->keyBy('ItemID');

        foreach ($this->form->item_refs as $itemId) {
            $item = $items->get($itemId);
            $this->selectedItems[$itemId] = [
                'id' => $itemId,
                'title' => (string) ($item?->Title ?? __('app.not_specified')),
                'code' => (string) ($item?->Code ?? ''),
            ];
        }
    }
};
?>

<x-slot name="title">
    {{ __('app.sales') }} — {{ __('app.create_item_cluster') }}
</x-slot>

<div>
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between gap-3">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.create_item_cluster') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.item_clusters_description') }}</flux:subheading>
            </div>
            <flux:button variant="ghost" href="{{ route('panels.sale.item-cluster.index') }}" wire:navigate>
                {{ __('app.back') }}
            </flux:button>
        </div>
        <flux:separator variant="subtle" />
    </div>

    <form wire:submit="save" class="space-y-6 max-w-4xl">
        <flux:card class="space-y-4">
            <flux:input wire:model="form.title" label="{{ __('app.title') }}" />
            <flux:textarea wire:model="form.description" label="{{ __('app.description') }}" rows="3" />
            <flux:field variant="inline">
                <flux:label>{{ __('app.active') }}</flux:label>
                <flux:switch wire:model="form.is_active" />
                <flux:error name="form.is_active" />
            </flux:field>
        </flux:card>

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
                    <flux:select.option value="{{ $item->ItemID }}" wire:key="cluster-create-search-{{ $item->ItemID }}">
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
            <flux:error name="form.item_refs" />

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('app.code') }}</flux:table.column>
                    <flux:table.column>{{ __('app.title') }}</flux:table.column>
                    <flux:table.column>{{ __('app.options') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($selectedItems as $selected)
                        <flux:table.row wire:key="cluster-create-row-{{ $selected['id'] }}">
                            <flux:table.cell>{{ $selected['code'] }}</flux:table.cell>
                            <flux:table.cell>{{ $selected['title'] }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:tooltip content="{{ __('app.delete') }}">
                                    <flux:button
                                        type="button"
                                        size="xs"
                                        variant="primary"
                                        color="red"
                                        icon="trash"
                                        icon:variant="outline"
                                        wire:click="removeItem({{ $selected['id'] }})"
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

        <flux:button type="submit" variant="primary" color="orange" class="w-full md:w-auto">
            {{ __('app.save') }}
        </flux:button>
    </form>
</div>
