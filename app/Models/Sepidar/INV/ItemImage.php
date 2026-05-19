<?php

namespace App\Models\Sepidar\INV;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PDO;
use PDOStatement;

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
                $stmt = static::pdo()->prepare(
                    'UPDATE [INV].[ItemImage] SET [Image] = ?, [Thumbnail] = ?, [Version] = ? WHERE [ItemImageID] = ?'
                );
                static::bindBinary($stmt, 1, $imageJpeg);
                static::bindBinary($stmt, 2, $thumbnailJpeg);
                $stmt->bindValue(3, ((int) $existing->Version) + 1, PDO::PARAM_INT);
                $stmt->bindValue(4, (int) $existing->ItemImageID, PDO::PARAM_INT);
                $stmt->execute();

                return;
            }

            $nextId = ((int) static::query()->max('ItemImageID')) + 1;

            $stmt = static::pdo()->prepare(
                'INSERT INTO [INV].[ItemImage] ([ItemImageID], [ItemRef], [Image], [Thumbnail], [Version]) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->bindValue(1, $nextId, PDO::PARAM_INT);
            $stmt->bindValue(2, $itemRef, PDO::PARAM_INT);
            static::bindBinary($stmt, 3, $imageJpeg);
            static::bindBinary($stmt, 4, $thumbnailJpeg);
            $stmt->bindValue(5, 1, PDO::PARAM_INT);
            $stmt->execute();
        });

        Cache::forget("item_image_{$itemRef}");
    }

    private static function pdo(): PDO
    {
        return DB::connection('sqlsrv')->getPdo();
    }

    private static function bindBinary(PDOStatement $stmt, int $param, string $binary): void
    {
        if (defined('PDO::SQLSRV_ENCODING_BINARY')) {
            $stmt->bindValue($param, $binary, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);

            return;
        }

        $stmt->bindValue($param, $binary, PDO::PARAM_LOB);
    }
}
