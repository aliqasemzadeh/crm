<?php

use App\Models\Sepidar\GNR\Grouping;
use App\Models\Sepidar\INV\InventoryReceipt;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public $groupingId;

    #[On('panels.accounting.price-note.receipts.assign-data')]
    public function assignData($groupingId)
    {
        $this->groupingId = $groupingId;
        $this->resetPage();
        Flux::modal('panels.accounting.price-note.receipts.modal')->show();
    }

    #[Computed]
    public function receipts()
    {
        $grouping = Grouping::find($this->groupingId);
        if (!$grouping) {
            return collect();
        }

        $groupIds = $grouping->getAllChildrenIds();
        $fiscalYearRef = config('sepidar.FiscalYearRef');

        return InventoryReceipt::query()
            ->where('FiscalYearRef', $fiscalYearRef)
            ->whereHas('items.item', function ($query) use ($groupIds) {
                $query->whereIn('CodingGroupRef', $groupIds);
            })
            ->with(['dl'])
            ->latest('Number')
            ->paginate(10);
    }
};
?>

<div>
    <flux:modal name="panels.accounting.price-note.receipts.modal" flyout position="right" class="md:w-1/2">
        <div class="space-y-6">
            <div>
                <flux:heading size="xl">{{ __('app.inventory_receipts') }}</flux:heading>
                <flux:subheading>{{ __('app.list_of_receipts_for_selected_group') }}</flux:subheading>
            </div>

            <flux:table :paginate="$this->receipts">
                <flux:table.columns>
                    <flux:table.column>{{ __('app.receipt_number') }}</flux:table.column>
                    <flux:table.column>{{ __('app.deliverer') }}</flux:table.column>
                    <flux:table.column>{{ __('app.date') }}</flux:table.column>
                    <flux:table.column align="end"></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->receipts as $receipt)
                        <flux:table.row :key="$receipt->InventoryReceiptID">
                            <flux:table.cell font="medium">{{ $receipt->Number }}</flux:table.cell>
                            <flux:table.cell>{{ $receipt->dl?->Title ?? '-' }}</flux:table.cell>
                            <flux:table.cell>{{ $receipt->Date }}</flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:tooltip content="{{ __('app.view_receipt') }}">
                                    <flux:button size="xs" variant="primary" color="sky" icon="eye"
                                        wire:click="$dispatch('panels.accounting.inventory-receipt.view.assign-data', { InventoryReceiptID: {{ $receipt->InventoryReceiptID }} })" />
                                </flux:tooltip>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:modal>
</div>
