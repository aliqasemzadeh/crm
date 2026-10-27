<?php

namespace App\Console\Commands\Sepidar;

use App\Jobs\Notification\NotifyLateInvoiceEditJob;
use App\Models\Crm\InvoiceEditWatermark;
use App\Models\LastRecordCheck;
use App\Models\Sepidar\SLS\Invoice;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AlertOnLateInvoiceEditCommand extends Command
{
    protected $signature = 'app:sepidar:alert-on-late-invoice-edit-command';

    protected $description = 'Alert accountants and administrators when an invoice older than 30 days is edited';

    private const LOOKBACK_SECONDS = 90;

    private const BATCH_LIMIT = 200;

    public function handle(): int
    {
        Log::info('Command app:sepidar:alert-on-late-invoice-edit-command started.');

        $cursor = LastRecordCheck::query()->firstOrCreate(
            ['model' => 'Models\Sepidar\SLS\Invoice:late-edit'],
            ['last_record_id' => 0],
        );

        if ((int) $cursor->last_record_id === 0) {
            $cursor->last_record_id = now()->timestamp;
            $cursor->save();

            Log::info('Command app:sepidar:alert-on-late-invoice-edit-command seeded cursor; skipping historical alerts.');

            return self::SUCCESS;
        }

        $since = Carbon::createFromTimestamp((int) $cursor->last_record_id)
            ->subSeconds(self::LOOKBACK_SECONDS);
        $threshold = now()->subDays(30);

        $invoices = Invoice::query()
            ->with(['customer', 'modifier'])
            ->where('CreationDate', '<=', $threshold)
            ->whereNotNull('LastModificationDate')
            ->where('LastModificationDate', '>', $since)
            ->orderBy('LastModificationDate')
            ->limit(self::BATCH_LIMIT)
            ->get();

        if ($invoices->isEmpty()) {
            $cursor->last_record_id = now()->timestamp;
            $cursor->save();

            Log::info('Command app:sepidar:alert-on-late-invoice-edit-command finished with no changes.');

            return self::SUCCESS;
        }

        $watermarks = InvoiceEditWatermark::query()
            ->whereIn('sepidar_invoice_id', $invoices->pluck('InvoiceId'))
            ->get()
            ->keyBy('sepidar_invoice_id');

        $notified = 0;

        foreach ($invoices as $invoice) {
            $modificationDate = Carbon::parse($invoice->LastModificationDate);
            $watermark = $watermarks->get($invoice->InvoiceId);

            if (
                $watermark
                && $watermark->last_modification_date
                && $modificationDate->lte($watermark->last_modification_date)
            ) {
                continue;
            }

            NotifyLateInvoiceEditJob::dispatch((int) $invoice->InvoiceId);
            $notified++;

            InvoiceEditWatermark::query()->updateOrCreate(
                ['sepidar_invoice_id' => $invoice->InvoiceId],
                [
                    'last_modification_date' => $modificationDate,
                    'last_notified_at' => now(),
                ],
            );
        }

        $maxModifiedTs = $invoices
            ->map(fn (Invoice $invoice): int => Carbon::parse($invoice->LastModificationDate)->timestamp)
            ->max();

        $cursor->last_record_id = max((int) $cursor->last_record_id, (int) $maxModifiedTs, now()->timestamp);
        $cursor->save();

        Log::info('Command app:sepidar:alert-on-late-invoice-edit-command finished.', [
            'scanned' => $invoices->count(),
            'notified' => $notified,
        ]);

        return self::SUCCESS;
    }
}
