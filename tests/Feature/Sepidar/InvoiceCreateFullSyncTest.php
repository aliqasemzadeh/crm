<?php

namespace Tests\Feature\Sepidar;

use App\Models\Sepidar\ACC\Voucher;
use App\Models\Sepidar\ACC\VoucherItem;
use App\Models\Sepidar\GNR\Party;
use App\Models\Sepidar\INV\InventoryDeliveryItem;
use App\Models\Sepidar\INV\Item;
use App\Models\Sepidar\INV\ItemStock;
use App\Models\Sepidar\INV\ItemStockSummary;
use App\Services\Sepidar\InvoiceCreator;
use Illuminate\Support\Facades\DB;
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
        $fiscalYearRef = (int) config('sepidar.FiscalYearRef');
        $previousMax = $this->maxVoucherNumberAndReference($fiscalYearRef);

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
            ->where('BaseInvoiceItem', $invoiceItem->getKey())
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
        $this->assertSame((int) $voucher->Number, (int) $voucher->ReferenceNumber);
        $this->assertGreaterThan($previousMax, (int) $voucher->ReferenceNumber);

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

    public function test_voucher_number_follows_max_of_number_and_reference_when_skewed(): void
    {
        [$party, $item] = $this->fixture();
        $fiscalYearRef = (int) config('sepidar.FiscalYearRef');
        $previousMax = $this->maxVoucherNumberAndReference($fiscalYearRef);
        $skewedReference = $previousMax + 50;

        $this->seedSkewedVoucher($fiscalYearRef, $previousMax, $skewedReference);

        $invoice = app(InvoiceCreator::class)->create([
            'customer_party_ref' => (int) $party->PartyId,
            'sale_type_ref' => 2,
            'date' => Jalalian::now()->format('Y/m/d'),
            'description' => 'CRM integration test skewed voucher number',
            'items' => [[
                'item_ref' => (int) $item->ItemID,
                'quantity' => 1,
                'fee' => 1000000,
                'discount' => 0,
                'tax' => 0,
            ]],
        ]);

        $voucher = Voucher::query()->findOrFail($invoice->VoucherRef);

        $this->assertSame((int) $voucher->Number, (int) $voucher->ReferenceNumber);
        $this->assertSame($skewedReference + 1, (int) $voucher->ReferenceNumber);
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

    private function maxVoucherNumberAndReference(int $fiscalYearRef): int
    {
        $row = DB::connection('sqlsrv')->selectOne(
            'SELECT ISNULL(MAX(Number), 0) AS MaxNumber,
                    ISNULL(MAX(ReferenceNumber), 0) AS MaxReference
             FROM ACC.Voucher
             WHERE FiscalYearRef = ?',
            [$fiscalYearRef]
        );

        return max((int) ($row->MaxNumber ?? 0), (int) ($row->MaxReference ?? 0));
    }

    private function seedSkewedVoucher(int $fiscalYearRef, int $number, int $referenceNumber): void
    {
        $voucherId = ((int) Voucher::query()->max('VoucherId')) + 1;
        $now = now()->format('Y-m-d H:i:s');

        Voucher::query()->create([
            'VoucherId' => $voucherId,
            'Number' => max(1, $number),
            'Date' => now()->startOfDay()->format('Y-m-d H:i:s'),
            'ReferenceNumber' => $referenceNumber,
            'SecondaryNumber' => null,
            'State' => (int) config('sepidar.VoucherState', 1),
            'Type' => (int) config('sepidar.VoucherType', 2),
            'FiscalYearRef' => $fiscalYearRef,
            'Description' => 'CRM test skewed voucher',
            'Description_En' => 'CRM test skewed voucher',
            'Version' => 1,
            'Creator' => (int) config('sepidar.Creator', 1),
            'CreationDate' => $now,
            'LastModifier' => (int) config('sepidar.Creator', 1),
            'LastModificationDate' => $now,
            'DailyNumber' => 1,
            'IssuerSystem' => (int) config('sepidar.VoucherIssuerSystem', 0),
            'IsMerged' => 0,
            'MergedIssuerSystem' => null,
        ]);
    }
}
