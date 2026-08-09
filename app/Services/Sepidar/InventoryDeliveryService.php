<?php

namespace App\Services\Sepidar;

use App\Models\Sepidar\GNR\Party;
use App\Models\Sepidar\INV\InventoryDelivery;
use App\Models\Sepidar\INV\InventoryDeliveryItem;
use App\Models\Sepidar\SLS\InvoiceItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;

class InventoryDeliveryService
{
    public function __construct(
        private readonly ItemStockEnsure $itemStockEnsure,
        private readonly ItemStockSummaryUpdater $stockSummaryUpdater,
    ) {}

    /**
     * @param  array{
     *     stock_ref: int,
     *     receiver_party_ref?: int|null,
     *     date: string,
     *     description?: string|null,
     *     items: list<array{
     *         item_ref: int,
     *         quantity: float|int,
     *         description?: string|null
     *     }>
     * }  $data
     */
    public function create(array $data): InventoryDelivery
    {
        return DB::connection('sqlsrv')->transaction(function () use ($data) {
            $deliveries = $this->createDeliveries($data);

            $stockKeys = $this->stockKeysFromDeliveries($deliveries);
            $this->stockSummaryUpdater->refresh($stockKeys);

            return $deliveries->first()->fresh(['items', 'items.item']);
        });
    }

    /**
     * @param  array{
     *     stock_ref: int,
     *     receiver_party_ref?: int|null,
     *     date: string,
     *     description?: string|null,
     *     items: list<array{
     *         item_ref: int,
     *         quantity: float|int,
     *         description?: string|null
     *     }>
     * }  $data
     */
    public function update(InventoryDelivery $delivery, array $data): InventoryDelivery
    {
        return DB::connection('sqlsrv')->transaction(function () use ($delivery, $data) {
            $oldKeys = $this->stockKeysForDelivery((int) $delivery->InventoryDeliveryID);

            $this->deleteDeliveryRecords((int) $delivery->InventoryDeliveryID);

            $deliveries = $this->createDeliveries($data);
            $newKeys = $this->stockKeysFromDeliveries($deliveries);

            $this->stockSummaryUpdater->refresh(array_merge($oldKeys, $newKeys));

            return $deliveries->first()->fresh(['items', 'items.item']);
        });
    }

    public function delete(int $inventoryDeliveryId): void
    {
        DB::connection('sqlsrv')->transaction(function () use ($inventoryDeliveryId) {
            $stockKeys = $this->stockKeysForDelivery($inventoryDeliveryId);
            $this->deleteDeliveryRecords($inventoryDeliveryId);
            $this->stockSummaryUpdater->refresh($stockKeys);
        });
    }

    /**
     * @return list<array{stock_ref: int, item_ref: int, tracing_ref: int|null, fiscal_year_ref: int}>
     */
    public function deleteForInvoice(int $invoiceId): array
    {
        $invoiceItemIds = InvoiceItem::query()
            ->where('InvoiceRef', $invoiceId)
            ->pluck('InvoiceItemID')
            ->all();

        if ($invoiceItemIds === []) {
            return [];
        }

        $deliveryItems = InventoryDeliveryItem::query()
            ->whereIn('BaseInvoiceItem', $invoiceItemIds)
            ->get(['InventoryDeliveryItemID', 'InventoryDeliveryRef', 'ItemRef', 'TracingRef']);

        if ($deliveryItems->isEmpty()) {
            return [];
        }

        $deliveryIds = $deliveryItems->pluck('InventoryDeliveryRef')->unique()->filter()->values()->all();

        $deliveries = InventoryDelivery::query()
            ->whereIn('InventoryDeliveryID', $deliveryIds)
            ->get(['InventoryDeliveryID', 'StockRef', 'FiscalYearRef'])
            ->keyBy('InventoryDeliveryID');

        $stockKeys = [];

        foreach ($deliveryItems as $item) {
            $header = $deliveries->get($item->InventoryDeliveryRef);

            if (! $header) {
                continue;
            }

            $stockKeys[] = [
                'stock_ref' => (int) $header->StockRef,
                'item_ref' => (int) $item->ItemRef,
                'tracing_ref' => $item->TracingRef !== null ? (int) $item->TracingRef : null,
                'fiscal_year_ref' => (int) $header->FiscalYearRef,
            ];
        }

        InventoryDeliveryItem::query()
            ->whereIn('BaseInvoiceItem', $invoiceItemIds)
            ->delete();

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

        return $stockKeys;
    }

