<?php

namespace App\Jobs\SetareganCo;

use App\Jobs\Notification\SendSmsMessageJob;
use App\Models\Crm\SetareganCo\CashBackRule;
use App\Models\SetareganCo\DiscountCode;
use App\Models\SetareganCo\Order;
use App\Support\PersianAmountFormatter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class CashBackDiscountCodeJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $orderId)
    {
    }

    public function handle(): void
    {
        $order = Order::query()->find($this->orderId);

        if (! $order || ! (bool) $order->IsPayed) {
            return;
        }

        $nationalCode = trim((string) $order->NationalCode);

        if ($nationalCode === '') {
            return;
        }

        $hasUnusedCode = DiscountCode::query()
            ->where('NationalCode', $nationalCode)
            ->where('Code', 'like', 'SRSCB%')
            ->where('RemainCount', '>', 0)
            ->exists();

        if ($hasUnusedCode) {
            return;
        }

        $totalAmount = (int) $order->TotalAmount;

        $rule = CashBackRule::query()
            ->where('start_amount', '<=', $totalAmount)
            ->where('end_amount', '>=', $totalAmount)
            ->orderBy('id')
            ->first();

        if (! $rule) {
            return;
        }

        $fromDate = now()->addDays($rule->activation_delay_days)->startOfDay();
        $toDate = $fromDate->copy()->addDays($rule->usage_duration_days)->endOfDay();

        $code = $this->generateUniqueCode();

        DiscountCode::query()->create([
            'ProductGroupId' => null,
            'ProductSubGroupId' => null,
            'ProductId' => null,
            'BrandId' => null,
            'Code' => $code,
            'FromDate' => $fromDate,
            'ToDate' => $toDate,
            'DiscountAmount' => $rule->cash_back_amount,
            'IsPercent' => $rule->is_percent,
            'RemainCount' => 1,
            'NationalCode' => $nationalCode,
            'ForSpecialOffer' => false,
            'Status' => true,
            'ForPackage' => false,
            'ShowInHomePage' => false,
        ]);

        $mobile = trim((string) $order->Mobile);

        if ($mobile === '') {
            return;
        }

        [$amountLabel, $amountCharacter] = $this->amountPlaceholders($rule);

        $message = CashBackGeneratorJob::buildMessage(
            $rule->sms_text ?: __('app.cash_back_sms'),
            (string) ($rule->site_url ?? ''),
            $code,
            (int) $rule->cash_back_amount,
            $fromDate,
            $toDate,
            trim((string) $order->CustomerName),
            (int) $rule->usage_duration_days,
            $amountLabel,
            $amountCharacter,
        );

        SendSmsMessageJob::dispatch($mobile, $message);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function amountPlaceholders(CashBackRule $rule): array
    {
        $amount = (int) $rule->cash_back_amount;

        if ($rule->is_percent) {
            $label = number_format($amount).'%';

            return [$label, $label.' '.__('app.percent')];
        }

        return [
            number_format($amount).' '.__('app.toman'),
            PersianAmountFormatter::formatCharacter($amount),
        ];
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = 'SRSCB'.Str::upper(Str::random(10));
        } while (DiscountCode::query()->where('Code', $code)->exists());

        return $code;
    }
}
