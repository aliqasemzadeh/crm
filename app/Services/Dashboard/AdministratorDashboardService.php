<?php

namespace App\Services\Dashboard;

use App\Models\Sepidar\INV\InventoryReceiptItem;
use App\Models\Sepidar\INV\ItemStockSummary;
use App\Models\Sepidar\RPA\BankAccount;
use App\Models\Sepidar\RPA\PaymentCheque;
use App\Models\Sepidar\RPA\PaymentHeader;
use App\Models\Sepidar\RPA\ReceiptCheque;
use App\Models\Sepidar\RPA\ReceiptHeader;
use App\Models\Sepidar\SLS\Invoice;
use Illuminate\Support\Facades\Cache;
use Morilog\Jalali\Jalalian;

class AdministratorDashboardService
{
    public static function statsCacheKey(int|string $fiscalYearRef): string
    {
        return "administrator_dashboard_stats_{$fiscalYearRef}";
    }

    public static function clearCache(int|string $fiscalYearRef): void
    {
        Cache::forget(self::statsCacheKey($fiscalYearRef));

        for ($month = 1; $month <= 12; $month++) {
            Cache::forget(BankAccountMonthBalanceService::cacheKey($fiscalYearRef).'_month_'.$month);
        }
    }

    /**
     * @return array{
     *     bankAccountsBalance: float,
     *     inventoryBalance: float,
     *     annualSalesTotal: float,
     *     annualSalesOfficial: float,
     *     annualSalesUnofficial: float,
     *     payableChequesBalance: float,
     *     receivableChequesBalance: float,
     *     salesChart: list<array{month: string, sales: float}>,
     *     receiptsPaymentsChart: list<array{month: string, receipts: float, expenses: float}>
     * }
     */
    public function stats(int|string $fiscalYearRef): array
    {
        return Cache::rememberForever(self::statsCacheKey($fiscalYearRef), function () use ($fiscalYearRef) {
            $salesAndReceipts = $this->salesAndReceiptStats($fiscalYearRef);

            return [
                'bankAccountsBalance' => (float) BankAccount::query()->sum('Balance'),
                'inventoryBalance' => $this->inventoryBalance($fiscalYearRef),
                'payableChequesBalance' => (float) PaymentCheque::query()->whereIn('State', [1, 2])->sum('Amount'),
                'receivableChequesBalance' => (float) ReceiptCheque::query()->whereIn('State', [1, 5])->sum('Amount'),
                ...$salesAndReceipts,
            ];
        });
    }

    /**
     * @return array{
     *     annualSalesTotal: float,
     *     annualSalesOfficial: float,
     *     annualSalesUnofficial: float,
     *     salesChart: list<array{month: string, sales: float}>,
     *     receiptsPaymentsChart: list<array{month: string, receipts: float, expenses: float}>
     * }
     */
    private function salesAndReceiptStats(int|string $fiscalYearRef): array
    {
        $monthlySales = array_fill(1, 12, 0.0);
        $monthlyOfficial = array_fill(1, 12, 0.0);
        $monthlyUnofficial = array_fill(1, 12, 0.0);
        $monthlyReceipts = array_fill(1, 12, 0.0);
        $monthlyExpenses = array_fill(1, 12, 0.0);

        $invoices = Invoice::query()
            ->where('FiscalYearRef', $fiscalYearRef)
            ->select(['Price', 'Date', 'SaleTypeRef'])
            ->get();

        foreach ($invoices as $invoice) {
            if (! $invoice->Date) {
                continue;
            }

            $month = Jalalian::fromDateTime($invoice->Date)->getMonth();
            $price = (float) $invoice->Price;
            $monthlySales[$month] += $price;

            if ((int) $invoice->SaleTypeRef === 1) {
                $monthlyOfficial[$month] += $price;
            } else {
                $monthlyUnofficial[$month] += $price;
            }
        }

        $receipts = ReceiptHeader::query()
            ->where('FiscalYearRef', $fiscalYearRef)
            ->select(['TotalAmount', 'Date'])
            ->get();

        foreach ($receipts as $receipt) {
            if (! $receipt->Date) {
                continue;
            }

            $month = Jalalian::fromDateTime($receipt->Date)->getMonth();
            $monthlyReceipts[$month] += (float) $receipt->TotalAmount;
        }

        $payments = PaymentHeader::query()
            ->where('FiscalYearRef', $fiscalYearRef)
            ->select(['TotalAmount', 'Date'])
            ->get();

        foreach ($payments as $payment) {
            if (! $payment->Date) {
                continue;
            }

            $month = Jalalian::fromDateTime($payment->Date)->getMonth();
            $monthlyExpenses[$month] += (float) $payment->TotalAmount;
        }

        $salesChart = [];
        $receiptsPaymentsChart = [];

        for ($i = 1; $i <= 12; $i++) {
            $monthLabel = __('app.jalali_months.'.$i);
            $salesChart[] = [
                'month' => $monthLabel,
                'sales' => $monthlySales[$i],
            ];
            $receiptsPaymentsChart[] = [
                'month' => $monthLabel,
                'receipts' => $monthlyReceipts[$i],
                'expenses' => $monthlyExpenses[$i],
            ];
        }

        $official = array_sum($monthlyOfficial);
        $unofficial = array_sum($monthlyUnofficial);

        return [
            'annualSalesTotal' => $official + $unofficial,
            'annualSalesOfficial' => $official,
            'annualSalesUnofficial' => $unofficial,
            'salesChart' => $salesChart,
            'receiptsPaymentsChart' => $receiptsPaymentsChart,
        ];
    }

    private function inventoryBalance(int|string $fiscalYearRef): float
    {
        $items = ItemStockSummary::query()
            ->where('FiscalYearRef', $fiscalYearRef)
            ->where('Quantity', '>', 0)
            ->whereHas('item', function ($query) {
                $query->where('CodingGroupRef', '!=', 584);
            })
            ->get();

        $itemIds = $items->pluck('ItemRef')->unique();

        $latestFees = InventoryReceiptItem::query()
            ->whereIn('ItemRef', $itemIds)
            ->whereIn('InventoryReceiptItemID', function ($query) {
                $query->selectRaw('MAX(InventoryReceiptItemID)')
                    ->from('INV.InventoryReceiptItem')
                    ->groupBy('ItemRef');
            })
            ->pluck('Fee', 'ItemRef');

        $balance = 0.0;

        foreach ($items as $item) {
            $fee = (float) ($latestFees[$item->ItemRef] ?? 0);
            $balance += $fee * (float) $item->Quantity;
        }

        return $balance;
    }
}
