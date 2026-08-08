<?php

namespace App\Livewire\Panels\Sale\Item;

use App\Models\Sepidar\INV\Item;
use App\Models\Sepidar\INV\ItemImage;
use Flux\Flux;
use GdImage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Throwable;

class UploadImage extends Component
{
    use WithFileUploads;

    public ?int $itemId = null;

    public string $item_title = '';

    /** @var array<int, TemporaryUploadedFile> */
    public array $photos = [];

    #[On('panels.sale.item.upload-image.assign-data')]
    public function assignData($id): void
    {
        $this->authorize('sales_item_image');

        $item = Item::find($id);
        if ($item === null) {
            return;
        }

        $this->itemId = (int) $item->ItemID;
        $this->item_title = (string) $item->Title;
        $this->photos = [];

        $this->modal('panels.sale.item.upload-image.modal')->show();
    }

    public function store(): void
    {
        $this->authorize('sales_item_image');

        if ($this->itemId === null) {
            return;
        }

        $this->validate([
            'photos' => ['required', 'array', 'min:1'],
            'photos.*' => ['image', 'max:10240'],
        ]);

        $selectedCount = count($this->photos);
        $file = $this->photos[0];

        try {
            [$fullJpeg, $thumbJpeg] = $this->buildJpegVariants($file);
        } catch (Throwable $e) {
            report($e);
            Flux::toast(text: __('app.warehouse_item_image_invalid_file'), variant: 'danger');

            return;
        }

        try {
            ItemImage::upsertBinaryForItem($this->itemId, $fullJpeg, $thumbJpeg);
        } catch (Throwable $e) {
            report($e);
            Flux::toast(text: __('app.warehouse_item_image_save_failed'), variant: 'danger');

            return;
        }

        $this->photos = [];
        $this->modal('panels.sale.item.upload-image.modal')->close();

        $message = $selectedCount > 1
            ? __('app.warehouse_item_image_saved_first_only', ['count' => $selectedCount])
            : __('app.warehouse_item_image_saved');

        Flux::toast(text: $message, variant: 'success');
        $this->dispatch('panels.sale.item.upload-image.saved');
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function buildJpegVariants(TemporaryUploadedFile $file): array
    {
        $path = $file->getRealPath();
        if ($path === false) {
            throw new \RuntimeException('Missing temp path');
        }

        $binary = file_get_contents($path);
        if ($binary === false || $binary === '') {
            throw new \RuntimeException('Empty upload');
        }

        $source = @imagecreatefromstring($binary);
        if (! $source instanceof GdImage) {
            throw new \RuntimeException('Unsupported image');
        }

        $full = $this->resizeToMaxEdgeCopy($source, 1600);
        $thumb = $this->resizeToMaxEdgeCopy($source, 128);
        imagedestroy($source);

        $fullJpeg = $this->toJpegString($full);
        imagedestroy($full);

        $thumbJpeg = $this->toJpegString($thumb);
        imagedestroy($thumb);

        return [$fullJpeg, $thumbJpeg];
    }

    private function resizeToMaxEdgeCopy(GdImage $src, int $maxEdge): GdImage
    {
        $w = imagesx($src);
        $h = imagesy($src);
        $ratio = min(1.0, $maxEdge / $w, $maxEdge / $h);
        $nw = max(1, (int) round($w * $ratio));
        $nh = max(1, (int) round($h * $ratio));

        $dst = imagecreatetruecolor($nw, $nh);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $nw, $nh, $white);
        imagealphablending($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        return $dst;
    }

    private function toJpegString(GdImage $im, int $quality = 86): string
    {
        ob_start();
        imagejpeg($im, null, $quality);

        return (string) ob_get_clean();
    }

    public function render()
    {
        return view('livewire.panels.sale.item.upload-image');
    }
}
