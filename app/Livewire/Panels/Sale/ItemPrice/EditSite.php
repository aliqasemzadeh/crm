<?php

namespace App\Livewire\Panels\Sale\ItemPrice;

use App\Models\Sepidar\INV\Item;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

class EditSite extends Component
{
    public $itemId;
    public $iran_code;
    public $item_title;

    #[On('panels.sale.item-price.edit-site.assign-data')]
    public function assignData($id)
    {
        $this->authorize('sales_item_site_edit');

        $item = Item::find($id);
        if ($item) {
            $this->itemId = $item->ItemID;
            $this->iran_code = $item->IranCode;
            $this->item_title = $item->Title;

            $this->dispatch('modal-show', name: 'panels.sale.item-price.edit-site.modal');
        }
    }

    public function edit()
    {
        $this->authorize('sales_item_site_edit');

        $this->validate([
            'iran_code' => 'required',
        ]);

        $item = Item::find($this->itemId);
        if ($item) {
            $item->IranCode = $this->iran_code;
            $item->save();

            $this->dispatch('modal-close', name: 'panels.sale.item-price.edit-site.modal');
            Flux::toast(text: __('app.updated_successfully'), variant: 'success');
            $this->dispatch('panels.sale.item-price.items.refresh');
        }
    }

    public function render()
    {
        return view('livewire.panels.sale.item-price.edit-site');
    }
}
