<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Jobs\Notification\BaleSendMessageJob;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BaleWebhookController extends Controller
{
    public function __invoke(Request $request, string $secret): Response
    {
        $configuredSecret = (string) config('bale.crm_bot_webhook_secret');

        if ($configuredSecret === '' || ! hash_equals($configuredSecret, $secret)) {
            abort(404);
        }

        try {
            $this->handleUpdate($request->all());
        } catch (\Throwable $e) {
            Log::error('Bale CRM webhook failed: '.$e->getMessage(), [
                'exception' => $e,
            ]);
        }

        return response()->noContent();
    }

    /**
     * @param  array<string, mixed>  $update
     */
    protected function handleUpdate(array $update): void
    {
        $message = $update['message'] ?? null;

        if (! is_array($message)) {
            return;
        }

        $text = trim((string) ($message['text'] ?? ''));
        $chatId = data_get($message, 'chat.id');

        if ($text === '' || $chatId === null || $chatId === '') {
            return;
        }

        if (! preg_match('/^\/start(?:@[A-Za-z0-9_]+)?(?:\s+([A-Za-z0-9_-]+))?$/u', $text, $matches)) {
            return;
        }

        $token = $matches[1] ?? null;

        if (! is_string($token) || $token === '') {
            return;
        }

        $cacheKey = 'bale:link:'.$token;
        $userId = Cache::pull($cacheKey);

        if (! $userId) {
            return;
        }

        $user = User::query()->find($userId);

        if (! $user) {
            return;
        }

        $chatIdString = (string) $chatId;

        User::query()
            ->where('bale_code', $chatIdString)
            ->where('id', '!=', $user->id)
            ->update(['bale_code' => null]);

        $user->bale_code = $chatIdString;
        $user->save();

        BaleSendMessageJob::dispatch(
            __('app.bale_bot_welcome_message', ['name' => $user->name]),
            'crm',
            $chatIdString
        );
    }
}