    /**
     * @param  array{
     *     stock_ref: int,
     *     receiver_party_ref?: int|null,
     *     date: string,
     *     description?: string|null,
     *     items: list<array{
     *         item_ref: int,
     *         quantity: float|int,
     *         description?: string|null
     *     }>
     * }  $data
     * @return Collection<int, InventoryDelivery>
     */
    private function createDeliveries(array $data): Collection
    {
        $items = collect($data['items'])
            ->filter(fn (array $row) => (int) ($row['item_ref'] ?? 0) > 0 && (float) ($row['quantity'] ?? 0) > 0)
            ->values();

        if ($items->isEmpty()) {
            throw new \InvalidArgumentException('Inventory delivery requires at least one item line.');
        }

        $stockRef = (int) $data['stock_ref'];
        $pairs = $items->map(fn (array $row) => [
            'item_ref' => (int) $row['item_ref'],
            'stock_ref' => $stockRef,
        ])->all();

        $this->itemStockEnsure->ensure($pairs);

        $receiverDlRef = null;
        $receiverPartyRef = $data['receiver_party_ref'] ?? null;

        if ($receiverPartyRef) {
            $receiverDlRef = Party::query()->whereKey((int) $receiverPartyRef)->value('DLRef');
        }

        $creator = (int) (auth()->user()?->resolveSepidarCreatorId() ?? config('sepidar.Creator', 1));
        $now = now();
        $fiscalYearRef = (int) config('sepidar.FiscalYearRef');
        $type = (int) config('sepidar.DeliveryType', 1);
        $creatorForm = (int) config('sepidar.DeliveryCreatorForm', 1);
        $cogsSlRef = (int) config('sepidar.CogsSLRef', 262);
        $date = $this->parseDate((string) $data['date']);
        $description = $data['description'] ?? null;

        $nextDeliveryId = ((int) InventoryDelivery::query()->max('InventoryDeliveryID')) + 1;
        $nextDeliveryItemId = ((int) InventoryDeliveryItem::query()->max('InventoryDeliveryItemID')) + 1;
        $nextNumber = ((int) InventoryDelivery::query()
            ->where('FiscalYearRef', $fiscalYearRef)
            ->where('IsReturn', 0)
            ->max('Number')) + 1;

        $deliveryId = $nextDeliveryId;

        InventoryDelivery::query()->create([
            'InventoryDeliveryID' => $deliveryId,
            'IsReturn' => 0,
            'Type' => $type,
            'StockRef' => $stockRef,
            'ReceiverDLRef' => $receiverDlRef,
            'Number' => $nextNumber,
            'Date' => $date,
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
            'Description' => $description,
        ]);

        $rowNumber = 1;

        foreach ($items as $row) {
            $quantity = (float) $row['quantity'];

            InventoryDeliveryItem::query()->create([
                'InventoryDeliveryItemID' => $nextDeliveryItemId++,
                'InventoryDeliveryRef' => $deliveryId,
                'IsReturn' => 0,
                'RowNumber' => $rowNumber++,
                'BaseInvoiceItem' => null,
                'BaseInventoryDeliveryItem' => null,
                'BaseReturnedInvoiceItem' => null,
                'QuotationItemRef' => null,
                'ItemRef' => (int) $row['item_ref'],
                'TracingRef' => null,
                'Quantity' => $quantity,
                'SecondaryQuantity' => $quantity,
                'RemainingQuantity' => 0,
                'RemainingSecondaryQuantity' => null,
                'SLAccountRef' => $cogsSlRef,
                'Price' => null,
                'Description' => $row['description'] ?? null,
                'Description_En' => null,
                'Version' => 1,
                'ProductOrderRef' => null,
                'ParityCheck' => null,
                'WeighingRef' => null,
                'ItemRequestItemRef' => null,
                'ItemDescription' => null,
            ]);
        }

        return collect([InventoryDelivery::query()->findOrFail($deliveryId)]);
    }

    private function deleteDeliveryRecords(int $inventoryDeliveryId): void
    {
        InventoryDeliveryItem::query()
            ->where('InventoryDeliveryRef', $inventoryDeliveryId)
            ->delete();

        InventoryDelivery::query()
            ->where('InventoryDeliveryID', $inventoryDeliveryId)
            ->delete();
    }

    /**
     * @param  Collection<int, InventoryDelivery>  $deliveries
     * @return list<array{stock_ref: int, item_ref: int, tracing_ref: int|null, fiscal_year_ref: int}>
     */
    private function stockKeysFromDeliveries(Collection $deliveries): array
    {
        $keys = [];

        foreach ($deliveries as $delivery) {
            $delivery->loadMissing('items');

            foreach ($delivery->items as $item) {
                $keys[] = [
                    'stock_ref' => (int) $delivery->StockRef,
                    'item_ref' => (int) $item->ItemRef,
                    'tracing_ref' => $item->TracingRef !== null ? (int) $item->TracingRef : null,
                    'fiscal_year_ref' => (int) $delivery->FiscalYearRef,
                ];
            }
        }

        return $keys;
    }

    /**
     * @return list<array{stock_ref: int, item_ref: int, tracing_ref: int|null, fiscal_year_ref: int}>
     */
    private function stockKeysForDelivery(int $inventoryDeliveryId): array
    {
        $delivery = InventoryDelivery::query()->find($inventoryDeliveryId);

        if (! $delivery) {
            return [];
        }

        $delivery->loadMissing('items');

        return $this->stockKeysFromDeliveries(collect([$delivery]));
    }

    private function parseDate(string $date): string
    {
        $date = trim(str_replace('-', '/', $date));

        if (preg_match('/^\d{4}\/\d{1,2}\/\d{1,2}$/', $date)) {
            return Jalalian::fromFormat('Y/m/d', $date)->toCarbon()->startOfDay()->format('Y-m-d H:i:s');
        }

        return Jalalian::fromFormat('Y/m/d', Jalalian::now()->format('Y/m/d'))
            ->toCarbon()
            ->startOfDay()
            ->format('Y-m-d H:i:s');
    }
}
