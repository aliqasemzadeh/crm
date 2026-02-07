<?php

namespace App\Jobs\Notification;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendSmsMessageJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $mobile,
        public string $text
    ) 
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Normalize and send SMS via Sabapnovin
        [$originalMobile, $normalizedMobile] = $this->normalizeIranMobile($this->mobile);

        $this->sendViaSabapnovin($normalizedMobile, $this->text, $originalMobile);
    }

    /**
     * Normalize Iranian mobile to start with 98 while preserving original.
     * Returns [original, normalized].
     */
    private function normalizeIranMobile(string $mobile): array
    {
        $original = trim($mobile);

        // Remove spaces, dashes and plus sign
        $m = preg_replace('/[^0-9+]/', '', $original) ?? $original;

        // Convert leading +98 to 98
        if (str_starts_with($m, '+98')) {
            $m = substr($m, 1); // remove +
        }

        // If starts with 0, replace with 98
        if (str_starts_with($m, '0')) {
            $m = '98'.substr($m, 1);
        }

        // If already starts with 98, keep
        if (str_starts_with($m, '98')) {
            return [$original, $m];
        }

        // If starts with 9 and looks like 9xxxxxxxxx, prepend 98
        if (str_starts_with($m, '9') && strlen($m) >= 10) {
            $m = '98'.$m;
        }

        return [$original, $m];
    }

    /**
     * Send SMS via Sabapnovin using provided gateway.
     */
    private function sendViaSabapnovin(string $normalizedTo, string $text, string $originalTo): void
    {
        try {
            $request = Http::get(
                sprintf('https://api.sabanovin.com/v1/%s/sms/send.json', (string) Config::get('sms.api-key')),
                [
                    'gateway' => Config::get('sms.gateway'),
                    'to' => $normalizedTo,
                    'text' => $text.PHP_EOL."لغو 11",
                ]
            )->json();

            // Optional: info log and simple auditing
            Log::info('SMS send attempt via Sabapnovin', [
                'to' => $normalizedTo,
                'original' => $originalTo,
                'message' => $text,
                'response' => $request,
            ]);

            if (($request['status']['code'] ?? 0) != 200) {
                Log::error('Send SMS Error: '.($request['status']['message'] ?? 'unknown error'));
            }
        } catch (\Throwable $e) {
            Log::error('Failed to send SMS: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
