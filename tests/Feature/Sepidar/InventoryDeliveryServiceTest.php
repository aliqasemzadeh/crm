<?php

namespace Tests\Feature\Sepidar;

use App\Models\Sepidar\GNR\Party;
use App\Models\Sepidar\INV\InventoryDeliveryItem;
use App\Models\Sepidar\INV\Item;
use App\Models\Sepidar\INV\ItemStockSummary;
use App\Models\Sepidar\SLS\Invoice;
use App\Services\Sepidar\InventoryDeliveryService;
use App\Services\Sepidar\InvoiceCreator;
use Morilog\Jalali\Jalalian;
use Tests\Concerns\UsesSepidarSqlsrvTransaction;
use Tests\TestCase;

class InventoryDeliveryServiceTest extends TestCase
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

    public function test_create_update_and_delete_standalone_delivery_updates_stock(): void
    {
        [$party, $item, $stockRef, $before] = $this->fixture();
        $service = app(InventoryDeliveryService::class);

        $delivery = $service->create([
            'stock_ref' => $stockRef,
            'receiver_party_ref' => (int) $party->PartyId,
            'date' => Jalalian::now()->format('Y/m/d'),
            'description' => 'CRM warehouse exit test',
            'items' => [[
                'item_ref' => (int) $item->ItemID,
                'quantity' => 2,
                'description' => null,
            ]],
        ]);

        $this->assertSame(0, Invoice::query()->where('Description', 'CRM warehouse exit test')->count());

        $deliveryItem = InventoryDeliveryItem::query()
            ->where('InventoryDeliveryRef', $delivery->InventoryDeliveryID)
            ->first();

        $this->assertNotNull($deliveryItem);
        $this->assertNull($deliveryItem->BaseInvoiceItem);
        $this->assertEquals(2.0, (float) $deliveryItem->Quantity);

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
        $this->assertEqualsWithDelta(
            (float) $before->Quantity - 2,
            (float) $afterCreate->Quantity,
            0.0001
        );

        $updated = $service->update($delivery, [
            'stock_ref' => $stockRef,
            'receiver_party_ref' => (int) $party->PartyId,
            'date' => Jalalian::now()->format('Y/m/d'),
            'description' => 'CRM warehouse exit test qty=1',
            'items' => [[
                'item_ref' => (int) $item->ItemID,
                'quantity' => 1,
                'description' => null,
            ]],
        ]);

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

        $service->delete((int) $updated->InventoryDeliveryID);

        $afterDelete = ItemStockSummary::query()
            ->where('ItemRef', $item->ItemID)
            ->where('StockRef', $stockRef)
            ->where('FiscalYearRef', config('sepidar.FiscalYearRef'))
            ->first();

        $this->assertEqualsWithDelta(
            (float) $before->OutputQuantity,
            (float) $afterDelete->OutputQuantity,
            0.0001
        );
        $this->assertEqualsWithDelta(
            (float) $before->Quantity,
            (float) $afterDelete->Quantity,
            0.0001
        );
    }

    public function test_create_from_invoice_links_base_invoice_item_and_updates_stock(): void
    {
        [$party, $item, $stockRef, $before] = $this->fixture();
        $service = app(InventoryDeliveryService::class);

        $invoice = app(InvoiceCreator::class)->create([
            'customer_party_ref' => (int) $party->PartyId,
            'sale_type_ref' => 2,
            'stock_ref' => $stockRef,
            'date' => Jalalian::now()->format('Y/m/d'),
            'description' => 'CRM warehouse exit from invoice test',
            'items' => [[
                'item_ref' => (int) $item->ItemID,
                'quantity' => 1,
                'fee' => 100000,
                'discount' => 0,
                'tax' => 0,
                'stock_ref' => $stockRef,
            ]],
        ]);

        $invoiceItem = $invoice->items()->first();
        $this->assertNotNull($invoiceItem);

        $deliveries = $service->createFromInvoice($invoice->fresh(['items', 'customer']));
        $this->assertCount(1, $deliveries);

        $deliveryItem = InventoryDeliveryItem::query()
            ->where('BaseInvoiceItem', $invoiceItem->getKey())
            ->first();

        $this->assertNotNull($deliveryItem);
        $this->assertEquals(1.0, (float) $deliveryItem->Quantity);

        $after = ItemStockSummary::query()
            ->where('ItemRef', $item->ItemID)
            ->where('StockRef', $stockRef)
            ->where('FiscalYearRef', config('sepidar.FiscalYearRef'))
            ->first();

        $this->assertEqualsWithDelta(
            (float) $before->OutputQuantity + 1,
            (float) $after->OutputQuantity,
            0.0001
        );
        $this->assertEqualsWithDelta(
            (float) $before->Quantity - 1,
            (float) $after->Quantity,
            0.0001
        );

        $this->expectException(\InvalidArgumentException::class);
        $service->createFromInvoice($invoice->fresh(['items', 'customer']));
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
