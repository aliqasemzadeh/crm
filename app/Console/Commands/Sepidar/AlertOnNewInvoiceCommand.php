<?php

namespace App\Console\Commands\Sepidar;

use App\Jobs\Sepidar\CheckInvoiceCommand;
use App\Jobs\Sepidar\CheckInvoiceJob;
use App\Models\LastRecordCheck;
use App\Models\Sepidar\SLS\Invoice;
use Illuminate\Console\Command;

class AlertOnNewInvoiceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sepidar:alert-on-new-invoice-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $lastRecord = LastRecordCheck::firstOrCreate(['model' => 'Models\Sepidar\SLS\Invoice']);
        $invoices = Invoice::query()
            ->where('id', '>', $lastRecord->last_record_id)
            ->orderBy('InvoiceId', 'desc')
            ->get();
        foreach ($invoices as $invoice) {
            CheckInvoiceJob::dispatch($invoice->InvoiceId);
        }
        if ($invoices->isNotEmpty()) {
            $lastRecord->last_record_id = $invoices->last()->InvoiceId;
            $lastRecord->save();
        }
    }
}
