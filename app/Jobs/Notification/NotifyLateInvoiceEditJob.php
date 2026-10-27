<?php

namespace App\Jobs\Notification;

use App\Models\Sepidar\SLS\Invoice;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Morilog\Jalali\Jalalian;

class NotifyLateInvoiceEditJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 300;

    public function __construct(public int $invoiceId) {}

    public function uniqueId(): string
    {
        return (string) $this->invoiceId;
    }

    public function handle(): void
    {
        $invoice = Invoice::query()
            ->with(['customer', 'modifier'])
            ->find($this->invoiceId);

        if (! $invoice) {
            return;
        }

        $customerName = $this->partyName($invoice);
        $modifierName = trim((string) ($invoice->modifier?->Name ?? '')) ?: '-';

        $replacements = [
            'number' => $invoice->Number ?? '-',
            'customer' => $customerName,
            'amount' => number_format((float) ($invoice->NetPrice ?? 0)),
            'created_at' => $invoice->CreationDate
                ? Jalalian::fromDateTime($invoice->CreationDate)->format('Y/m/d H:i')
                : '-',
            'modified_at' => $invoice->LastModificationDate
                ? Jalalian::fromDateTime($invoice->LastModificationDate)->format('Y/m/d H:i')
                : '-',
            'modifier' => $modifierName,
            'url' => route('panels.accounting.invoice.view', $invoice->InvoiceId),
        ];

        $baleMessage = __('app.late_invoice_edit_bale_message', $replacements);
        $smsMessage = __('app.late_invoice_edit_sms_message', $replacements);

        User::role(['accounting', 'administrator'])
            ->select(['id', 'bale_code', 'mobile'])
            ->cursor()
            ->each(function (User $user) use ($baleMessage, $smsMessage): void {
                if (filled($user->bale_code)) {
                    BaleSendMessageJob::dispatch($baleMessage, 'crm', (string) $user->bale_code);
                }

                if (filled($user->mobile)) {
                    SendSmsMessageJob::dispatch((string) $user->mobile, $smsMessage);
                }
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
