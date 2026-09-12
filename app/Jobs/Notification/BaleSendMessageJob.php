<?php

namespace App\Jobs\Notification;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BaleSendMessageJob implements ShouldQueue
{
    use Queueable;

    /**
     * The message to send.
     *
     * @var string
     */
    protected $message;

    /**
     * Destination bot/group: price (default) or crm.
     *
     * @var string
     */
    protected $destination;

    /**
     * Optional override chat id (private chat or specific group).
     */
    protected ?string $chatId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $message, string $destination = 'price', ?string $chatId = null)
    {
        $this->message = $message;
        $this->destination = $destination;
        $this->chatId = $chatId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->destination === 'crm') {
            $token = config('bale.crm_bot_token');
            $chatId = $this->chatId ?: config('bale.crm_bot_group_chat_id');
        } else {
            $token = config('bale.bot_token');
            $chatId = $this->chatId ?: config('bale.bot_group_chat_id');
        }

        if (! $token || ! $chatId) {
            return;
        }

        try {
            Http::withoutVerifying()->withOptions(['verify' => false])->post("http://tapi.bale.ai/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $this->message,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send message to Bale: '.$e->getMessage());
        }
    }
}
