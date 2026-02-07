<?php

namespace App\Jobs\Otp;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendOtpJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public string $mobile, public string $otp)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Prepare message text (translatable)
        $message = __('otp.sms_text', ['code' => $this->otp]);

        // Preserve original, normalize for providers that require 98 prefix
        [$originalMobile, $normalizedMobile] = $this->normalizeIranMobile($this->mobile);

        // 1) Try Bale Safir Gateway first
        $baleResponse = null;
        try {
            $baleResponse = $this->sendViaBale($normalizedMobile, (int) $this->otp);

            // Log attempt for traceability
            Log::info('Bale send attempt', [
                'to' => $normalizedMobile,
                'original' => $originalMobile,
                'response' => $baleResponse,
            ]);

            // If success, stop here
            if ($this->isBaleSuccess($baleResponse)) {
                // Log balance if present
                if (isset($baleResponse['balance'])) {
                    Log::info('Bale send success, balance updated.', [
                        'balance' => $baleResponse['balance'],
                    ]);
                }
                return;
            }

            // If user has no Bale (code=17 & type=3), fall back to SMS
            if ($this->isBaleNoAccount($baleResponse)) {
                $this->sendViaSabapnovin($normalizedMobile, $message, $originalMobile);

                return;
            }

            // Any other non-success: log and still try SMS as a safety net
            Log::warning('Bale send was not successful, falling back to SMS.', [
                'response' => $baleResponse,
            ]);
            $this->sendViaSabapnovin($normalizedMobile, $message, $originalMobile);
        } catch (\Throwable $e) {
            Log::error('Bale send exception, falling back to SMS: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            $this->sendViaSabapnovin($normalizedMobile, $message, $originalMobile);
        }
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
     * Send OTP via Bale Safir Gateway.
     * Implements the documented flow:
     * 1) Get access token using client_credentials
     * 2) POST send_otp with Bearer token
     */
    private function sendViaBale(string $to, int $otp): array
    {
        $base = rtrim((string) Config::get('bale.base_uri'), '/').'/';

        // Step 1: get access token
        $token = $this->getBaleAccessToken();
        if ($token === null || $token === '') {
            Log::warning('Bale access token could not be retrieved.');
            return [];
        }

        // Step 2: send OTP
        $endpoint = $base.'send_otp';

        $response = Http::asJson()
            ->timeout(10)
            ->withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/json',
            ])->post($endpoint, [
                'phone' => $to,
                'otp' => $otp,
            ]);

        return $response->json() ?? [];
    }

    /**
     * Retrieve access token from Bale Safir using client_credentials.
     */
    private function getBaleAccessToken(): ?string
    {
        $base = rtrim((string) Config::get('bale.base_uri'), '/').'/';
        $clientId = (string) Config::get('bale.client_id');
        $clientSecret = (string) Config::get('bale.client_secret');

        $endpoint = $base.'auth/token';
        Log::info('Bale EndPont:', [$endpoint]);
        try {
            $resp = Http::asForm()->post($endpoint, [
                'grant_type' => 'client_credentials',
                'client_secret' => $clientSecret,
                'scope' => 'read',
                'client_id' => $clientId,
            ]);

            $data = $resp->json();
            Log::info('Bale Data Access:', [$data]);
            if (! is_array($data)) {
                return null;
            }

            $token = $data['access_token'] ?? null;
            if (is_string($token) && $token !== '') {
                return $token;
            }

            return null;
        } catch (\Throwable $e) {
            Log::error('Error requesting Bale access token: '.$e->getMessage());
            return null;
        }
    }

    private function isBaleSuccess(array $resp): bool
    {
        // According to the provided spec, success shape contains balance
        if (isset($resp['balance'])) {
            return is_numeric($resp['balance']);
        }

        // Fallbacks
        $code = $resp['status']['code'] ?? $resp['code'] ?? null;
        if ($code !== null) {
            return (int) $code === 200;
        }

        return (bool) ($resp['ok'] ?? false);
    }

    private function isBaleNoAccount(array $resp): bool
    {
        $code = (int) ($resp['status']['code'] ?? $resp['code'] ?? 0);
        $type = (int) ($resp['status']['type'] ?? $resp['type'] ?? 0);

        return $code === 17 && $type === 3;
    }

    /**
     * Send SMS via Sabapnovin fallback method using provided gateway.
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
