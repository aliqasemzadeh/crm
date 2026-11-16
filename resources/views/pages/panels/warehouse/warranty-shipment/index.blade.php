<?php

use App\Enums\WarehouseWarrantyShipmentStatusEnum;
use App\Models\Warehouse\WarrantyShipment;
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

    public string $statusFilter = '';

    public function mount(): void
    {
        $this->authorize('warehouse_warranty_shipment_index');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    #[On('panels.warehouse.warranty-shipment.index.render')]
    public function refresh(): void
    {
        unset($this->shipments);
    }

    #[Computed]
    public function shipments()
    {
        return WarrantyShipment::query()
            ->with('creator')
            ->when($this->search !== '', function ($query) {
                $search = trim($this->search);

                $query->where(function ($q) use ($search) {
                    $q->where('item_title', 'like', "%{$search}%")
                        ->orWhere('warranty_company', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->orderByDesc('sent_at')
            ->orderByDesc('id')
            ->paginate(20);
    }

    #[Computed]
    public function statusOptions(): array
    {
        return WarehouseWarrantyShipmentStatusEnum::options();
    }

    public function delete(int $id): void
    {
        $this->authorize('warehouse_warranty_shipment_delete');

        WarrantyShipment::query()->findOrFail($id)->delete();

        Flux::toast(__('app.deleted_successfully', ['name' => __('app.warehouse_warranty_shipment')]));
        unset($this->shipments);
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
                <flux:heading size="xl" level="1">{{ __('app.warehouse_warranty_shipments') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.warehouse_warranty_shipments_description') }}</flux:subheading>
            </div>
            @can('warehouse_warranty_shipment_create')
                <flux:modal.trigger name="panels.warehouse.warranty-shipment.create.modal">
                    <flux:button variant="primary" color="teal" icon="shield">{{ __('app.create_warehouse_warranty_shipment') }}</flux:button>
                </flux:modal.trigger>
            @endcan
        </div>
        <flux:separator variant="subtle" />
    </div>

    <livewire:warehouse.warranty-shipment.create :key="'warehouse-warranty-shipment-create'" />
    <livewire:warehouse.warranty-shipment.edit :key="'warehouse-warranty-shipment-edit'" />

    <flux:card class="mb-4">
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <flux:input
                size="sm"
                class="md:max-w-xs"
                placeholder="{{ __('app.search_placeholder') }}"
                wire:model.live.debounce.300ms="search"
            />
            <flux:select
                size="sm"
                class="md:max-w-xs"
                wire:model.live="statusFilter"
                placeholder="{{ __('app.warehouse_warranty_filter_status') }}"
                searchable
            >
                <flux:select.option value="">{{ __('app.all') }}</flux:select.option>
                @foreach ($this->statusOptions as $option)
                    <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </flux:card>

    <flux:table :paginate="$this->shipments">
        <flux:table.columns>
            <flux:table.column>{{ __('app.warehouse_warranty_item_title') }}</flux:table.column>
            <flux:table.column>{{ __('app.warehouse_warranty_company') }}</flux:table.column>
            <flux:table.column>{{ __('app.warehouse_warranty_sent_at') }}</flux:table.column>
            <flux:table.column>{{ __('app.warehouse_warranty_received_at') }}</flux:table.column>
            <flux:table.column>{{ __('app.status') }}</flux:table.column>
            <flux:table.column>{{ __('app.description') }}</flux:table.column>
            <flux:table.column>{{ __('app.options') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->shipments as $shipment)
                <flux:table.row :key="$shipment->id">
                    <flux:table.cell>{{ $shipment->item_title }}</flux:table.cell>
                    <flux:table.cell>{{ $shipment->warranty_company }}</flux:table.cell>
                    <flux:table.cell>{{ $this->formatDate($shipment->sent_at) }}</flux:table.cell>
                    <flux:table.cell>{{ $this->formatDate($shipment->received_at) }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="{{ $shipment->status->color() }}" size="sm">
                            {{ $shipment->status->label() }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="max-w-48 truncate" title="{{ $shipment->description }}">
                        {{ $shipment->description ?: '—' }}
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">
                        @can('warehouse_warranty_shipment_edit')
                            <flux:tooltip content="{{ __('app.edit') }}">
                                <flux:button
                                    size="xs"
                                    variant="primary"
                                    color="amber"
                                    icon="pencil"
                                    icon:variant="outline"
                                    wire:click="$dispatch('panels.warehouse.warranty-shipment.edit.assign-data', { id: {{ $shipment->id }} })"
                                />
                            </flux:tooltip>
                        @endcan
                        @can('warehouse_warranty_shipment_delete')
                            <flux:tooltip content="{{ __('app.delete') }}">
                                <flux:button
                                    size="xs"
                                    variant="primary"
                                    color="red"
                                    icon="trash"
                                    icon:variant="outline"
                                    wire:click="delete({{ $shipment->id }})"
                                    wire:confirm="{{ __('app.are_you_sure') }}"
                                />
                            </flux:tooltip>
                        @endcan
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="py-4 text-center text-zinc-500">
                        {{ __('app.no_results') }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
