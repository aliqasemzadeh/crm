<?php

namespace App\Livewire\Panels\Sale\ItemPrice;

use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class ItemImage extends Component
{
    public $itemId;

    public function mount($itemId)
    {
        $this->itemId = $itemId;
    }

    public function render()
    {
        $hasImage = \App\Models\Sepidar\INV\ItemImage::where('ItemRef', $this->itemId)->exists();
        return view('livewire.panels.sale.item-price.item-image', [
            'hasImage' => $hasImage
        ]);
    }
}
