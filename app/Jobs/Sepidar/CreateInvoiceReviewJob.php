<?php

namespace App\Jobs\Sepidar;

use App\Enums\InvoiceReviewStatusEnum;
use App\Jobs\Notification\BaleSendMessageJob;
use App\Models\Crm\InvoiceReview;
use App\Models\Sepidar\INV\ItemStockSummary;
use App\Models\Sepidar\SLS\Invoice;
use App\Models\User;
use App\Services\Sepidar\PartyBalance;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Morilog\Jalali\Jalalian;

class CreateInvoiceReviewJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 300;

    public function __construct(public int $invoiceId) {}

    public function uniqueId(): string
    {
        return (string) $this->invoiceId;
    }

    public function handle(PartyBalance $partyBalance): void
    {
        $invoice = Invoice::query()
            ->with(['customer', 'items.item'])
            ->find($this->invoiceId);

        if (! $invoice) {
            return;
        }

        $customerName = $this->partyName($invoice);
        $balance = $invoice->CustomerPartyRef
            ? $partyBalance->forParty((int) $invoice->CustomerPartyRef)
            : [
                'debit' => 0.0,
                'credit' => 0.0,
                'uncashed_receipts' => 0.0,
                'uncashed_payments' => 0.0,
                'balance' => 0.0,
                'final_balance' => 0.0,
                'debtor' => 0.0,
                'creditor' => 0.0,
            ];

        $stockSnapshot = $this->buildStockSnapshot($invoice);

        $review = InvoiceReview::query()->firstOrCreate(
            ['sepidar_invoice_id' => $invoice->InvoiceId],
            [
                'invoice_number' => $invoice->Number,
                'customer_party_ref' => $invoice->CustomerPartyRef,
                'customer_name' => $customerName,
                'invoice_date' => $invoice->Date,
                'invoice_net_price' => $invoice->NetPrice,
                'status' => InvoiceReviewStatusEnum::PENDING,
                'balance_snapshot' => $balance,
                'stock_snapshot' => $stockSnapshot,
                'snapshot_at' => now(),
            ]
        );

        if (! $review->wasRecentlyCreated) {
            return;
        }

        $this->notifyAccountants($invoice, $review, $balance, $customerName);
    }

    /**
     * @return list<array{
     *     item_ref: int|null,
     *     code: string|null,
     *     title: string|null,
     *     invoice_quantity: float,
     *     stock_quantity: float,
     *     is_zero: bool,
     *     is_short: bool
     * }>
     */
    private function buildStockSnapshot(Invoice $invoice): array
    {
        $itemRefs = $invoice->items->pluck('ItemRef')->filter()->unique()->values();

        $stocks = ItemStockSummary::query()
            ->whereIn('ItemRef', $itemRefs)
            ->where('FiscalYearRef', config('sepidar.FiscalYearRef'))
            ->get()
            ->keyBy('ItemRef');

        $snapshot = [];

        foreach ($invoice->items as $item) {
            $stock = $stocks->get($item->ItemRef);
            $stockQty = (float) ($stock?->Quantity ?? 0);
            $invoiceQty = (float) ($item->Quantity ?? 0);

            $snapshot[] = [
                'item_ref' => $item->ItemRef,
                'code' => $item->item?->Code,
                'title' => $item->item?->Title,
                'invoice_quantity' => $invoiceQty,
                'stock_quantity' => $stockQty,
                'is_zero' => $stockQty <= 0,
                'is_short' => $stockQty < $invoiceQty,
            ];
        }

        return $snapshot;
    }

    /**
     * @param  array{
     *     debit: float,
     *     credit: float,
     *     uncashed_receipts: float,
     *     uncashed_payments: float,
     *     balance: float,
     *     final_balance: float,
     *     debtor: float,
     *     creditor: float
     * }  $balance
     */
    private function notifyAccountants(Invoice $invoice, InvoiceReview $review, array $balance, string $customerName): void
    {
        $finalBalance = (float) ($balance['final_balance'] ?? 0);
        $direction = match (true) {
            $finalBalance > 0 => __('app.debtor'),
            $finalBalance < 0 => __('app.creditor'),
            default => __('app.settled'),
        };

        $message = __('app.invoice_review_bale_accounting_message', [
            'number' => $invoice->Number ?? $review->invoice_number,
            'customer' => $customerName,
            'amount' => number_format((float) ($invoice->NetPrice ?? 0)),
            'balance' => number_format(abs($finalBalance)),
            'direction' => $direction,
            'date' => $invoice->Date
                ? Jalalian::fromDateTime($invoice->Date)->format('Y/m/d')
                : '-',
            'url' => route('panels.accounting.invoice.view', $invoice->InvoiceId),
        ]);

        User::role('accounting')
            ->whereNotNull('bale_code')
            ->where('bale_code', '!=', '')
            ->select(['id', 'bale_code'])
            ->cursor()
            ->each(function (User $user) use ($message): void {
                BaleSendMessageJob::dispatch($message, 'crm', (string) $user->bale_code);
            });
    }

    private function partyName(Invoice $invoice): string
    {
        $party = $invoice->customer;

        if (! $party) {
            return (string) ($invoice->CustomerRealName ?? '-');
        }

        return trim(implode(' ', array_filter([
            trim((string) ($party->Name ?? '')),
            trim((string) ($party->LastName ?? '')),
        ], static fn (string $part): bool => $part !== ''))) ?: (string) ($invoice->CustomerRealName ?? '-');
    }
}
