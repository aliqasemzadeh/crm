<?php

namespace Tests\Feature\Sepidar;

use App\Models\Sepidar\ACC\VoucherItem;
use App\Models\Sepidar\GNR\Party;
use App\Models\Sepidar\INV\InventoryDeliveryItem;
use App\Models\Sepidar\INV\Item;
use App\Models\Sepidar\INV\ItemStockSummary;
use App\Models\Sepidar\SLS\Invoice;
use App\Services\Sepidar\InvoiceCreator;
use App\Services\Sepidar\InvoiceUpdater;
use Morilog\Jalali\Jalalian;
use Tests\Concerns\UsesSepidarSqlsrvTransaction;
use Tests\TestCase;

class InvoiceUpdateFullSyncTest extends TestCase
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

    public function test_update_invoice_quantity_resyncs_delivery_voucher_and_stock(): void
    {
        [$party, $item, $stockRef, $before] = $this->fixture();

        $creator = app(InvoiceCreator::class);
        $updater = app(InvoiceUpdater::class);

        $invoice = $creator->create([
            'customer_party_ref' => (int) $party->PartyId,
            'sale_type_ref' => 2,
            'date' => Jalalian::now()->format('Y/m/d'),
            'description' => 'CRM integration test update',
            'items' => [[
                'item_ref' => (int) $item->ItemID,
                'quantity' => 2,
                'fee' => 1500000,
                'discount' => 0,
                'tax' => 0,
            ]],
        ]);

        $afterCreate = ItemStockSummary::query()
            ->where('ItemRef', $item->ItemID)
            ->where('StockRef', $stockRef)
            ->where('FiscalYearRef', config('sepidar.FiscalYearRef'))
            ->first();

        $this->assertEqualsWithDelta(
            (float) $before->OutputQuantity + 2,
            (float) $afterCreate->OutputQuantity,
            0.0001
        );

        $updated = $updater->update($invoice, [
            'customer_party_ref' => (int) $party->PartyId,
            'sale_type_ref' => 2,
            'date' => Jalalian::now()->format('Y/m/d'),
            'description' => 'CRM integration test update qty=1',
            'items' => [[
                'item_ref' => (int) $item->ItemID,
                'quantity' => 1,
                'fee' => 1500000,
                'discount' => 0,
                'tax' => 0,
            ]],
        ]);

        $this->assertSame(1, $updated->items()->count());
        $this->assertNotNull($updated->VoucherRef);

        $invoiceItem = $updated->items()->first();
        $deliveryItems = InventoryDeliveryItem::query()
            ->where('BaseInvoiceItem', $invoiceItem->getKey())
            ->get();

        $this->assertCount(1, $deliveryItems);
        $this->assertEquals(1.0, (float) $deliveryItems->first()->Quantity);

        $voucherItems = VoucherItem::query()
            ->where('VoucherRef', $updated->VoucherRef)
            ->get();

        $this->assertEquals(
            (float) $voucherItems->sum('Debit'),
            (float) $voucherItems->sum('Credit')
        );
        $this->assertEquals(1500000.0, (float) $voucherItems->sum('Credit'));

        $afterUpdate = ItemStockSummary::query()
            ->where('ItemRef', $item->ItemID)
            ->where('StockRef', $stockRef)
            ->where('FiscalYearRef', config('sepidar.FiscalYearRef'))
            ->first();

        $this->assertEqualsWithDelta(
            (float) $before->OutputQuantity + 1,
            (float) $afterUpdate->OutputQuantity,
            0.0001
        );
        $this->assertEqualsWithDelta(
            (float) $before->Quantity - 1,
            (float) $afterUpdate->Quantity,
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
            ->where('Quantity', '>=', 3)
            ->orderByDesc('Quantity')
            ->first();

        $this->assertNotNull($summary, 'Need an item with stock for integration test.');

        $item = Item::query()->findOrFail($summary->ItemRef);
        $party = Party::query()->whereNotNull('DLRef')->orderBy('PartyId')->first();
        $this->assertNotNull($party);

        return [$party, $item, $stockRef, $summary];
    }
}
