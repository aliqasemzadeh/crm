<?php

namespace App\Services\Accounting;

use App\Models\Sepidar\FMK\FiscalYear;
use App\Models\Sepidar\INV\InventoryReceipt;
use App\Models\Sepidar\INV\InventoryReceiptItem;
use App\Models\Sepidar\INV\ItemStockSummary;
use App\Models\Sepidar\RPA\BankAccount;
use App\Models\Sepidar\RPA\PaymentCheque;
use App\Models\Sepidar\RPA\ReceiptCheque;
use App\Models\Sepidar\SLS\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;

class AccountingDashboardService
{
    public static function statsCacheKey(int|string $fiscalYearRef, int $month): string
    {
        return "accounting_dashboard_stats_fiscal_year_{$fiscalYearRef}_month_{$month}";
    }

    public static function clearCache(int|string|null $fiscalYearRef = null): void
    {
        $yearIds = $fiscalYearRef !== null
            ? [(int) $fiscalYearRef]
            : Cache::remember('sepidar_fiscal_year_ids', now()->addDay(), function () {
                return FiscalYear::query()->pluck('FiscalYearID')->all();
            });

        foreach ($yearIds as $yearId) {
            for ($month = 1; $month <= 12; $month++) {
                Cache::forget(self::statsCacheKey($yearId, $month));
            }

            Cache::forget("accounting_dashboard_stats_fiscal_year_{$yearId}");
        }
    }

    /**
     * @return array{
     *     totalSales: float,
     *     salesChangePercent: float|null,
     *     totalPurchases: float,
     *     purchaseChangePercent: float|null,
     *     inventoryBalance: float,
     *     creditorTotal: float,
     *     debtorTotal: float,
     *     receivableCheques: float,
     *     transferredCheques: float,
     *     periodBalance: float,
     *     fixedCosts: float,
     *     variableCosts: float,
     *     engagedCapital: float,
     *     operatingProfit: float,
     *     nonOperatingProfit: float,
     *     previousMonthSales: float,
     *     previousMonthPurchases: float
     * }
     */
    public function stats(int|string $fiscalYearRef, int $month): array
    {
        $month = max(1, min(12, $month));

        return Cache::rememberForever(self::statsCacheKey($fiscalYearRef, $month), function () use ($fiscalYearRef, $month) {
            [$start, $end] = $this->monthBounds($fiscalYearRef, $month);
            [$prevFy, $prevMonth] = $this->previousPeriod($fiscalYearRef, $month);
            [$prevStart, $prevEnd] = $this->monthBounds($prevFy, $prevMonth);

            $totalSales = $this->sumSales($fiscalYearRef, $start, $end);
            $previousMonthSales = $this->sumSales($prevFy, $prevStart, $prevEnd);
            $totalPurchases = $this->sumPurchases($fiscalYearRef, $start, $end);
            $previousMonthPurchases = $this->sumPurchases($prevFy, $prevStart, $prevEnd);

            $inventoryBalance = $this->inventoryBalance($fiscalYearRef);
            $partySplits = $this->partyBalanceSplits($fiscalYearRef, $end);
            $receivableCheques = (float) ReceiptCheque::query()->whereIn('State', [1, 5])->sum('Amount');
            $transferredCheques = (float) ReceiptCheque::query()->where('State', 2)->sum('Amount');
            $payableCheques = (float) PaymentCheque::query()->whereIn('State', [1, 2])->sum('Amount');
            $bankBalance = (float) BankAccount::query()->sum('Balance');

            $fixedCosts = $this->accountNets(
                $fiscalYearRef,
                $start,
                $end,
                config('sepidar.FixedCostAccountIds', [])
            );
            $variableCosts = $this->accountNets(
                $fiscalYearRef,
                $start,
                $end,
                config('sepidar.VariableCostAccountIds', [])
            );

            // Topic contribution = SUM(Credit - Debit); expenses/COGS naturally reduce profit.
            $operatingProfit = $this->topicContribution($fiscalYearRef, $start, $end, (int) config('sepidar.OperatingIncomeTopicRef', 255))
                + $this->topicContribution($fiscalYearRef, $start, $end, (int) config('sepidar.OtherOperatingIncomeTopicRef', 260))
                + $this->topicContribution($fiscalYearRef, $start, $end, (int) config('sepidar.CogsTopicRef', 265))
                + $this->topicContribution($fiscalYearRef, $start, $end, (int) config('sepidar.SgaTopicRef', 270));

            $nonOperatingProfit = $this->topicContribution($fiscalYearRef, $start, $end, (int) config('sepidar.NonOperatingTopicRef', 250))
                + $this->topicContribution($fiscalYearRef, $start, $end, (int) config('sepidar.FinancialExpenseTopicRef', 165));

            $debtorTotal = $partySplits['debtor_total'];
            $creditorTotal = $partySplits['creditor_total'];

            return [
                'totalSales' => $totalSales,
                'salesChangePercent' => $this->percentChange($totalSales, $previousMonthSales),
                'totalPurchases' => $totalPurchases,
                'purchaseChangePercent' => $this->percentChange($totalPurchases, $previousMonthPurchases),
                'inventoryBalance' => $inventoryBalance,
                'creditorTotal' => $creditorTotal,
                'debtorTotal' => $debtorTotal,
                'receivableCheques' => $receivableCheques,
                'transferredCheques' => $transferredCheques,
                'periodBalance' => $totalSales - $totalPurchases,
                'fixedCosts' => $fixedCosts,
                'variableCosts' => $variableCosts,
                'engagedCapital' => $inventoryBalance + $bankBalance + $debtorTotal + $receivableCheques - $creditorTotal - $payableCheques,
                'operatingProfit' => $operatingProfit,
                'nonOperatingProfit' => $nonOperatingProfit,
                'previousMonthSales' => $previousMonthSales,
                'previousMonthPurchases' => $previousMonthPurchases,
            ];
        });
    }

