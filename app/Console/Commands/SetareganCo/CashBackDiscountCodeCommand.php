<?php

namespace App\Console\Commands\SetareganCo;

use App\Jobs\SetareganCo\CashBackDiscountCodeJob;
use App\Models\LastRecordCheck;
use App\Models\SetareganCo\Order;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('app:cash-back-discount-code-command')]
#[Description('Check new SetareganCo paid orders and create cash-back discount codes')]
class CashBackDiscountCodeCommand extends Command
{
    public function handle(): int
    {
        Log::info('Command app:cash-back-discount-code-command started.');

        $lastRecord = LastRecordCheck::query()->firstOrCreate(
            ['model' => 'Models\SetareganCo\Order'],
            ['last_record_id' => 204202]
        );

        $orders = Order::query()
            ->where('Id', '>', $lastRecord->last_record_id)
            ->orderBy('Id')
            ->get();

        foreach ($orders as $order) {
            CashBackDiscountCodeJob::dispatch($order->Id);
        }

        if ($orders->last()?->Id) {
            $lastRecord->last_record_id = $orders->last()->Id;
            $lastRecord->save();
        }

        Log::info('Command app:cash-back-discount-code-command finished.', [
            'processed' => $orders->count(),
            'last_record_id' => $lastRecord->last_record_id,
        ]);

        return self::SUCCESS;
    }
}
