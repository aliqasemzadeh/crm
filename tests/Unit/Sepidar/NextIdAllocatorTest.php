<?php

namespace Tests\Unit\Sepidar;

use App\Support\Sepidar\NextIdAllocator;
use InvalidArgumentException;
use Tests\TestCase;

class NextIdAllocatorTest extends TestCase
{
    public function test_rejects_invalid_table_name(): void
    {
        $this->expectException(InvalidArgumentException::class);

        NextIdAllocator::next('SLS.Invoice; DROP TABLE', 'InvoiceId');
    }

    public function test_rejects_invalid_column_name(): void
    {
        $this->expectException(InvalidArgumentException::class);

        NextIdAllocator::next('SLS.Invoice', 'InvoiceId)');
    }
}
