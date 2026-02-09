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
            $response = Http::timeout(15)->get('https://api.tetherland.com/currencies');
        } catch (\Exception $e) {
            $this->error('Connection error: ' . $e->getMessage());
            return;
        }

        if ($response->failed()) {
            $this->error('Failed to fetch currency rates from Tetherland.');
            return;
        }

        $data = $response->json();
        $usdtPrice = $data['data']['currencies']['USDT']['price'] ?? null;

        if (!$usdtPrice) {
            $this->error('USDT price not found in the response.');
            return;
        }

        $todayStr = Carbon::today()->toDateString();

        // Save to database
        CurrencyRate::updateOrCreate(
            ['currency_code' => 'USDT', 'rate_date' => $todayStr],
            ['rate_irr' => $usdtPrice]
        );

        $this->info("USDT rate updated: {$usdtPrice} IRR");

        // Notification logic
        $now = Carbon::now('Asia/Tehran');

        // Check if it's Friday
        if ($now->isFriday()) {
            $this->info('Today is Friday. No notification sent.');
            return;
        }

        // Check time between 9:00 and 18:00
        if ($now->hour >= 9 && $now->hour < 18) {
            $diff24d = $data['data']['currencies']['USDT']['diff24d'] ?? '0';

            $message = "نرخ تتر (USDT): " . number_format($usdtPrice) . " ریال\n";
            $message .= "تغییرات ۲۴ ساعت: " . $diff24d . "%";

            BaleSendMessageJob::dispatch($message);
            $this->info('Notification dispatched to Bale.');
        } else {
            $this->info('Outside of notification hours (9-18) in Tehran.');
        }
    }
}
