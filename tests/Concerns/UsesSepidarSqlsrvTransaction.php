<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;

trait UsesSepidarSqlsrvTransaction
{
    protected function setUpSepidarTransaction(): void
    {
        if (! $this->sepidarAvailable()) {
            $this->markTestSkipped('sqlsrv Sepidar connection is not available.');
        }

        DB::connection('sqlsrv')->beginTransaction();
    }

    protected function tearDownSepidarTransaction(): void
    {
        if (DB::connection('sqlsrv')->transactionLevel() > 0) {
            DB::connection('sqlsrv')->rollBack();
        }
    }

    protected function sepidarAvailable(): bool
    {
        try {
            DB::connection('sqlsrv')->selectOne('SELECT 1 AS ok');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
