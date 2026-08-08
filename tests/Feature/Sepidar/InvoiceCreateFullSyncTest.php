<?php

namespace Tests\Feature\Sepidar;

use App\Models\Sepidar\ACC\VoucherItem;
use App\Models\Sepidar\GNR\Party;
use App\Models\Sepidar\INV\InventoryDeliveryItem;
use App\Models\Sepidar\INV\Item;
use App\Models\Sepidar\INV\ItemStock;
use App\Models\Sepidar\INV\ItemStockSummary;
use App\Services\Sepidar\InvoiceCreator;
use Morilog\Jalali\Jalalian;
use Tests\Concerns\UsesSepidarSqlsrvTransaction;
use Tests\TestCase;

class InvoiceCreateFullSyncTest extends TestCase
{
    use UsesSepidarSqlsrvTransaction;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSepidarTransaction();
    }

    protected function tearDown(): void
    {
        $this->tearDownSepidarTransaction();
        parent::tearDown();
    }

    public function test_create_invoice_updates_delivery_voucher_and_stock_summary(): void
    {
        [$party, $item, $stockRef, $before] = $this->fixture();

        $qty = 1.0;
        $fee = 1000000;

        $invoice = app(InvoiceCreator::class)->create([
            'customer_party_ref' => (int) $party->PartyId,
            'sale_type_ref' => 2,
            'date' => Jalalian::now()->format('Y/m/d'),
            'description' => 'CRM integration test create',
            'items' => [[
                'item_ref' => (int) $item->ItemID,
                'quantity' => $qty,
                'fee' => $fee,
                'discount' => 0,
                'tax' => 0,
            ]],
        ]);

        $invoice->refresh();

        $this->assertNotNull($invoice->VoucherRef);
        $this->assertSame(1, $invoice->items()->count());

        $invoiceItem = $invoice->items()->first();
        $this->assertNotNull($invoiceItem->StockRef);

        $deliveryItem = InventoryDeliveryItem::query()
            ->where('BaseInvoiceItem', $invoiceItem->InvoiceItemId)
            ->first();

        $this->assertNotNull($deliveryItem);
        $this->assertEquals($qty, (float) $deliveryItem->Quantity);
        $this->assertEquals(0, (float) $deliveryItem->RemainingQuantity);

        $this->assertTrue(
            ItemStock::query()
                ->where('ItemRef', $item->ItemID)
                ->where('StockRef', $stockRef)
                ->exists()
        );

        $voucher = Voucher::query()->findOrFail($invoice->VoucherRef);
        $voucherItems = VoucherItem::query()->where('VoucherRef', $voucher->VoucherId)->get();
        $this->assertTrue($voucherItems->isNotEmpty());
        $this->assertEquals(
            (float) $voucherItems->sum('Debit'),
            (float) $voucherItems->sum('Credit')
        );

        $after = ItemStockSummary::query()
            ->where('ItemRef', $item->ItemID)
            ->where('StockRef', $stockRef)
            ->where('FiscalYearRef', config('sepidar.FiscalYearRef'))
            ->first();

        $this->assertNotNull($after);
        $this->assertEqualsWithDelta(
            (float) $before->OutputQuantity + $qty,
            (float) $after->OutputQuantity,
            0.0001
        );
        $this->assertEqualsWithDelta(
            (float) $before->Quantity - $qty,
            (float) $after->Quantity,
            0.0001
        );
        $this->assertEqualsWithDelta(
            (float) $before->SaleQuantity,
            (float) $after->SaleQuantity,
            0.0001
        );
    }

    /**
     * @return array{0: Party, 1: Item, 2: int, 3: ItemStockSummary}
     */
    private function fixture(): array
    {
        $fiscalYearRef = (int) config('sepidar.FiscalYearRef');
        $stockRef = (int) config('sepidar.DefaultStockRef', 2);

        $summary = ItemStockSummary::query()
            ->where('FiscalYearRef', $fiscalYearRef)
            ->where('StockRef', $stockRef)
            ->where('Quantity', '>=', 2)
            ->orderByDesc('Quantity')
            ->first();

        $this->assertNotNull($summary, 'Need an item with stock for integration test.');

        $item = Item::query()->findOrFail($summary->ItemRef);

        $party = Party::query()
            ->whereNotNull('DLRef')
            ->orderBy('PartyId')
            ->first();

        $this->assertNotNull($party, 'Need a party with DLRef.');

        return [$party, $item, $stockRef, $summary];
    }
}
