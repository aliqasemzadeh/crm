<?php

namespace App\Console\Commands\Warehouse;

use App\Jobs\Notification\SendSmsMessageJob;
use Illuminate\Console\Command;

class DayCheckCreationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:day-check-creation-command';

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
        $warehouseUsers = config('main.warehouse_users', []);
        $userIds = array_keys($warehouseUsers);
        $users = \App\Models\User::whereIn('id', $userIds)->get();

        if ($users->isEmpty()) {
            $this->info('No users found in config main.warehouse_users');
            return;
        }

        $fiscalYearRef = config('sepidar.FiscalYearRef');
        $thirtyDaysAgo = now()->subDays(30);

        foreach ($users as $user) {
            $itemCount = $warehouseUsers[$user->id] ?? 30;

            // Get items checked by this user in the last 30 days
            $recentlyCheckedItemIds = \App\Models\Warehouse\DayCheck::where('user_id', $user->id)
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->get()
                ->pluck('items')
                ->flatten()
                ->unique()
                ->toArray();

            // Get items in stock that were not recently checked
            $items = \App\Models\Sepidar\INV\Item::whereInStock($fiscalYearRef)
                ->whereNotIn('ItemID', $recentlyCheckedItemIds)
                ->inRandomOrder()
                ->limit($itemCount)
                ->get();

            if ($items->count() < $itemCount) {
                // If not enough items, fill with any items in stock (excluding those already selected)
                $additionalItems = \App\Models\Sepidar\INV\Item::whereInStock($fiscalYearRef)
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
                $quantity = \App\Models\Sepidar\INV\ItemStockSummary::where('ItemRef', $item->ItemID)
                    ->where('FiscalYearRef', $fiscalYearRef)
                    ->sum('Quantity');
                $itemStocks[$item->ItemID] = (float) $quantity;
            }

            \App\Models\Warehouse\DayCheck::create([
                'user_id' => $user->id,
                'status' => 'check',
                'items' => $itemIds,
                'item_stocks' => $itemStocks,
            ]);

            $this->info("Created DayCheck for user: {$user->name} with " . count($itemIds) . " items.");

            // Send SMS notification with link to the warehouse check page
            if (!empty($user->mobile)) {
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
    }
}
