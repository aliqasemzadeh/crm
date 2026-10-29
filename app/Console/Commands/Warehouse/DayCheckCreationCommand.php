<?php

namespace App\Console\Commands\Warehouse;

use App\Jobs\Notification\SendSmsMessageJob;
use App\Models\Calender\Day;
use App\Models\Sepidar\INV\Item;
use App\Models\Sepidar\INV\ItemStockSummary;
use App\Models\User;
use App\Models\Warehouse\DayCheck;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DayCheckCreationCommand extends Command
{
    protected $signature = 'app:day-check-creation-command';

    protected $description = 'Create daily warehouse stock-check tasks for warehouse staff';

    public function handle()
    {
        Log::info('Command app:day-check-creation-command started.');

        if (! config('main.active_day_check.warehouse')) {
            $this->info('Warehouse day check is disabled. Skipping command execution.');
            Log::info('Command app:day-check-creation-command skipped (warehouse inactive).');

            return;
        }

        if (Day::isNonWorkingDay()) {
            $this->info('Today is a non-working day (Friday or holiday). Skipping command execution.');
            Log::info('Command app:day-check-creation-command skipped (non-working day).');

            return;
        }

        $users = User::query()->withStaffFlag('warehouse')->get();

        if ($users->isEmpty()) {
            $this->info('No warehouse staff found (is_warehouse_staff).');

            return;
        }

        $fiscalYearRef = config('sepidar.FiscalYearRef');
        $thirtyDaysAgo = now()->subDays(30);

        foreach ($users as $user) {
            $itemCount = $user->warehouseCheckQuota();

            $recentlyCheckedItemIds = DayCheck::where('user_id', $user->id)
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->get()
                ->pluck('items')
                ->flatten()
                ->unique()
                ->toArray();

            $items = Item::whereInStock($fiscalYearRef)
                ->whereNotIn('ItemID', $recentlyCheckedItemIds)
                ->inRandomOrder()
                ->limit($itemCount)
                ->get();

            if ($items->count() < $itemCount) {
                $additionalItems = Item::whereInStock($fiscalYearRef)
                    ->whereNotIn('ItemID', array_merge($recentlyCheckedItemIds, $items->pluck('ItemID')->toArray()))
                    ->inRandomOrder()
                    ->limit($itemCount - $items->count())
                    ->get();

                $items = $items->concat($additionalItems);
            }

            if ($items->isEmpty()) {
                $this->warn("No items found for user: {$user->name}");

                continue;
            }

            $itemIds = $items->pluck('ItemID')->toArray();
            $itemStocks = [];

            foreach ($items as $item) {
                $quantity = ItemStockSummary::where('ItemRef', $item->ItemID)
                    ->where('FiscalYearRef', $fiscalYearRef)
                    ->sum('Quantity');
                $itemStocks[$item->ItemID] = (float) $quantity;
            }

            DayCheck::create([
                'user_id' => $user->id,
                'status' => 'check',
                'items' => $itemIds,
                'item_stocks' => $itemStocks,
            ]);

            $this->info("Created DayCheck for user: {$user->name} with ".count($itemIds).' items.');

            if (! empty($user->mobile)) {
                $link = route('panels.warehouse.check.index');
                $text = __('app.day_check.sms_message', [
                    'website_title' => __('app.website_title'),
                    'link' => $link,
                ]);

                SendSmsMessageJob::dispatch($user->mobile, $text);

                $this->info("SMS dispatched for user: {$user->name}");
            } else {
                $this->warn("No mobile number for user: {$user->name}");
            }
        }

        Log::info('Command app:day-check-creation-command finished.');
    }
}
