<?php

namespace App\Models\Sepidar\INV;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ItemImage extends Model
{
    public $table = 'INV.ItemImage';

    public $connection = 'sqlsrv';

    public $primaryKey = 'ItemImageID';

    public $timestamps = false;

    protected $fillable = ['ItemImageID', 'ItemRef', 'Image', 'Thumbnail', 'Version'];

    public static function upsertBinaryForItem(int $itemRef, string $imageJpeg, string $thumbnailJpeg): void
    {
        DB::connection('sqlsrv')->transaction(function () use ($itemRef, $imageJpeg, $thumbnailJpeg) {
            $existing = static::query()
                ->where('ItemRef', $itemRef)
                ->orderByDesc('ItemImageID')
                ->first();

            if ($existing !== null) {
                $existing->forceFill([
                    'Image' => $imageJpeg,
                    'Thumbnail' => $thumbnailJpeg,
                    'Version' => ((int) $existing->Version) + 1,
                ])->save();

                return;
            }

            $nextId = ((int) static::query()->max('ItemImageID')) + 1;

            static::query()->create([
                'ItemImageID' => $nextId,
                'ItemRef' => $itemRef,
                'Image' => $imageJpeg,
                'Thumbnail' => $thumbnailJpeg,
                'Version' => 1,
            ]);
        });

        Cache::forget("item_image_{$itemRef}");
    }
}
