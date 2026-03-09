<?php

namespace App\Console\Commands;

use App\Jobs\Notification\SendSmsMessageJob;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class PriceTextMessageNotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:price-text-message-notification-command';

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
        $phones = [
            "09177886099", "09177114358"
        ];
        $symbols = [
            'USDT' => [
                'title' => 'دلار',
                'pair' => 'USDTIRT',
                'currency' => 'ریال',
                'decimal' => 0
            ],
            'PAXG' => [
                'title' => 'اونس',
                'pair' => 'PAXGUSDT',
                'currency' => 'دلار',
                'decimal' => 0
            ],
            'BTC' => [
                'title' => 'BTC',
                'pair' => 'BTCUSDT',
                'currency' => 'دلار',
                'decimal' => 0
            ],
            'XRP' => [
                'title' => 'XRP',
                'pair' => 'XRPUSDT',
                'currency' => 'دلار',
                'decimal' => 3
            ],
        ];
        $message = "";
        $now = Carbon::now('Asia/Tehran');

        // Check if it's Friday
        if ($now->isFriday()) {
            $this->info(__('currencies.friday_no_notification'));
            return;
        }
        if ($now->hour >= 7 && $now->hour < 21) {
            foreach ($symbols as $symbol) {
                try {
                    $response = Http::timeout(15)->withoutVerifying()->withOptions(["verify"=>false])->get('https://apiv2.nobitex.ir/v3/orderbook/'.$symbol['pair']);
                } catch (\Exception $e) {
                    $this->error(__('currencies.connection_error', ['error' => $e->getMessage()]));
                    return;
                }

                if ($response->failed()) {
                    $this->error(__('currencies.fetch_failed'));
                    return;
                }

                $data = $response->json();
                $price = $data['lastTradePrice'] ?? null;


                $message .=  $symbol['title'] .":" . number_format($price, $symbol['decimal'],'.',',') . PHP_EOL;
            }
            foreach($phones as $phone) {
                SendSmsMessageJob::dispatch($phone, trim($message));
            }
        }
    }
}
