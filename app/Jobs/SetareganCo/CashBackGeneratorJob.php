<?php

namespace App\Jobs\SetareganCo;

use App\Jobs\Notification\SendSmsMessageJob;
use App\Models\SetareganCo\DiscountCode;
use App\Models\SetareganCo\Order;
use App\Support\PersianAmountFormatter;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Morilog\Jalali\Jalalian;

class CashBackGeneratorJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $fromDate,
        public int $minOrderCount,
        public int $minTotalAmount,
        public int $discountAmount,
        public int $usageDurationDays,
        public string $smsText,
        public string $siteUrl = '',
    ) {
    }

    public function handle(): void
    {
        $fromDate = Carbon::parse($this->fromDate)->startOfDay();
        $codeFromDate = now()->startOfDay();
        $codeToDate = $codeFromDate->copy()->addDays($this->usageDurationDays)->endOfDay();

        $customers = Order::query()
            ->where('IsPayed', 1)
            ->where('Date', '>=', $fromDate)
            ->whereNotNull('NationalCode')
            ->where('NationalCode', '!=', '')
            ->selectRaw('NationalCode, COUNT(*) as orders_count, SUM(TotalAmount) as total_amount, MAX(Mobile) as mobile, MAX(CustomerName) as customer_name')
            ->groupBy('NationalCode')
            ->havingRaw('COUNT(*) >= ? AND SUM(TotalAmount) >= ?', [$this->minOrderCount, $this->minTotalAmount])
            ->cursor();

        $created = 0;
        $skipped = 0;

        foreach ($customers as $customer) {
            $nationalCode = trim((string) $customer->NationalCode);

            if ($nationalCode === '') {
                $skipped++;
                continue;
            }

            $hasUnusedCode = DiscountCode::query()
                ->where('NationalCode', $nationalCode)
                ->where('Code', 'like', 'SRSCB%')
                ->where('RemainCount', '>', 0)
                ->exists();

            if ($hasUnusedCode) {
                $skipped++;
                continue;
            }

            $code = $this->generateUniqueCode();

            DiscountCode::query()->create([
                'ProductGroupId' => null,
                'ProductSubGroupId' => null,
                'ProductId' => null,
                'BrandId' => null,
                'Code' => $code,
                'FromDate' => $codeFromDate,
                'ToDate' => $codeToDate,
                'DiscountAmount' => $this->discountAmount,
                'IsPercent' => false,
                'RemainCount' => 1,
                'NationalCode' => $nationalCode,
                'ForSpecialOffer' => false,
                'Status' => true,
                'ForPackage' => false,
                'ShowInHomePage' => false,
            ]);

            $created++;

            $mobile = trim((string) $customer->mobile);

            if ($mobile === '') {
                continue;
            }

            $message = self::buildMessage(
                $this->smsText,
                $this->siteUrl,
                $code,
                $this->discountAmount,
                $codeFromDate,
                $codeToDate,
                trim((string) $customer->customer_name),
                $this->usageDurationDays,
            );

            SendSmsMessageJob::dispatch($mobile, $message);
        }

        Log::info('CashBackGeneratorJob finished.', [
            'created' => $created,
            'skipped' => $skipped,
            'from_date' => $fromDate->toDateTimeString(),
            'min_order_count' => $this->minOrderCount,
            'min_total_amount' => $this->minTotalAmount,
            'discount_amount' => $this->discountAmount,
        ]);
    }

    public static function buildMessage(
        string $smsText,
        string $siteUrl,
        string $code,
        int $discountAmount,
        Carbon $fromDate,
        Carbon $toDate,
        string $name,
        int $usageDurationDays,
    ): string {
        $message = str_replace(
            [':code', ':amount_character', ':amount', ':from', ':to', ':name', ':duration_days', ':duration_hours'],
            [
                $code,
                PersianAmountFormatter::formatCharacter($discountAmount),
                number_format($discountAmount).' '.__('app.toman'),
                Jalalian::fromDateTime($fromDate)->format('Y/m/d'),
                Jalalian::fromDateTime($toDate)->format('Y/m/d'),
                $name,
                $usageDurationDays.' '.__('app.day'),
                ($usageDurationDays * 24).' '.__('app.hour'),
            ],
            $smsText
        );

        $siteUrl = trim($siteUrl);

        if ($siteUrl !== '') {
            $message = rtrim($message).PHP_EOL.$siteUrl;
        }

        return $message;
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = 'SRSCB'.Str::upper(Str::random(10));
        } while (DiscountCode::query()->where('Code', $code)->exists());

        return $code;
    }
}
