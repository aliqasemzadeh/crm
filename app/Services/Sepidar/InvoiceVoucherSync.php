<?php

namespace App\Services\Sepidar;

use App\Models\Sepidar\ACC\Voucher;
use App\Models\Sepidar\ACC\VoucherItem;
use App\Models\Sepidar\SLS\Invoice;
use App\Models\Sepidar\SLS\SaleType;
use Morilog\Jalali\Jalalian;

class InvoiceVoucherSync
{
    public function sync(Invoice $invoice): Voucher
    {
        $invoice->loadMissing(['customer']);

        $saleType = SaleType::query()->findOrFail((int) $invoice->SaleTypeRef);
        $lines = $this->buildLines($invoice, $saleType);
        $description = $this->description($invoice);

        $creator = (int) ($invoice->LastModifier ?: $invoice->Creator ?: config('sepidar.Creator', 1));
        $now = now();
        $fiscalYearRef = (int) ($invoice->FiscalYearRef ?: config('sepidar.FiscalYearRef'));
        $date = $invoice->Date;

        $voucher = null;

        if ($invoice->VoucherRef) {
            $voucher = Voucher::query()->find($invoice->VoucherRef);
        }

        if ($voucher) {
            VoucherItem::query()->where('VoucherRef', $voucher->VoucherId)->delete();

            $voucher->update([
                'Date' => $date,
                'Description' => $description,
                'Description_En' => $description,
                'LastModifier' => $creator,
                'LastModificationDate' => $now,
                'Version' => ((int) $voucher->Version) + 1,
            ]);
        } else {
            $voucherId = ((int) Voucher::query()->max('VoucherId')) + 1;
            $number = ((int) Voucher::query()
                ->where('FiscalYearRef', $fiscalYearRef)
                ->max('Number')) + 1;
            $dailyNumber = ((int) Voucher::query()
                ->where('FiscalYearRef', $fiscalYearRef)
                ->whereDate('Date', Jalalian::fromDateTime($date)->toCarbon()->toDateString())
                ->max('DailyNumber')) + 1;

            $voucher = Voucher::query()->create([
                'VoucherId' => $voucherId,
                'Number' => $number,
                'Date' => $date,
                'ReferenceNumber' => $number,
                'SecondaryNumber' => null,
                'State' => (int) config('sepidar.VoucherState', 1),
                'Type' => (int) config('sepidar.VoucherType', 2),
                'FiscalYearRef' => $fiscalYearRef,
                'Description' => $description,
                'Description_En' => $description,
                'Version' => 1,
                'Creator' => $creator,
                'CreationDate' => $now,
                'LastModifier' => $creator,
                'LastModificationDate' => $now,
                'DailyNumber' => $dailyNumber,
                'IssuerSystem' => (int) config('sepidar.VoucherIssuerSystem', 0),
                'IsMerged' => 0,
                'MergedIssuerSystem' => null,
            ]);

            $invoice->update(['VoucherRef' => $voucher->VoucherId]);
        }

        $nextItemId = ((int) VoucherItem::query()->max('VoucherItemId')) + 1;
        $issuerEntityName = (string) config('sepidar.InvoiceIssuerEntityName');

        foreach ($lines as $index => $line) {
            VoucherItem::query()->create([
                'VoucherItemId' => $nextItemId++,
                'VoucherRef' => $voucher->VoucherId,
                'RowNumber' => $index + 1,
                'AccountSLRef' => $line['account_sl_ref'],
                'DLRef' => $line['dl_ref'],
                'Debit' => $line['debit'],
                'Credit' => $line['credit'],
                'CurrencyRef' => null,
                'CurrencyRate' => null,
                'CurrencyDebit' => null,
                'CurrencyCredit' => null,
                'TrackingNumber' => null,
                'TrackingDate' => null,
                'IssuerEntityName' => $issuerEntityName,
                'IssuerEntityRef' => (int) $invoice->InvoiceId,
                'Description' => $line['description'],
                'Description_En' => $line['description'],
                'Version' => 1,
            ]);
        }

        return $voucher->fresh(['items']);
    }

    public function deleteForInvoice(Invoice $invoice): void
    {
        if (! $invoice->VoucherRef) {
            return;
        }

        VoucherItem::query()->where('VoucherRef', $invoice->VoucherRef)->delete();
        Voucher::query()->where('VoucherId', $invoice->VoucherRef)->delete();

        $invoice->update(['VoucherRef' => null]);
    }

    /**
     * @return list<array{account_sl_ref: int, dl_ref: int|null, debit: float, credit: float, description: string}>
     */
    public function buildLines(Invoice $invoice, SaleType $saleType): array
    {
        $price = (float) $invoice->Price;
        $discount = (float) $invoice->Discount;
        $tax = (float) $invoice->Tax;
        $netPrice = (float) ($invoice->NetPrice ?? ($price - $discount + $tax + (float) $invoice->Duty + (float) $invoice->Addition));
        $description = $this->description($invoice);
        $customerName = trim((string) ($invoice->CustomerRealName ?? $invoice->customer?->Name ?? ''));
        $lineDescription = $customerName !== ''
            ? $description.' به '.$customerName
            : $description;

        $lines = [];

        if ($netPrice > 0) {
            $lines[] = [
                'account_sl_ref' => (int) $invoice->SLRef,
                'dl_ref' => $invoice->customer?->DLRef ? (int) $invoice->customer->DLRef : null,
                'debit' => $netPrice,
                'credit' => 0.0,
                'description' => $description,
            ];
        }

        if ($price > 0) {
            $lines[] = [
                'account_sl_ref' => (int) $saleType->PartSalesSLRef,
                'dl_ref' => null,
                'debit' => 0.0,
                'credit' => $price,
                'description' => $lineDescription,
            ];
        }

        if ($discount > 0) {
            $lines[] = [
                'account_sl_ref' => (int) $saleType->PartSalesDiscountSLRef,
                'dl_ref' => null,
                'debit' => $discount,
                'credit' => 0.0,
                'description' => $lineDescription,
            ];
        }

        if ($tax > 0) {
            $lines[] = [
                'account_sl_ref' => (int) config('sepidar.TaxSLRef', 531),
                'dl_ref' => null,
                'debit' => 0.0,
                'credit' => $tax,
                'description' => $lineDescription,
            ];
        }

        return $lines;
    }

    private function description(Invoice $invoice): string
    {
        $date = $invoice->Date
            ? Jalalian::fromDateTime($invoice->Date)->format('Y/m/d')
            : Jalalian::now()->format('Y/m/d');

        $saleTypeTitle = SaleType::query()
            ->where('SaleTypeId', $invoice->SaleTypeRef)
            ->value('Title') ?? '';

        $typePart = $saleTypeTitle !== '' ? " نوع فروش {$saleTypeTitle}" : '';

        return "بابت فاكتور شماره {$invoice->Number} تاريخ {$date}{$typePart}";
    }
}
