<?php

namespace App\Console\Commands\SetareganCo;

use App\Jobs\SetareganCo\SendPaidOrderBaleNotificationJob;
use App\Models\LastRecordCheck;
use App\Models\SetareganCo\Order;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('app:setaregan-co:send-paid-order-bale-notification')]
#[Description('Check new SetareganCo paid orders and send Bale CRM group summaries')]
class SendPaidOrderBaleNotificationCommand extends Command
{
    private const LAST_RECORD_MODEL = 'Models\SetareganCo\Order:BalePaidNotification';

    public function handle(): int
    {
        Log::info('Command app:setaregan-co:send-paid-order-bale-notification started.');

        $existing = LastRecordCheck::query()
            ->where('model', self::LAST_RECORD_MODEL)
            ->first();

        if (! $existing) {
            $maxPaidId = (int) (Order::query()->where('IsPayed', 1)->max('Id') ?? 0);

            LastRecordCheck::query()->create([
                'model' => self::LAST_RECORD_MODEL,
                'last_record_id' => $maxPaidId,
            ]);

            Log::info('Command app:setaregan-co:send-paid-order-bale-notification initialized cursor.', [
                'last_record_id' => $maxPaidId,
            ]);

            return self::SUCCESS;
        }

        $orders = Order::query()
            ->with(['details.product'])
            ->where('Id', '>', $existing->last_record_id)
            ->where('IsPayed', 1)
            ->orderBy('Id')
            ->get();

        foreach ($orders as $order) {
            SendPaidOrderBaleNotificationJob::dispatch($order->Id);
        }

        if ($orders->last()?->Id) {
            $existing->last_record_id = $orders->last()->Id;
            $existing->save();
        }

        Log::info('Command app:setaregan-co:send-paid-order-bale-notification finished.', [
            'processed' => $orders->count(),
            'last_record_id' => $existing->last_record_id,
        ]);

        return self::SUCCESS;
    }
}
