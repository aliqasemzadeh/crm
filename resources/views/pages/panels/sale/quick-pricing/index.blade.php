<?php

use App\Models\Sepidar\Local\INV\Cluster;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

return new #[Layout('layouts.panels.sale')] class extends Component
{
    public array $clusterIds = [];

    public function mount(): void
    {
        $this->authorize('sales_quick_pricing_index');
    }

    public function updatedClusterIds(): void
    {
        unset($this->selectedClusters);
    }

    public function saveAll(): void
    {
        $this->authorize('sales_item_fee');
        $this->dispatch('panels.sale.quick-pricing.save-all');
        Flux::toast(__('app.saved_successfully', ['name' => __('app.quick_pricing')]));
    }

    #[Computed]
    public function clusters()
    {
        return Cluster::query()
            ->where('is_active', true)
            ->orderBy('title')
            ->get(['id', 'title']);
    }

    #[Computed]
    public function selectedClusters()
    {
        $ids = collect($this->clusterIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $byId = $this->clusters->keyBy('id');

        return $ids
            ->map(fn (int $id) => $byId->get($id))
            ->filter()
            ->values();
    }
};
?>

<x-slot name="title">
    {{ __('app.sales') }} — {{ __('app.quick_pricing') }}
</x-slot>

<div>
    <div class="relative mb-6 w-full">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.quick_pricing') }}</flux:heading>
                <flux:subheading size="lg">{{ __('app.quick_pricing_description') }}</flux:subheading>
            </div>

            <div class="w-full max-w-md">
                <flux:pillbox
                    wire:model.live="clusterIds"
                    multiple
                    searchable
                    label="{{ __('app.item_clusters') }}"
                    placeholder="{{ __('app.select_item_cluster') }}"
                >
                    @foreach ($this->clusters as $clusterOption)
                        <flux:pillbox.option value="{{ $clusterOption->id }}">
                            {{ $clusterOption->title }}
                        </flux:pillbox.option>
                    @endforeach
                </flux:pillbox>
            </div>
        </div>
        <flux:separator variant="subtle" class="mt-6" />
    </div>

    @if ($this->selectedClusters->isNotEmpty())
        <div class="mb-4 flex items-center justify-end gap-3">
            @can('sales_item_fee')
                <flux:button variant="primary" color="orange" icon="save" wire:click="saveAll">
                    {{ __('app.save_all') }}
                </flux:button>
            @endcan
        </div>

        <div class="space-y-4">
            @foreach ($this->selectedClusters as $cluster)
                <livewire:sale.quick-pricing.cluster-table
                    :cluster-id="$cluster->id"
                    :cluster-title="$cluster->title"
                    :key="'quick-pricing-cluster-'.$cluster->id"
                    lazy
                />
            @endforeach
        </div>
    @else
        <flux:card class="py-10 text-center text-zinc-500">
            {{ __('app.select_item_cluster_to_price') }}
        </flux:card>
    @endif
</div>
