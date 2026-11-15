<?php

namespace App\Support\Sepidar;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class NextIdAllocator
{
    /**
     * Allocate the next primary-key value under a transaction-scoped range lock.
     *
     * Must be called inside a sqlsrv transaction that also performs the insert(s),
     * so concurrent allocators block on UPDLOCK/HOLDLOCK until commit.
     */
    public static function next(string $qualifiedTable, string $column, string $connection = 'sqlsrv'): int
    {
        return self::nextRange($qualifiedTable, $column, 1, $connection);
    }

    /**
     * Allocate a contiguous range of IDs and return the first value.
     * Caller inserts count rows as start, start+1, ... while the transaction holds the lock.
     */
    public static function nextRange(string $qualifiedTable, string $column, int $count, string $connection = 'sqlsrv'): int
    {
        $count = max(1, $count);
        [$schema, $table] = self::splitTable($qualifiedTable);
        $quotedColumn = self::quoteIdent($column);
        $from = self::quoteTable($schema, $table);
        $resource = substr("sepidar:next:{$qualifiedTable}.{$column}", 0, 255);

        if (DB::connection($connection)->transactionLevel() < 1) {
            throw new \RuntimeException(
                "NextIdAllocator requires an open [{$connection}] transaction for [{$qualifiedTable}.{$column}]."
            );
        }

        // Serialize CRM allocators even if another session reads MAX without row locks.
        $lock = DB::connection($connection)->selectOne(
            'DECLARE @result int;
             EXEC @result = sp_getapplock
                @Resource = ?,
                @LockMode = \'Exclusive\',
                @LockOwner = \'Transaction\',
                @LockTimeout = 30000;
             SELECT @result AS Result',
            [$resource]
        );

        if ((int) ($lock->Result ?? -999) < 0) {
            throw new \RuntimeException("Could not acquire Sepidar id lock for [{$qualifiedTable}.{$column}].");
        }

        $row = DB::connection($connection)->selectOne(
            "SELECT ISNULL(MAX({$quotedColumn}), 0) AS MaxId FROM {$from} WITH (UPDLOCK, HOLDLOCK)"
        );

        return ((int) ($row->MaxId ?? 0)) + 1;
    }

    /**
     * Keep SQL Server IDENTITY in sync after manual inserts so Sepidar desktop
     * does not regenerate an ID that CRM already wrote.
     */
    public static function syncIdentity(string $qualifiedTable, string $column, string $connection = 'sqlsrv'): void
    {
        [$schema, $table] = self::splitTable($qualifiedTable);
        $objectName = $schema !== null ? "{$schema}.{$table}" : $table;

        if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)?$/', $objectName)) {
            throw new InvalidArgumentException("Invalid table name [{$objectName}].");
        }

        $hasIdentity = DB::connection($connection)->selectOne(
            "SELECT OBJECTPROPERTY(OBJECT_ID(?), 'TableHasIdentity') AS HasIdentity",
            [$objectName]
        );

        if ((int) ($hasIdentity->HasIdentity ?? 0) !== 1) {
            return;
        }

        $quotedColumn = self::quoteIdent($column);
        $from = self::quoteTable($schema, $table);

        $max = DB::connection($connection)->selectOne(
            "SELECT ISNULL(MAX({$quotedColumn}), 0) AS MaxId FROM {$from}"
        );

        $maxId = (int) ($max->MaxId ?? 0);

        DB::connection($connection)->unprepared(
            "DBCC CHECKIDENT (N'".str_replace("'", "''", $objectName)."', RESEED, {$maxId})"
        );
    }

    /**
     * @return array{0: ?string, 1: string}
     */
    private static function splitTable(string $qualifiedTable): array
    {
        $qualifiedTable = trim($qualifiedTable);

        if ($qualifiedTable === '') {
            throw new InvalidArgumentException('Table name cannot be empty.');
        }

        if (str_contains($qualifiedTable, '.')) {
            [$schema, $table] = explode('.', $qualifiedTable, 2);

            return [self::assertIdent($schema), self::assertIdent($table)];
        }

        return [null, self::assertIdent($qualifiedTable)];
    }

    private static function quoteTable(?string $schema, string $table): string
    {
        if ($schema === null) {
            return self::quoteIdent($table);
        }

        return self::quoteIdent($schema).'.'.self::quoteIdent($table);
    }

    private static function quoteIdent(string $ident): string
    {
        return '['.self::assertIdent($ident).']';
    }

    private static function assertIdent(string $ident): string
    {
        $ident = trim($ident, "[] \t\n\r");

        if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $ident)) {
            throw new InvalidArgumentException("Invalid SQL identifier [{$ident}].");
        }

        return $ident;
    }
}
