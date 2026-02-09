<?php

namespace App\Jobs\Notification;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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
     * Create a new job instance.
     */
    public function __construct(string $message)
    {
        $this->message = $message;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $token = config('bale.bot_token');
        $chatId = config('bale.bot_group_chat_id');

        if (!$token || !$chatId) {
            return;
        }

        \Illuminate\Support\Facades\Http::withoutVerifying()->withOptions(["verify"=>false])->post("http://tapi.bale.ai/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $this->message,
        ]);
    }
}
