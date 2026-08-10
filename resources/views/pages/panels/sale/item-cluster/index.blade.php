<?php

use App\Models\Sepidar\Local\INV\Cluster;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

return new #[Layout('layouts.panels.sale')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        $this->authorize('sales_item_cluster_index');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[On('panels.sale.item-cluster.index.render')]
    public function refresh(): void
    {
        unset($this->clusters);
    }

    #[Computed]
    public function clusters()
    {
        return Cluster::query()
            ->when($this->search !== '', function ($query) {
                $search = $this->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(20);
    }

    public function delete(int $id): void
    {
        $this->authorize('sales_item_cluster_delete');

        Cluster::query()->findOrFail($id)->delete();

        Flux::toast(__('app.deleted_successfully', ['name' => __('app.item_cluster')]));
        unset($this->clusters);
    }
};
?>

<x-slot name="title">
    {{ __('app.sales') }} — {{ __('app.item_clusters') }}
</x-slot>

<div>
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.item_clusters') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.item_clusters_description') }}</flux:subheading>
            </div>
            @can('sales_item_cluster_create')
                <flux:button
                    variant="primary"
                    color="teal"
                    icon="layers"
                    href="{{ route('panels.sale.item-cluster.create') }}"
                    wire:navigate
                >
                    {{ __('app.create_item_cluster') }}
                </flux:button>
            @endcan
        </div>
        <flux:separator variant="subtle" />
    </div>

    <flux:table :paginate="$this->clusters">
        <flux:table.columns sticky class="bg-white dark:bg-zinc-900">
            <flux:table.column colspan="5" class="bg-white dark:bg-zinc-900">
                <div class="flex flex-col gap-1 pe-2 items-end">
                    <flux:input
                        size="sm"
                        placeholder="{{ __('app.search_placeholder') }}"
                        wire:model.live="search"
                    />
                </div>
            </flux:table.column>
        </flux:table.columns>
        <flux:table.columns>
            <flux:table.column>{{ __('app.id') }}</flux:table.column>
            <flux:table.column>{{ __('app.title') }}</flux:table.column>
            <flux:table.column>{{ __('app.items') }}</flux:table.column>
            <flux:table.column>{{ __('app.active') }}</flux:table.column>
            <flux:table.column>{{ __('app.options') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->clusters as $cluster)
                <flux:table.row :key="$cluster->id">
                    <flux:table.cell>{{ $cluster->id }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="font-medium">{{ $cluster->title }}</div>
                        @if ($cluster->description)
                            <div class="text-xs text-zinc-500 line-clamp-1">{{ $cluster->description }}</div>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ __('app.item_cluster_items_count', [
                            'available' => number_format($cluster->availableItemCount()),
                            'total' => number_format($cluster->itemCount()),
                        ]) }}
                    </flux:table.cell>
                    <flux:table.cell>
                        @if ($cluster->is_active)
                            <flux:badge color="green" size="sm">{{ __('app.active') }}</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">{{ __('app.inactive') }}</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">
                        @can('sales_item_cluster_edit')
                            <flux:tooltip content="{{ __('app.edit') }}">
                                <flux:button
                                    size="xs"
                                    variant="primary"
                                    color="orange"
                                    icon="pencil"
                                    icon:variant="outline"
                                    href="{{ route('panels.sale.item-cluster.edit', $cluster) }}"
                                    wire:navigate
                                />
                            </flux:tooltip>
                        @endcan
                        @can('sales_item_cluster_delete')
                            <flux:tooltip content="{{ __('app.delete') }}">
                                <flux:button
                                    size="xs"
                                    variant="primary"
                                    color="red"
                                    icon="trash"
                                    icon:variant="outline"
                                    wire:click="delete({{ $cluster->id }})"
                                    wire:confirm="{{ __('app.are_you_sure') }}"
                                />
                            </flux:tooltip>
                        @endcan
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center text-zinc-500">
                        {{ __('app.no_results') }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
