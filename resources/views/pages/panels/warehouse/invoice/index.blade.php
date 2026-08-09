<?php

use App\Models\Sepidar\INV\InventoryDelivery;
use App\Services\Sepidar\InventoryDeliveryService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

new #[Layout('layouts::panels.warehouse')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        $this->authorize('warehouse_inventory_delivery_index');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[On('panels.warehouse.inventory-delivery.index.render')]
    public function refresh(): void
    {
        unset($this->deliveries);
    }

    #[Computed]
    public function deliveries()
    {
        return InventoryDelivery::query()
            ->standalone()
            ->with(['items.item'])
            ->when($this->search !== '', function ($query) {
                $search = trim($this->search);

                $query->where(function ($q) use ($search) {
                    $q->where('Number', 'like', "%{$search}%")
                        ->orWhere('Description', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('Date')
            ->orderByDesc('InventoryDeliveryID')
            ->paginate(20);
    }

    public function delete(int $id, InventoryDeliveryService $service): void
    {
        $this->authorize('warehouse_inventory_delivery_delete');

        $delivery = InventoryDelivery::query()->standalone()->findOrFail($id);

        try {
            $service->delete((int) $delivery->InventoryDeliveryID);
        } catch (\Throwable $e) {
            report($e);
            Flux::toast(__('app.warehouse_delivery_delete_failed'), variant: 'danger');

            return;
        }

        Flux::toast(__('app.deleted_successfully', ['name' => __('app.warehouse_delivery')]));
        unset($this->deliveries);
    }

    public function formatDate(mixed $date): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format('Y/m/d');
    }
};
?>

<div>
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between gap-4">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.warehouse_deliveries') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.warehouse_deliveries_description') }}</flux:subheading>
            </div>
            @can('warehouse_inventory_delivery_create')
                <flux:modal.trigger name="panels.warehouse.inventory-delivery.create.modal">
                    <flux:button variant="primary" color="teal" icon="truck">{{ __('app.create_warehouse_delivery') }}</flux:button>
                </flux:modal.trigger>
            @endcan
        </div>
        <flux:separator variant="subtle" />
    </div>

    <livewire:warehouse.inventory-delivery.create :key="'warehouse-delivery-create'" />
    <livewire:warehouse.inventory-delivery.edit :key="'warehouse-delivery-edit'" />

    <flux:table :paginate="$this->deliveries">
        <flux:table.columns sticky class="bg-white dark:bg-zinc-900">
            <flux:table.column colspan="6" class="bg-white dark:bg-zinc-900">
                <div class="flex flex-col gap-1 pe-2 items-end">
                    <flux:input
                        size="sm"
                        placeholder="{{ __('app.search_placeholder') }}"
                        wire:model.live.debounce.300ms="search"
                    />
                </div>
            </flux:table.column>
        </flux:table.columns>
        <flux:table.columns>
            <flux:table.column>{{ __('app.number') }}</flux:table.column>
            <flux:table.column>{{ __('app.date') }}</flux:table.column>
            <flux:table.column>{{ __('app.warehouse_stock') }}</flux:table.column>
            <flux:table.column>{{ __('app.items') }}</flux:table.column>
            <flux:table.column>{{ __('app.quantity') }}</flux:table.column>
            <flux:table.column>{{ __('app.options') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->deliveries as $delivery)
                <flux:table.row :key="$delivery->InventoryDeliveryID">
                    <flux:table.cell>{{ $delivery->Number }}</flux:table.cell>
                    <flux:table.cell>{{ $this->formatDate($delivery->Date) }}</flux:table.cell>
                    <flux:table.cell>{{ __('app.warehouse_stock_label', ['id' => $delivery->StockRef]) }}</flux:table.cell>
                    <flux:table.cell>{{ $delivery->items->count() }}</flux:table.cell>
                    <flux:table.cell>{{ number_format((float) $delivery->items->sum('Quantity'), 2) }}</flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">
                        @can('warehouse_inventory_delivery_edit')
                            <flux:tooltip content="{{ __('app.edit') }}">
                                <flux:button
                                    size="xs"
                                    variant="primary"
                                    color="amber"
                                    icon="pencil"
                                    icon:variant="outline"
                                    wire:click="$dispatch('panels.warehouse.inventory-delivery.edit.assign-data', { id: {{ $delivery->InventoryDeliveryID }} })"
                                />
                            </flux:tooltip>
                        @endcan
                        @can('warehouse_inventory_delivery_delete')
                            <flux:tooltip content="{{ __('app.delete') }}">
                                <flux:button
                                    size="xs"
                                    variant="primary"
                                    color="red"
                                    icon="trash"
                                    icon:variant="outline"
                                    wire:click="delete({{ $delivery->InventoryDeliveryID }})"
                                    wire:confirm="{{ __('common.are_you_sure') }}"
                                />
                            </flux:tooltip>
                        @endcan
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center text-zinc-500">
                        {{ __('app.no_results') }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
