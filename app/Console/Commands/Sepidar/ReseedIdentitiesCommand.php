<?php

namespace App\Console\Commands\Sepidar;

use App\Support\Sepidar\NextIdAllocator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReseedIdentitiesCommand extends Command
{
    protected $signature = 'app:sepidar:reseed-identities';

    protected $description = 'Reseed SQL Server IDENTITY on Sepidar tables so desktop inserts do not collide with CRM-written primary keys';

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
        $skipped = 0;

        $this->info('Reseeding Sepidar SQL Server identities…');
        $this->newLine();

        foreach (self::TARGETS as [$table, $column]) {
            try {
                $result = $this->reseedOne($connection, $table, $column);
            } catch (InvalidArgumentException|\Throwable $e) {
                $this->error("{$table}.{$column}: {$e->getMessage()}");

                return self::FAILURE;
            }

            if ($result === 'skipped') {
                $skipped++;
                continue;
            }

            if ($result === 'fixed') {
                $fixed++;
            }
        }

        $this->newLine();
        $this->info("Done. Fixed {$fixed} table(s), skipped {$skipped}.");

        return self::SUCCESS;
    }

    private function reseedOne(string $connection, string $table, string $column): string
    {
        $objectName = $table;

        $hasIdentity = DB::connection($connection)->selectOne(
            "SELECT OBJECTPROPERTY(OBJECT_ID(?), 'TableHasIdentity') AS HasIdentity",
            [$objectName]
        );

        if ((int) ($hasIdentity->HasIdentity ?? 0) !== 1) {
            $this->line("{$table}: skipped (no identity)");

            return 'skipped';
        }

        $parts = explode('.', $table, 2);
        $quoted = count($parts) === 2
            ? '['.$parts[0].'].['.$parts[1].']'
            : '['.$table.']';
        $quotedColumn = '['.$column.']';

        $maxRow = DB::connection($connection)->selectOne(
            "SELECT ISNULL(MAX({$quotedColumn}), 0) AS MaxId FROM {$quoted}"
        );
        $maxId = (int) ($maxRow->MaxId ?? 0);

        $identRow = DB::connection($connection)->selectOne(
            'SELECT CONVERT(bigint, IDENT_CURRENT(?)) AS IdentCurrent',
            [$objectName]
        );
        $identBefore = (int) ($identRow->IdentCurrent ?? 0);

        $status = $identBefore < $maxId ? 'OUT_OF_SYNC' : 'ok';

        NextIdAllocator::syncIdentity($table, $column, $connection);

        $identAfterRow = DB::connection($connection)->selectOne(
            'SELECT CONVERT(bigint, IDENT_CURRENT(?)) AS IdentCurrent',
            [$objectName]
        );
        $identAfter = (int) ($identAfterRow->IdentCurrent ?? 0);

        if ($status === 'OUT_OF_SYNC') {
            $this->warn("{$table}  max={$maxId}  ident={$identBefore}  OUT_OF_SYNC -> reseeded to {$identAfter}");

            return 'fixed';
        }

        $this->line("{$table}  max={$maxId}  ident={$identBefore}  ok -> reseeded to {$identAfter}");

        return 'ok';
    }
}
