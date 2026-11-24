<?php

use App\Enums\WarehouseWarrantyShipmentStatusEnum;
use App\Livewire\Forms\Warehouse\WarrantyShipmentForm;
use App\Models\Warehouse\WarrantyShipment;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public WarrantyShipmentForm $form;

    public function mount(): void
    {
        $this->form->ensureDefaults();
    }

    #[Computed]
    public function statusOptions(): array
    {
        return WarehouseWarrantyShipmentStatusEnum::options();
    }

    public function save(): void
    {
        $this->authorize('warehouse_warranty_shipment_create');

        $payload = $this->form->payload();
        $payload['created_by'] = Auth::id();

        WarrantyShipment::query()->create($payload);

        $this->form->reset();
        $this->form->ensureDefaults();

        $this->dispatch('panels.warehouse.warranty-shipment.index.render');
        Flux::modal('panels.warehouse.warranty-shipment.create.modal')->close();
        Flux::toast(__('app.saved_successfully', ['name' => __('app.warehouse_warranty_shipment')]));
    }
};
?>

<flux:modal name="panels.warehouse.warranty-shipment.create.modal" flyout position="right" class="md:w-[28rem]">
    <div class="space-y-6">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('app.create_warehouse_warranty_shipment') }}</flux:heading>
                <flux:subheading>{{ __('app.warehouse_warranty_shipment_form_description') }}</flux:subheading>
            </div>

            <flux:input
                wire:model="form.item_title"
                label="{{ __('app.warehouse_warranty_item_title') }}"
            />
            <flux:error name="form.item_title" />

            <flux:input
                type="number"
                min="1"
                wire:model="form.quantity"
                label="{{ __('app.warehouse_warranty_quantity') }}"
            />
            <flux:error name="form.quantity" />

            <flux:input
                wire:model="form.serial_number"
                label="{{ __('app.warehouse_warranty_serial_number') }}"
            />
            <flux:error name="form.serial_number" />

            <flux:input
                wire:model="form.warranty_company"
                label="{{ __('app.warehouse_warranty_company') }}"
            />
            <flux:error name="form.warranty_company" />

            <x-date-select wire:model="form.sent_at" :label="__('app.warehouse_warranty_sent_at')" :required="true" />
            <flux:error name="form.sent_at" />

            <x-date-select wire:model="form.received_at" :label="__('app.warehouse_warranty_received_at')" />
            <flux:error name="form.received_at" />

            <flux:select wire:model="form.status" searchable label="{{ __('app.status') }}">
                @foreach ($this->statusOptions as $option)
                    <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="form.status" />

            <flux:textarea
                wire:model="form.description"
                label="{{ __('app.description') }}"
                rows="3"
            />
            <flux:error name="form.description" />

            <div class="flex">
                <flux:button type="submit" variant="primary" color="orange" class="w-full">
                    {{ __('app.save') }}
                </flux:button>
            </div>
        </form>
    </div>
</flux:modal>
