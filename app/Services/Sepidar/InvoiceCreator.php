<?php

namespace App\Services\Sepidar;

use App\Models\Sepidar\GNR\Party;
use App\Models\Sepidar\GNR\PartyAddress;
use App\Models\Sepidar\INV\ItemStockSummary;
use App\Models\Sepidar\SLS\Invoice;
use App\Models\Sepidar\SLS\InvoiceItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Morilog\Jalali\Jalalian;

class InvoiceCreator
{
    public function __construct(
        private readonly InvoiceInventoryDeliverySync $deliverySync,
        private readonly InvoiceVoucherSync $voucherSync,
        private readonly ItemStockSummaryUpdater $stockSummaryUpdater,
    ) {}

    /**
     * @param  array{
     *     customer_party_ref: int,
     *     sale_type_ref: int,
     *     date: string,
     *     description?: string|null,
     *     items: list<array{
     *         item_ref: int,
     *         quantity: float|int,
     *         fee: float|int,
     *         discount?: float|int,
     *         tax?: float|int,
     *         description?: string|null
     *     }>
     * }  $data
     */
    public function create(array $data): Invoice
    {
        $party = Party::query()->findOrFail($data['customer_party_ref']);
        $customerName = $this->partyDisplayName($party);
        $customerNameEn = $this->partyDisplayNameEn($party);
        $addressId = PartyAddress::query()
            ->where('PartyRef', $party->PartyId)
            ->orderBy('PartyAddressId')
            ->value('PartyAddressId');

        $date = $this->parseDate($data['date']);
        $now = now();
        $rate = 1;
        $creator = (int) (auth()->user()?->resolveSepidarCreatorId() ?? config('sepidar.Creator', 1));
        $fiscalYearRef = (int) config('sepidar.FiscalYearRef');
        $saleTypeRef = (int) $data['sale_type_ref'];

        $linePayloads = [];
        $totals = [
            'Price' => 0,
            'Discount' => 0,
            'Addition' => 0,
            'Tax' => 0,
            'Duty' => 0,
            'NetPrice' => 0,
        ];

        foreach (array_values($data['items']) as $index => $row) {
            $quantity = (float) $row['quantity'];
            $fee = (float) $row['fee'];
            $discount = (float) ($row['discount'] ?? 0);
            $price = $quantity * $fee;
            $tax = (float) ($row['tax'] ?? 0);
            $duty = 0;
            $addition = 0;
            $netPrice = $price - $discount + $addition + $tax + $duty;

            $stockRef = $this->resolveStockRef((int) $row['item_ref']);

            $linePayloads[] = [
                'RowID' => $index + 1,
                'ItemRef' => (int) $row['item_ref'],
                'TracingRef' => null,
                'StockRef' => $stockRef,
                'Quantity' => $quantity,
                'SecondaryQuantity' => $quantity,
                'Fee' => $fee,
                'Price' => $price,
                'PriceInBaseCurrency' => $price * $rate,
                'Discount' => $discount,
                'DiscountInBaseCurrency' => $discount * $rate,
                'DiscountItemGroupRef' => null,
                'PriceInfoDiscountRate' => 0,
                'PriceInfoPriceDiscount' => 0,
                'PriceInfoPercentDiscount' => 0,
                'CustomerDiscount' => 0,
                'CustomerDiscountRate' => 0,
                'AggregateAmountDiscountRate' => 0,
                'AggregateAmountPriceDiscount' => 0,
                'AggregateAmountPercentDiscount' => 0,
                'Addition' => $addition,
                'AdditionInBaseCurrency' => $addition * $rate,
                'Tax' => $tax,
                'TaxInBaseCurrency' => $tax * $rate,
                'Duty' => $duty,
                'DutyInBaseCurrency' => $duty * $rate,
                'AdditionFactor_VatEffective' => 0,
                'AdditionFactorInBaseCurrency_VatEffective' => 0,
                'AdditionFactor_VatIneffective' => 0,
                'AdditionFactorInBaseCurrency_VatIneffective' => 0,
                'NetPriceInBaseCurrency' => $netPrice * $rate,
                'Rate' => $rate,
                'QuotationItemRef' => null,
                'OrderItemRef' => null,
                'Description' => $row['description'] ?? null,
                'Description_En' => null,
                'DiscountInvoiceItemRef' => null,
                'ProductPackRef' => null,
                'ProductPackQuantity' => null,
                'BankFeeForCurrencySale' => 0,
                'BankFeeForCurrencySaleInBaseCurrency' => 0,
                'IsAggregateDiscountInvoiceItem' => 0,
                'TaxPayerCurrencyPurchaseRate' => 0,
            ];

            $totals['Price'] += $price;
            $totals['Discount'] += $discount;
            $totals['Addition'] += $addition;
            $totals['Tax'] += $tax;
            $totals['Duty'] += $duty;
            $totals['NetPrice'] += $netPrice;
        }

        return DB::connection('sqlsrv')->transaction(function () use (
            $data,
            $party,
            $customerName,
            $customerNameEn,
            $addressId,
            $date,
            $now,
            $rate,
            $creator,
            $fiscalYearRef,
            $saleTypeRef,
            $linePayloads,
            $totals
        ) {
            $invoiceId = ((int) Invoice::query()->max('InvoiceId')) + 1;
            $number = ((int) Invoice::query()
                ->where('FiscalYearRef', $fiscalYearRef)
                ->where('SaleTypeRef', $saleTypeRef)
                ->max('Number')) + 1;

            $invoice = Invoice::query()->create([
                'InvoiceId' => $invoiceId,
                'QuotationRef' => null,
                'OrderRef' => null,
                'CustomerPartyRef' => $party->PartyId,
                'CustomerRealName' => $customerName,
                'CustomerRealName_En' => $customerNameEn !== '' ? $customerNameEn : null,
                'SaleTypeRef' => $saleTypeRef,
                'PartyAddressRef' => $addressId,
                'Number' => $number,
                'Date' => $date,
                'CurrencyRef' => (int) config('sepidar.CurrencyRef', 1),
                'SLRef' => config('sepidar.InvoiceSLRef'),
                'DeliveryLocationRef' => (int) config('sepidar.DeliveryLocationRef', 1),
                'State' => (int) config('sepidar.InvoiceState', 1),
                'Price' => $totals['Price'],
                'PriceInBaseCurrency' => $totals['Price'] * $rate,
                'Discount' => $totals['Discount'],
                'DiscountInBaseCurrency' => $totals['Discount'] * $rate,
                'Addition' => $totals['Addition'],
                'AdditionInBaseCurrency' => $totals['Addition'] * $rate,
                'Tax' => $totals['Tax'],
                'TaxInBaseCurrency' => $totals['Tax'] * $rate,
                'Duty' => $totals['Duty'],
                'DutyInBaseCurrency' => $totals['Duty'] * $rate,
                'AdditionFactor_VatEffective' => 0,
                'AdditionFactorInBaseCurrency_VatEffective' => 0,
                'AdditionFactor_VatIneffective' => 0,
                'AdditionFactorInBaseCurrency_VatIneffective' => 0,
                'NetPriceInBaseCurrency' => $totals['NetPrice'] * $rate,
                'Rate' => $rate,
                'Version' => 1,
                'Creator' => $creator,
                'CreationDate' => $now,
                'LastModifier' => $creator,
                'LastModificationDate' => $now,
                'FiscalYearRef' => $fiscalYearRef,
                'VoucherRef' => null,
                'ShouldControlCustomerCredit' => 0,
                'Guid' => (string) Str::uuid(),
                'BaseOnInventoryDelivery' => 0,
                'AgreementRef' => null,
                'TaxPayerBillIssueDateTime' => null,
                'SettlementType' => (int) config('sepidar.SettlementType', 1),
                'Description' => $data['description'] ?? null,
                'SignatureRef' => null,
            ]);

            $nextItemId = ((int) InvoiceItem::query()->max('InvoiceItemId')) + 1;

            foreach ($linePayloads as $payload) {
                InvoiceItem::query()->create(array_merge($payload, [
                    'InvoiceItemId' => $nextItemId++,
                    'InvoiceRef' => $invoiceId,
                ]));
            }

            $invoice = $invoice->fresh(['items', 'customer']);

            $stockKeys = $this->deliverySync->sync($invoice);
            $this->voucherSync->sync($invoice->fresh(['customer']));
            $this->stockSummaryUpdater->refresh($stockKeys);

            return $invoice->fresh(['items']);
        });
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

    private function partyDisplayName(Party $party): string
    {
        return trim(implode(' ', array_filter([
            trim((string) ($party->Name ?? '')),
            trim((string) ($party->LastName ?? '')),
        ], static fn (string $part): bool => $part !== '')));
    }

    private function partyDisplayNameEn(Party $party): string
    {
        return trim(implode(' ', array_filter([
            trim((string) ($party->Name_En ?? '')),
            trim((string) ($party->LastName_En ?? '')),
        ], static fn (string $part): bool => $part !== '')));
    }

    private function resolveStockRef(int $itemRef): ?int
    {
        $fiscalYearRef = config('sepidar.FiscalYearRef');

        $summary = ItemStockSummary::query()
            ->where('ItemRef', $itemRef)
            ->where('FiscalYearRef', $fiscalYearRef)
            ->where('Quantity', '>', 0)
            ->orderByDesc('Quantity')
            ->first();

        $stockRef = $summary?->getAttribute('StockRef');

        if ($stockRef) {
            return (int) $stockRef;
        }

        $fallback = config('sepidar.DefaultStockRef');

        return $fallback !== null && $fallback !== '' ? (int) $fallback : null;
    }
}
