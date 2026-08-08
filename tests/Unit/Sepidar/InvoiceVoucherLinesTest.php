<?php

namespace Tests\Unit\Sepidar;

use App\Models\Sepidar\GNR\Party;
use App\Models\Sepidar\SLS\Invoice;
use App\Models\Sepidar\SLS\SaleType;
use App\Services\Sepidar\InvoiceVoucherSync;
use Tests\TestCase;

class InvoiceVoucherLinesTest extends TestCase
{
    public function test_build_lines_with_price_discount_and_tax(): void
    {
        config([
            'sepidar.TaxSLRef' => 531,
            'sepidar.InvoiceIssuerEntityName' => 'test',
        ]);

        $party = new Party;
        $party->forceFill(['DLRef' => 100]);

        $invoice = new Invoice;
        $invoice->forceFill([
            'InvoiceId' => 1,
            'Number' => 10,
            'SLRef' => 117,
            'SaleTypeRef' => 2,
            'Price' => 1000,
            'Discount' => 100,
            'Tax' => 90,
            'Duty' => 0,
            'Addition' => 0,
            'NetPrice' => 990,
            'CustomerRealName' => 'مشتری تست',
            'Date' => '2026-08-08 00:00:00',
        ]);
        $invoice->setRelation('customer', $party);

        $saleType = new SaleType;
        $saleType->forceFill([
            'SaleTypeId' => 2,
            'PartSalesSLRef' => 249,
            'PartSalesDiscountSLRef' => 255,
            'Title' => 'فروش غيررسمي',
        ]);

        $lines = app(InvoiceVoucherSync::class)->buildLines($invoice, $saleType);

        $this->assertCount(4, $lines);

        $this->assertSame(117, $lines[0]['account_sl_ref']);
        $this->assertSame(100, $lines[0]['dl_ref']);
        $this->assertSame(990.0, $lines[0]['debit']);
        $this->assertSame(0.0, $lines[0]['credit']);

        $this->assertSame(249, $lines[1]['account_sl_ref']);
        $this->assertSame(1000.0, $lines[1]['credit']);

        $this->assertSame(255, $lines[2]['account_sl_ref']);
        $this->assertSame(100.0, $lines[2]['debit']);

        $this->assertSame(531, $lines[3]['account_sl_ref']);
        $this->assertSame(90.0, $lines[3]['credit']);

        $debits = array_sum(array_column($lines, 'debit'));
        $credits = array_sum(array_column($lines, 'credit'));
        $this->assertEquals($debits, $credits);
    }

    public function test_build_lines_skips_receivable_when_net_is_zero(): void
    {
        config(['sepidar.TaxSLRef' => 531]);

        $saleType = new SaleType;
        $saleType->forceFill([
            'PartSalesSLRef' => 249,
            'PartSalesDiscountSLRef' => 255,
            'Title' => 'فروش غيررسمي',
        ]);

        $invoice = new Invoice;
        $invoice->forceFill([
            'InvoiceId' => 2,
            'Number' => 11,
            'SLRef' => 117,
            'SaleTypeRef' => 2,
            'Price' => 500,
            'Discount' => 500,
            'Tax' => 0,
            'Duty' => 0,
            'Addition' => 0,
            'NetPrice' => 0,
            'CustomerRealName' => 'مشتری',
            'Date' => '2026-08-08 00:00:00',
        ]);

        $lines = app(InvoiceVoucherSync::class)->buildLines($invoice, $saleType);

        $this->assertCount(2, $lines);
        $this->assertSame(249, $lines[0]['account_sl_ref']);
        $this->assertSame(255, $lines[1]['account_sl_ref']);
        $this->assertEquals(
            array_sum(array_column($lines, 'debit')),
            array_sum(array_column($lines, 'credit'))
        );
    }
}
