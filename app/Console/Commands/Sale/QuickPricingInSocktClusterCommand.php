<?php

namespace App\Console\Commands\Sale;

use App\Jobs\Sale\QuickPricingInSocktClusterJob;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('app:quick-pricing-in-stock-cluster-command')]
#[Description('Sync in-stock availability for item clusters without removing permanent membership')]
class QuickPricingInSocktClusterCommand extends Command
{
    public function handle(): int
    {
        Log::info('Command app:quick-pricing-in-stock-cluster-command started.');

        QuickPricingInSocktClusterJob::dispatch();

        Log::info('Command app:quick-pricing-in-stock-cluster-command finished.');

        return self::SUCCESS;
    }
}
