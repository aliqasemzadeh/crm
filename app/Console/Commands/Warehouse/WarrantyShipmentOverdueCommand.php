<?php

namespace App\Console\Commands\Warehouse;

use App\Jobs\Notification\BaleSendMessageJob;
use App\Models\Calender\Day;
use App\Models\User;
use App\Models\Warehouse\WarrantyShipment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Morilog\Jalali\Jalalian;

class WarrantyShipmentOverdueCommand extends Command
{
    protected $signature = 'app:warehouse:warranty-shipment-overdue-command';

    protected $description = 'Remind warehouse staff about warranty shipments overdue more than 28 days';

    public function handle(): int
    {
        Log::info('Command app:warehouse:warranty-shipment-overdue-command started.');

        if (Day::isNonWorkingDay()) {
            $this->info('Today is a non-working day. Skipping command execution.');
            Log::info('Command app:warehouse:warranty-shipment-overdue-command skipped (non-working day).');

            return self::SUCCESS;
        }

        $shipments = WarrantyShipment::query()
            ->needsReminder(28)
            ->orderBy('sent_at')
            ->get();

        if ($shipments->isEmpty()) {
            $this->info('No overdue warranty shipments found.');
            Log::info('Command app:warehouse:warranty-shipment-overdue-command finished with no overdue items.');

            return self::SUCCESS;
        }

        $itemLines = $shipments->map(function (WarrantyShipment $shipment): string {
            return __('app.warehouse_warranty_overdue_bale_item', [
                'item' => $shipment->item_title,
                'company' => $shipment->warranty_company,
                'sent_at' => Jalalian::fromDateTime($shipment->sent_at)->format('Y/m/d'),
                'status' => $shipment->status->label(),
            ]);
        })->implode("\n");

        $message = __('app.warehouse_warranty_overdue_bale_message', [
            'count' => $shipments->count(),
            'items' => $itemLines,
            'url' => route('panels.warehouse.warranty-shipment.index'),
        ]);

        $sent = 0;

        User::role('warehouse')
            ->whereNotNull('bale_code')
            ->where('bale_code', '!=', '')
            ->select(['id', 'bale_code'])
            ->cursor()
            ->each(function (User $user) use ($message, &$sent): void {
                BaleSendMessageJob::dispatch($message, 'crm', (string) $user->bale_code);
                $sent++;
            });

        WarrantyShipment::query()
            ->whereIn('id', $shipments->pluck('id'))
            ->update(['last_reminded_at' => now()]);

        $this->info("Dispatched {$sent} Bale reminder(s) for {$shipments->count()} overdue shipment(s).");
        Log::info('Command app:warehouse:warranty-shipment-overdue-command finished.', [
            'shipments' => $shipments->count(),
            'bale_messages' => $sent,
        ]);

        return self::SUCCESS;
    }
}
