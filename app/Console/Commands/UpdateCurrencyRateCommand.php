<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\CurrencyRate;
use App\Jobs\Notification\BaleSendMessageJob;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class UpdateCurrencyRateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-currency-rate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch USDT rate and update database, then notify via Bale';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $response = Http::timeout(15)->get('http://api.tetherland.com/currencies');
        } catch (\Exception $e) {
            $this->error(__('currencies.connection_error', ['error' => $e->getMessage()]));
            return;
        }

        if ($response->failed()) {
            $this->error(__('currencies.fetch_failed'));
            return;
        }

        $data = $response->json();
        $usdt = $data['data']['currencies']['USDT'] ?? null;
        $usdtPrice = isset($usdt['price']) ? $usdt['price'] * 10 : null;

        if (!$usdtPrice) {
            $this->error(__('currencies.price_not_found'));
            return;
        }

        $todayStr = Carbon::today()->toDateString();

        // Save to database
        CurrencyRate::updateOrCreate(
            ['currency_code' => 'USDT', 'rate_date' => $todayStr],
            ['rate_irr' => $usdtPrice]
        );

        $this->info(__('currencies.updated_log', ['price' => $usdtPrice]));

        // Notification logic
        $now = Carbon::now('Asia/Tehran');

        // Check if it's Friday
        if ($now->isFriday()) {
            $this->info(__('currencies.friday_no_notification'));
            return;
        }

        // Check time between 9:00 and 18:00
        if ($now->hour >= 9 && $now->hour < 18) {
            $diff24d = $usdt['diff24d'] ?? '0';
            $diff7d = $usdt['diff7d'] ?? null;
            $diff30d = $usdt['diff30d'] ?? null;
            $minPrice = isset($usdt['last24hMin']) ? $usdt['last24hMin'] * 10 : null;
            $maxPrice = isset($usdt['last24hMax']) ? $usdt['last24hMax'] * 10 : null;

            $message = __('currencies.usdt_rate', ['price' => number_format($usdtPrice)]) . "\n";
            $message .= __('currencies.diff_24h', ['diff' => $diff24d]) . "\n";

            if ($diff7d) {
                $message .= __('currencies.diff_7d', ['diff' => $diff7d]) . "\n";
            }
            if ($diff30d) {
                $message .= __('currencies.diff_30d', ['diff' => $diff30d]) . "\n";
            }
            if ($minPrice) {
                $message .= __('currencies.min_price', ['price' => number_format($minPrice)]) . "\n";
            }
            if ($maxPrice) {
                $message .= __('currencies.max_price', ['price' => number_format($maxPrice)]) . "\n";
            }

            BaleSendMessageJob::dispatch(trim($message));
            $this->info(__('currencies.notification_dispatched'));
        } else {
            $this->info(__('currencies.outside_hours'));
        }
    }
}
