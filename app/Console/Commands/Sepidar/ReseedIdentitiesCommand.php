<?php

namespace App\Console\Commands\Sepidar;

use App\Support\Sepidar\NextIdAllocator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ReseedIdentitiesCommand extends Command
{
    protected $signature = 'app:sepidar:reseed-identities
                            {--all : Sync every row in FMK.IDGeneration, not only CRM-written tables}';

    protected $description = 'Sync FMK.IDGeneration.LastId to MAX(pk) so Sepidar desktop does not collide with CRM-written primary keys';

    /**
     * Tables CRM writes with NextIdAllocator — keep in sync with syncIdentity call sites.
     *
     * @var list<array{0: string, 1: string}>
     */
    private const TARGETS = [
        ['SLS.Invoice', 'InvoiceId'],
        ['SLS.InvoiceItem', 'InvoiceItemID'],
        ['INV.InventoryDelivery', 'InventoryDeliveryID'],
        ['INV.InventoryDeliveryItem', 'InventoryDeliveryItemID'],
        ['ACC.Voucher', 'VoucherId'],
        ['ACC.VoucherItem', 'VoucherItemId'],
        ['INV.ItemStock', 'ItemStockID'],
        ['INV.ItemImage', 'ItemImageID'],
    ];

    public function handle(): int
    {
        $connection = 'sqlsrv';
        $fixed = 0;
        $ok = 0;
        $skipped = 0;

        $this->info('Syncing Sepidar FMK.IDGeneration counters…');
        $this->newLine();

        $targets = $this->option('all')
            ? $this->discoverAllTargets($connection)
            : self::TARGETS;

        foreach ($targets as [$table, $column]) {
            try {
                $result = $this->syncOne($connection, $table, $column);
            } catch (Throwable $e) {
                $this->error("{$table}.{$column}: {$e->getMessage()}");

                return self::FAILURE;
            }

            match ($result) {
                'fixed' => $fixed++,
                'ok' => $ok++,
                default => $skipped++,
            };
        }

        $this->newLine();
        $this->info("Done. Fixed {$fixed}, already ok {$ok}, skipped {$skipped}.");

        return self::SUCCESS;
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private function discoverAllTargets(string $connection): array
    {
        $rows = DB::connection($connection)->select('
            SELECT
                g.[TableName],
                COL_NAME(ic.object_id, ic.column_id) AS ColumnName,
                COUNT(*) OVER (PARTITION BY g.[TableName]) AS PkColumnCount
            FROM [FMK].[IDGeneration] g
            INNER JOIN sys.tables t
                ON t.object_id = OBJECT_ID(g.[TableName])
            INNER JOIN sys.indexes i
                ON i.object_id = t.object_id AND i.is_primary_key = 1
            INNER JOIN sys.index_columns ic
                ON ic.object_id = i.object_id AND ic.index_id = i.index_id
            ORDER BY g.[TableName]
        ');

        $targets = [];

        foreach ($rows as $row) {
            if ((int) $row->PkColumnCount !== 1 || empty($row->ColumnName)) {
                $this->line("{$row->TableName}: skipped (composite or missing PK)");

                continue;
            }

            $targets[] = [(string) $row->TableName, (string) $row->ColumnName];
        }

        return $targets;
    }

    private function syncOne(string $connection, string $table, string $column): string
    {
        $parts = explode('.', $table, 2);
        $quoted = count($parts) === 2
            ? '['.$parts[0].'].['.$parts[1].']'
            : '['.$table.']';
        $quotedColumn = '['.$column.']';

        $maxRow = DB::connection($connection)->selectOne(
            "SELECT ISNULL(MAX({$quotedColumn}), 0) AS MaxId FROM {$quoted}"
        );
        $maxId = (int) ($maxRow->MaxId ?? 0);

        $gen = DB::connection($connection)->selectOne(
            'SELECT [LastId] FROM [FMK].[IDGeneration] WHERE [TableName] = ?',
            [$table]
        );

        $lastBefore = $gen !== null ? (int) $gen->LastId : null;

        NextIdAllocator::syncIdentity($table, $column, $connection);

        $genAfter = DB::connection($connection)->selectOne(
            'SELECT [LastId] FROM [FMK].[IDGeneration] WHERE [TableName] = ?',
            [$table]
        );
        $lastAfter = $genAfter !== null ? (int) $genAfter->LastId : null;

        if ($lastBefore === null) {
            $this->warn("{$table}  max={$maxId}  LastId=missing -> set to {$lastAfter}");

            return 'fixed';
        }

        if ($lastBefore < $maxId) {
            $this->warn("{$table}  max={$maxId}  LastId={$lastBefore}  OUT_OF_SYNC -> {$lastAfter}");

            return 'fixed';
        }

        $this->line("{$table}  max={$maxId}  LastId={$lastBefore}  ok");

        return 'ok';
    }
}
