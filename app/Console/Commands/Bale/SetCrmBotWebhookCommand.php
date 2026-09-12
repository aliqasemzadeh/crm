<?php

namespace App\Console\Commands\Bale;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

#[Signature('app:bale:set-crm-bot-webhook')]
#[Description('Register SetareganCRMBot webhook URL with Bale API')]
class SetCrmBotWebhookCommand extends Command
{
    public function handle(): int
    {
        $token = (string) config('bale.crm_bot_token');
        $secret = (string) config('bale.crm_bot_webhook_secret');

        if ($token === '' || $secret === '') {
            $this->error('CRM_BALE_BOT_TOKEN or CRM_BALE_BOT_WEBHOOK_SECRET is missing.');

            return self::FAILURE;
        }

        $webhookUrl = url('/api/bale/webhook/'.$secret);

        try {
            $response = Http::withoutVerifying()
                ->withOptions(['verify' => false])
                ->post("http://tapi.bale.ai/bot{$token}/setWebhook", [
                    'url' => $webhookUrl,
                ]);

            $body = $response->json();

            if ($response->successful() && ($body['ok'] ?? false)) {
                $this->info('Webhook registered: '.$webhookUrl);
                Log::info('Bale CRM webhook registered.', ['url' => $webhookUrl, 'response' => $body]);

                return self::SUCCESS;
            }

            $this->error('Failed to register webhook: '.$response->body());
            Log::error('Bale CRM webhook registration failed.', [
                'url' => $webhookUrl,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error('Failed to register webhook: '.$e->getMessage());
            Log::error('Bale CRM webhook registration exception: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