    private function sumSales(int|string $fiscalYearRef, ?Carbon $start, ?Carbon $end): float
    {
        if (! $start || ! $end) {
            return 0.0;
        }

        return (float) Invoice::query()
            ->where('FiscalYearRef', $fiscalYearRef)
            ->where('State', '<>', 2)
            ->whereBetween('Date', [$start, $end])
            ->sum('Price');
    }

    private function sumPurchases(int|string $fiscalYearRef, ?Carbon $start, ?Carbon $end): float
    {
        if (! $start || ! $end) {
            return 0.0;
        }

        return (float) InventoryReceipt::query()
            ->where('FiscalYearRef', $fiscalYearRef)
            ->whereBetween('Date', [$start, $end])
            ->sum('TotalPrice');
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

        if ($itemIds->isEmpty()) {
            return 0.0;
        }

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

    /**
     * @return array{debtor_total: float, creditor_total: float}
     */
    private function partyBalanceSplits(int|string $fiscalYearRef, ?Carbon $asOf): array
    {
        if (! $asOf) {
            return ['debtor_total' => 0.0, 'creditor_total' => 0.0];
        }

        $glRows = DB::connection('sqlsrv')->table('ACC.VoucherItem')
            ->join('ACC.Voucher', 'ACC.VoucherItem.VoucherRef', '=', 'ACC.Voucher.VoucherId')
            ->where('ACC.Voucher.FiscalYearRef', $fiscalYearRef)
            ->where('ACC.Voucher.Date', '<=', $asOf)
            ->whereNotNull('ACC.VoucherItem.DLRef')
            ->groupBy('ACC.VoucherItem.DLRef')
            ->selectRaw('ACC.VoucherItem.DLRef as DLRef, SUM(ACC.VoucherItem.Debit) as debit, SUM(ACC.VoucherItem.Credit) as credit')
            ->get()
            ->keyBy('DLRef');

        $receiptCheques = DB::connection('sqlsrv')->table('RPA.ReceiptCheque')
            ->whereIn('State', [1, 5])
            ->whereNotNull('DlRef')
            ->groupBy('DlRef')
            ->selectRaw('DlRef, SUM(Amount) as amount')
            ->pluck('amount', 'DlRef');

        $paymentCheques = DB::connection('sqlsrv')->table('RPA.PaymentCheque')
            ->whereIn('State', [1, 2])
            ->whereNotNull('DlRef')
            ->groupBy('DlRef')
            ->selectRaw('DlRef, SUM(Amount) as amount')
            ->pluck('amount', 'DlRef');

        $dlRefs = $glRows->keys()
            ->merge($receiptCheques->keys())
            ->merge($paymentCheques->keys())
            ->unique();

        $debtorTotal = 0.0;
        $creditorTotal = 0.0;

        foreach ($dlRefs as $dlRef) {
            $gl = $glRows->get($dlRef);
            $debit = (float) ($gl->debit ?? 0);
            $credit = (float) ($gl->credit ?? 0);
            $uncashedReceipts = (float) ($receiptCheques[$dlRef] ?? 0);
            $uncashedPayments = (float) ($paymentCheques[$dlRef] ?? 0);
            $final = ($debit + $uncashedPayments) - ($credit + $uncashedReceipts);

            if ($final > 0) {
                $debtorTotal += $final;
            } elseif ($final < 0) {
                $creditorTotal += abs($final);
            }
        }

        return [
            'debtor_total' => $debtorTotal,
            'creditor_total' => $creditorTotal,
        ];
    }

    /**
     * P&L contribution for a topic: SUM(Credit - Debit).
     * Income increases the value; expenses/COGS/returns decrease it.
     */
    private function topicContribution(int|string $fiscalYearRef, ?Carbon $start, ?Carbon $end, int $topicRef): float
    {
        if (! $start || ! $end || $topicRef < 1) {
            return 0.0;
        }

        $row = DB::connection('sqlsrv')->table('ACC.VoucherItem as vi')
            ->join('ACC.Voucher as v', 'vi.VoucherRef', '=', 'v.VoucherId')
            ->join('ACC.AccountTopic as at', 'vi.AccountSLRef', '=', 'at.AccountSLRef')
            ->where('at.TopicRef', $topicRef)
            ->where('v.FiscalYearRef', $fiscalYearRef)
            ->whereBetween('v.Date', [$start, $end])
            ->selectRaw('SUM(vi.Credit - vi.Debit) as net')
            ->value('net');

        return (float) ($row ?? 0);
    }

    /**
     * Expense amount for account list: SUM(Debit - Credit), shown as a positive cost.
     *
     * @param  list<int>  $accountIds
     */
    private function accountNets(int|string $fiscalYearRef, ?Carbon $start, ?Carbon $end, array $accountIds): float
    {
        if (! $start || ! $end || $accountIds === []) {
            return 0.0;
        }

        $row = DB::connection('sqlsrv')->table('ACC.VoucherItem as vi')
            ->join('ACC.Voucher as v', 'vi.VoucherRef', '=', 'v.VoucherId')
            ->whereIn('vi.AccountSLRef', $accountIds)
            ->where('v.FiscalYearRef', $fiscalYearRef)
            ->whereBetween('v.Date', [$start, $end])
            ->selectRaw('SUM(vi.Debit - vi.Credit) as net')
            ->value('net');

        return (float) ($row ?? 0);
    }

    private function percentChange(float $current, float $previous): ?float
    {
        if (abs($previous) < 0.00001) {
            return null;
        }

        return (($current - $previous) / abs($previous)) * 100;
    }

    /**
     * @return array{0: Carbon|null, 1: Carbon|null}
     */
    private function monthBounds(int|string $fiscalYearRef, int $month): array
    {
        $fiscalYear = FiscalYear::query()->find($fiscalYearRef);

        if (! $fiscalYear) {
            return [null, null];
        }

        $jalaliYear = (int) $fiscalYear->Title;

        if ($jalaliYear < 1300 || $month < 1 || $month > 12) {
            return [null, null];
        }

        $start = (new Jalalian($jalaliYear, $month, 1))->toCarbon()->startOfDay();
        $days = (new Jalalian($jalaliYear, $month, 1))->getMonthDays();
        $end = (new Jalalian($jalaliYear, $month, $days))->toCarbon()->endOfDay();

        if ($fiscalYear->StartDate) {
            $fyStart = Carbon::parse($fiscalYear->StartDate)->startOfDay();
            if ($start->lt($fyStart)) {
                $start = $fyStart;
            }
        }

        if ($fiscalYear->EndDate) {
            $fyEnd = Carbon::parse($fiscalYear->EndDate)->endOfDay();
            if ($end->gt($fyEnd)) {
                $end = $fyEnd;
            }
        }

        if ($start->gt($end)) {
            return [null, null];
        }

        return [$start, $end];
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function previousPeriod(int|string $fiscalYearRef, int $month): array
    {
        if ($month > 1) {
            return [(int) $fiscalYearRef, $month - 1];
        }

        $previousYear = FiscalYear::query()
            ->where('FiscalYearID', '<', $fiscalYearRef)
            ->orderByDesc('FiscalYearID')
            ->first();

        return [$previousYear ? (int) $previousYear->FiscalYearID : (int) $fiscalYearRef - 1, 12];
    }
}
