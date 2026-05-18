<?php

namespace App\Livewire\Panels\Warehouse\Item;

use App\Models\Sepidar\INV\Item;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

class EditSite extends Component
{
    public $itemId;

    public $iran_code;

    public $item_title;

    #[On('panels.warehouse.item.edit-site.assign-data')]
    public function assignData($id): void
    {
        $this->authorize('warehouse_item_index');

        $item = Item::find($id);
        if ($item) {
            $this->itemId = $item->ItemID;
            $this->iran_code = $item->IranCode ?? '';
            $this->item_title = $item->Title;

            Flux::modal('panels.warehouse.item.edit-site.modal')->show();
        }
    }

    public function edit(): void
    {
        $this->authorize('warehouse_item_index');

        $this->validate([
            'iran_code' => 'required',
        ]);

        $item = Item::find($this->itemId);
        if ($item) {
            $item->IranCode = $this->iran_code;
            $item->save();

            Flux::modal('panels.warehouse.item.edit-site.modal')->close();
            Flux::toast(text: __('app.updated_successfully'), variant: 'success');
            $this->dispatch('panels.warehouse.item.edit-site.saved');
        }
    }

    public function render()
    {
        return view('livewire.panels.warehouse.item.edit-site');
    }
}
