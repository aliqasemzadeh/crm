<?php

namespace App\Jobs\Notification;

use App\Enums\InvoiceReviewStatusEnum;
use App\Models\Crm\InvoiceReview;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Morilog\Jalali\Jalalian;

class NotifyWarehouseOfApprovedInvoiceReviewJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $invoiceReviewId) {}

    public function handle(): void
    {
        $review = InvoiceReview::query()
            ->with('accountingReviewer')
            ->find($this->invoiceReviewId);

        if (! $review || $review->status !== InvoiceReviewStatusEnum::APPROVED) {
            return;
        }

        $message = __('app.invoice_review_bale_warehouse_message', [
            'number' => $review->invoice_number ?: '-',
            'customer' => $review->customer_name ?: '-',
            'amount' => number_format((float) ($review->invoice_net_price ?? 0)),
            'reviewer' => $review->accountingReviewer?->name ?: '-',
            'reviewed_at' => $review->accounting_reviewed_at
                ? Jalalian::fromDateTime($review->accounting_reviewed_at)->format('Y/m/d H:i')
                : '-',
            'url' => route('panels.accounting.invoice.view', $review->sepidar_invoice_id),
        ]);

        User::role('warehouse')
            ->whereNotNull('bale_code')
            ->where('bale_code', '!=', '')
            ->select(['id', 'bale_code'])
            ->cursor()
            ->each(function (User $user) use ($message): void {
                BaleSendMessageJob::dispatch($message, 'crm', (string) $user->bale_code);
            });
    }
}
