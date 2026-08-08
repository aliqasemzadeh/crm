<?php

namespace App\Services\Sepidar;

use App\Models\Sepidar\INV\InventoryDelivery;
use App\Models\Sepidar\INV\InventoryDeliveryItem;
use App\Models\Sepidar\SLS\Invoice;
use App\Models\Sepidar\SLS\InvoiceItem;
use Illuminate\Support\Collection;

class InvoiceInventoryDeliverySync
{
    public function __construct(
        private readonly ItemStockEnsure $itemStockEnsure,
    ) {}

    /**
     * @return list<array{stock_ref: int, item_ref: int, tracing_ref: int|null, fiscal_year_ref: int}>
     */
    public function sync(Invoice $invoice): array
    {
        $this->deleteForInvoice((int) $invoice->InvoiceId);

        $invoice->loadMissing(['items', 'customer']);

        /** @var Collection<int, InvoiceItem> $items */
        $items = $invoice->items
            ->filter(fn (InvoiceItem $item) => (int) $item->ItemRef > 0 && (int) ($item->StockRef ?? 0) > 0)
            ->values();

        if ($items->isEmpty()) {
            return [];
        }

        $pairs = $items->map(fn (InvoiceItem $item) => [
            'item_ref' => (int) $item->ItemRef,
            'stock_ref' => (int) $item->StockRef,
        ])->all();

        $this->itemStockEnsure->ensure($pairs);

        $receiverDlRef = $invoice->customer?->DLRef;
        $creator = (int) ($invoice->LastModifier ?: $invoice->Creator ?: config('sepidar.Creator', 1));
        $now = now();
        $fiscalYearRef = (int) ($invoice->FiscalYearRef ?: config('sepidar.FiscalYearRef'));
        $type = (int) config('sepidar.DeliveryType', 1);
        $creatorForm = (int) config('sepidar.DeliveryCreatorForm', 1);
        $cogsSlRef = (int) config('sepidar.CogsSLRef', 262);

        $grouped = $items->groupBy(fn (InvoiceItem $item) => (int) $item->StockRef);
        $stockKeys = [];

        $nextDeliveryId = ((int) InventoryDelivery::query()->max('InventoryDeliveryID')) + 1;
        $nextDeliveryItemId = ((int) InventoryDeliveryItem::query()->max('InventoryDeliveryItemID')) + 1;
        $nextNumber = ((int) InventoryDelivery::query()
            ->where('FiscalYearRef', $fiscalYearRef)
            ->where('IsReturn', 0)
            ->max('Number')) + 1;

        foreach ($grouped as $stockRef => $stockItems) {
            $deliveryId = $nextDeliveryId++;

            InventoryDelivery::query()->create([
                'InventoryDeliveryID' => $deliveryId,
                'IsReturn' => 0,
                'Type' => $type,
                'StockRef' => (int) $stockRef,
                'ReceiverDLRef' => $receiverDlRef,
                'Number' => $nextNumber++,
                'Date' => $invoice->Date,
                'TotalPrice' => 0,
                'AccountingVoucherRef' => null,
                'FiscalYearRef' => $fiscalYearRef,
                'DestinationStockRef' => null,
                'CreatorForm' => $creatorForm,
                'Creator' => $creator,
                'CreationDate' => $now,
                'LastModifier' => $creator,
                'LastModificationDate' => $now,
                'Version' => 1,
                'Description' => $invoice->Description,
            ]);

            $rowNumber = 1;

            foreach ($stockItems as $invoiceItem) {
                $quantity = (float) $invoiceItem->Quantity;

                InventoryDeliveryItem::query()->create([
                    'InventoryDeliveryItemID' => $nextDeliveryItemId++,
                    'InventoryDeliveryRef' => $deliveryId,
                    'IsReturn' => 0,
                    'RowNumber' => $rowNumber++,
                    'BaseInvoiceItem' => (int) $invoiceItem->InvoiceItemId,
                    'BaseInventoryDeliveryItem' => null,
                    'BaseReturnedInvoiceItem' => null,
                    'QuotationItemRef' => null,
                    'ItemRef' => (int) $invoiceItem->ItemRef,
                    'TracingRef' => $invoiceItem->TracingRef,
                    'Quantity' => $quantity,
                    'SecondaryQuantity' => $invoiceItem->SecondaryQuantity,
                    'RemainingQuantity' => 0,
                    'RemainingSecondaryQuantity' => null,
                    'SLAccountRef' => $cogsSlRef,
                    'Price' => null,
                    'Description' => $invoiceItem->Description,
                    'Description_En' => $invoiceItem->Description_En,
                    'Version' => 1,
                    'ProductOrderRef' => null,
                    'ParityCheck' => null,
                    'WeighingRef' => null,
                    'ItemRequestItemRef' => null,
                    'ItemDescription' => null,
                    'Fee' => null,
                ]);

                $stockKeys[] = [
                    'stock_ref' => (int) $stockRef,
                    'item_ref' => (int) $invoiceItem->ItemRef,
                    'tracing_ref' => $invoiceItem->TracingRef !== null ? (int) $invoiceItem->TracingRef : null,
                    'fiscal_year_ref' => $fiscalYearRef,
                ];
            }
        }

        return $stockKeys;
    }

    public function deleteForInvoice(int $invoiceId): void
    {
        $invoiceItemIds = InvoiceItem::query()
            ->where('InvoiceRef', $invoiceId)
            ->pluck('InvoiceItemId')
            ->all();

        if ($invoiceItemIds === []) {
            return;
        }

        $deliveryIds = InventoryDeliveryItem::query()
            ->whereIn('BaseInvoiceItem', $invoiceItemIds)
            ->pluck('InventoryDeliveryRef')
            ->unique()
            ->filter()
            ->values()
            ->all();

        InventoryDeliveryItem::query()
            ->whereIn('BaseInvoiceItem', $invoiceItemIds)
            ->delete();

        if ($deliveryIds === []) {
            return;
        }

        foreach ($deliveryIds as $deliveryId) {
            $remaining = InventoryDeliveryItem::query()
                ->where('InventoryDeliveryRef', $deliveryId)
                ->exists();

            if (! $remaining) {
                InventoryDelivery::query()
                    ->where('InventoryDeliveryID', $deliveryId)
                    ->delete();
            }
        }
    }
}
