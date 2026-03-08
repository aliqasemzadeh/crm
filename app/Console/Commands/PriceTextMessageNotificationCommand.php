<?php

namespace App\Console\Commands;

use App\Jobs\Notification\SendSmsMessageJob;
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
        try {
            $usdtResponse = Http::timeout(15)->withoutVerifying()->withOptions(["verify"=>false])->get('https://apiv2.nobitex.ir/v3/orderbook/USDTIRT');
        } catch (\Exception $e) {
            $this->error(__('currencies.connection_error', ['error' => $e->getMessage()]));
            return;
        }

        if ($usdtResponse->failed()) {
            $this->error(__('currencies.fetch_failed'));
            return;
        }

        $usdtData = $usdtResponse->json();
        $usdt = $usdtData['lastTradePrice'] ?? null;


        try {
            $paxgResponse = Http::timeout(15)->withoutVerifying()->withOptions(["verify"=>false])->get('https://apiv2.nobitex.ir/v3/orderbook/PAXGUSDT');
        } catch (\Exception $e) {
            $this->error(__('currencies.connection_error', ['error' => $e->getMessage()]));
            return;
        }


        if ($paxgResponse->failed()) {
            $this->error(__('currencies.fetch_failed'));
            return;
        }

        $paxgData = $paxgResponse->json();
        $paxg = $paxgData['lastTradePrice'] ?? null;

        $message = "قیمت دلار:" . number_format(floor($usdt),0,'.',',') . " ریال " . PHP_EOL;
        $message .= "قیمت اونس:" . number_format(floor($paxg),0,'.',','). " دلار " . PHP_EOL;

        SendSmsMessageJob::dispatch("09177886099", trim($message));
        SendSmsMessageJob::dispatch("09177114358", trim($message));
    }
}
