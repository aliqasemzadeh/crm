<?php

namespace App\Models\Sepidar\INV;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PDO;

class ItemImage extends Model
{
    public $table = 'INV.ItemImage';

    public $connection = 'sqlsrv';

    public $primaryKey = 'ItemImageID';

    public $timestamps = false;

    protected $fillable = ['ItemImageID', 'ItemRef', 'Image', 'Thumbnail', 'Version'];

    public static function upsertBinaryForItem(int $itemRef, string $imageJpeg, string $thumbnailJpeg): void
    {
        $imageSql = static::sqlBinaryExpression($imageJpeg);
        $thumbnailSql = static::sqlBinaryExpression($thumbnailJpeg);

        DB::connection('sqlsrv')->transaction(function () use ($itemRef, $imageSql, $thumbnailSql) {
            $existing = static::query()
                ->where('ItemRef', $itemRef)
                ->orderByDesc('ItemImageID')
                ->first();

            if ($existing !== null) {
                $stmt = static::pdo()->prepare(
                    "UPDATE [INV].[ItemImage] SET [Image] = {$imageSql}, [Thumbnail] = {$thumbnailSql}, [Version] = ? WHERE [ItemImageID] = ?"
                );
                $stmt->bindValue(1, ((int) $existing->Version) + 1, PDO::PARAM_INT);
                $stmt->bindValue(2, (int) $existing->ItemImageID, PDO::PARAM_INT);
                $stmt->execute();

                return;
            }

            $nextId = ((int) static::query()->max('ItemImageID')) + 1;

            $stmt = static::pdo()->prepare(
                "INSERT INTO [INV].[ItemImage] ([ItemImageID], [ItemRef], [Image], [Thumbnail], [Version]) VALUES (?, ?, {$imageSql}, {$thumbnailSql}, ?)"
            );
            $stmt->bindValue(1, $nextId, PDO::PARAM_INT);
            $stmt->bindValue(2, $itemRef, PDO::PARAM_INT);
            $stmt->bindValue(3, 1, PDO::PARAM_INT);
            $stmt->execute();
        });

        Cache::forget("item_image_{$itemRef}");
    }

    private static function pdo(): PDO
    {
        return DB::connection('sqlsrv')->getPdo();
    }

    /**
     * Embed binary as a SQL Server varbinary literal (safe: bin2hex output is hex digits only).
     */
    private static function sqlBinaryExpression(string $binary): string
    {
        return 'CONVERT(varbinary(max), 0x'.bin2hex($binary).')';
    }
}
