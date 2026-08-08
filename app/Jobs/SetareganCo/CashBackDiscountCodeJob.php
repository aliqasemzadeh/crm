<?php

namespace App\Jobs\SetareganCo;

use App\Jobs\Notification\SendSmsMessageJob;
use App\Models\Crm\SetareganCo\CashBackRule;
use App\Models\SetareganCo\DiscountCode;
use App\Models\SetareganCo\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;
use Morilog\Jalali\Jalalian;

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

        $amountLabel = $rule->is_percent
            ? number_format($rule->cash_back_amount).'%'
            : number_format($rule->cash_back_amount).' '.__('app.toman');

        $message = __('app.cash_back_sms', [
            'amount' => $amountLabel,
            'code' => $code,
            'from' => Jalalian::fromDateTime($fromDate)->format('Y/m/d'),
            'to' => Jalalian::fromDateTime($toDate)->format('Y/m/d'),
        ]);

        SendSmsMessageJob::dispatch($mobile, $message);
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = 'SRSCB'.Str::upper(Str::random(10));
        } while (DiscountCode::query()->where('Code', $code)->exists());

        return $code;
    }
}
