<?php

use App\Livewire\Forms\Sale\ItemClusterForm;
use App\Models\Sepidar\INV\Item;
use App\Models\Sepidar\Local\INV\Cluster;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public ItemClusterForm $form;

    public ?int $clusterId = null;

    public string $search = '';

    public mixed $selectedItemId = null;

    /** @var array<int, array{id: int, title: string, code: string}> */
    public array $selectedItems = [];

    #[On('panels.sale.item-cluster.edit.assign-data')]
    public function assignData(int $id): void
    {
        $this->authorize('sales_item_cluster_edit');

        $cluster = Cluster::query()->findOrFail($id);

        $this->clusterId = $cluster->id;
        $this->form->title = (string) $cluster->title;
        $this->form->description = (string) ($cluster->description ?? '');
        $this->form->is_active = (bool) $cluster->is_active;
        $this->form->item_refs = collect($cluster->item_refs ?? [])
            ->map(fn ($ref) => (int) $ref)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->hydrateSelectedItems();
        $this->search = '';
        $this->selectedItemId = null;
        unset($this->items);

        Flux::modal('panels.sale.item-cluster.edit.modal')->show();
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
        unset($this->items);
    }

    public function removeItem(int $itemId): void
    {
        $this->form->item_refs = array_values(array_filter(
            $this->form->item_refs,
            fn ($id) => (int) $id !== $itemId
        ));
        unset($this->selectedItems[$itemId]);
    }

    public function save(): void
    {
        $this->authorize('sales_item_cluster_edit');

        if (! $this->clusterId) {
            return;
        }

        $this->form->validate();

        Cluster::query()->findOrFail($this->clusterId)->update($this->form->toPayload());

        $this->dispatch('panels.sale.item-cluster.index.render');
        Flux::modal('panels.sale.item-cluster.edit.modal')->close();
        Flux::toast(__('app.saved_successfully', ['name' => __('app.item_cluster')]));
    }

    #[Computed]
    public function items()
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

<flux:modal name="panels.sale.item-cluster.edit.modal" flyout position="right" class="md:w-[32rem]">
    <div class="space-y-6">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('app.edit_item_cluster') }}</flux:heading>
                <flux:subheading>{{ __('app.item_clusters_description') }}</flux:subheading>
            </div>

            <flux:input wire:model="form.title" label="{{ __('app.title') }}" />
            <flux:textarea wire:model="form.description" label="{{ __('app.description') }}" rows="3" />

            <flux:field variant="inline">
                <flux:label>{{ __('app.active') }}</flux:label>
                <flux:switch wire:model="form.is_active" />
                <flux:error name="form.is_active" />
            </flux:field>

            <div class="space-y-2">
                <flux:text size="sm" weight="medium">{{ __('app.items') }}</flux:text>
                <flux:select
                    wire:model.live="selectedItemId"
                    variant="combobox"
                    :filter="false"
                    :placeholder="__('app.search_placeholder')"
                >
                    <x-slot name="input">
                        <flux:select.input wire:model.live.debounce.400ms="search" />
                    </x-slot>

                    @foreach ($this->items as $item)
                        <flux:select.option value="{{ $item->ItemID }}" wire:key="cluster-edit-item-{{ $item->ItemID }}">
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
            </div>

            @if ($selectedItems !== [])
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    @foreach ($selectedItems as $selected)
                        <div class="flex items-center justify-between gap-2 rounded-lg border border-zinc-200 dark:border-zinc-700 px-3 py-2" wire:key="cluster-edit-selected-{{ $selected['id'] }}">
                            <div class="min-w-0">
                                <div class="truncate text-sm">{{ $selected['title'] }}</div>
                                <div class="text-xs text-zinc-500">{{ $selected['code'] }}</div>
                            </div>
                            <flux:button
                                type="button"
                                size="xs"
                                variant="ghost"
                                color="red"
                                icon="x-mark"
                                wire:click="removeItem({{ $selected['id'] }})"
                            />
                        </div>
                    @endforeach
                </div>
            @endif

            <flux:button type="submit" variant="primary" color="orange" class="w-full">
                {{ __('app.save') }}
            </flux:button>
        </form>
    </div>
</flux:modal>
