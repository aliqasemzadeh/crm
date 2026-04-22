<?php

namespace App\Livewire\Panels\Accounting\PriceNote;

use Livewire\Component;

class ItemImage extends Component
{
    use \Livewire\WithFileUploads;

    public $itemId;
    public $image;

    public function mount($itemId)
    {
        $this->itemId = $itemId;
    }

    public function updatedImage()
    {
        $this->validate([
            'image' => 'image|max:2048', // 2MB Max
        ]);

        $itemImage = \App\Models\Sepidar\INV\ItemImage::where('ItemRef', $this->itemId)->first();

        $imageData = file_get_contents($this->image->getRealPath());

        if ($itemImage) {
            $itemImage->update([
                'Image' => $imageData,
                'Version' => ($itemImage->Version ?? 0) + 1,
            ]);
        } else {
            \App\Models\Sepidar\INV\ItemImage::create([
                'ItemRef' => $this->itemId,
                'Image' => $imageData,
                'Version' => 1,
            ]);
        }

        \Illuminate\Support\Facades\Cache::forget("item_image_{$this->itemId}");

        $this->dispatch('image-updated');
    }

    public function render()
    {
        $hasImage = \App\Models\Sepidar\INV\ItemImage::where('ItemRef', $this->itemId)->exists();
        return view('livewire.panels.accounting.price-note.item-image', [
            'hasImage' => $hasImage
        ]);
    }
}
