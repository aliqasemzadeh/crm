<?php

namespace App\Jobs\SetareganCo;

use App\Jobs\Notification\SendSmsMessageJob;
use App\Models\SetareganCo\DiscountCode;
use App\Models\SetareganCo\Order;
use App\Support\PersianAmountFormatter;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
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
        public bool $forSpecialOffer = false,
    ) {
    }

    public function handle(): void
    {
        $fromDate = Carbon::parse($this->fromDate)->startOfDay();

        $customers = self::eligibleCustomersQuery(
            $this->fromDate,
            $this->minOrderCount,
            $this->minTotalAmount,
        )->cursor();

        $created = 0;
        $skipped = 0;

        foreach ($customers as $customer) {
            $nationalCode = trim((string) $customer->NationalCode);

            if ($nationalCode === '') {
                $skipped++;
                continue;
            }

            if (self::hasUnusedCashBackCode($nationalCode)) {
                $skipped++;
                continue;
            }

            $createdCode = self::createCashBackCode(
                $nationalCode,
                $this->discountAmount,
                $this->usageDurationDays,
                $this->forSpecialOffer,
            );

            $created++;

            $mobile = trim((string) $customer->mobile);

            if ($mobile === '') {
                continue;
            }

            $message = self::buildMessage(
                $this->smsText,
                $this->siteUrl,
                $createdCode['code'],
                $this->discountAmount,
                $createdCode['from'],
                $createdCode['to'],
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
            'for_special_offer' => $this->forSpecialOffer,
        ]);
    }

    public static function eligibleCustomersQuery(
        string $fromDate,
        int $minOrderCount,
        int $minTotalAmount,
    ): Builder {
        $fromDate = Carbon::parse($fromDate)->startOfDay();

        return Order::query()
            ->where('IsPayed', 1)
            ->where('Date', '>=', $fromDate)
            ->whereNotNull('NationalCode')
            ->where('NationalCode', '!=', '')
            ->selectRaw('NationalCode, COUNT(*) as orders_count, SUM(TotalAmount) as total_amount, MAX(Mobile) as mobile, MAX(CustomerName) as customer_name')
            ->groupBy('NationalCode')
            ->havingRaw('COUNT(*) >= ? AND SUM(TotalAmount) >= ?', [$minOrderCount, $minTotalAmount]);
    }

    public static function estimateEligibleCustomerCount(
        string $fromDate,
        int $minOrderCount,
        int $minTotalAmount,
    ): int {
        $nationalCodes = self::eligibleCustomersQuery($fromDate, $minOrderCount, $minTotalAmount)
            ->pluck('NationalCode')
            ->map(fn ($code) => trim((string) $code))
            ->filter()
            ->unique()
            ->values();

        if ($nationalCodes->isEmpty()) {
            return 0;
        }

        $blockedCodes = DiscountCode::query()
            ->where('Code', 'like', 'SRSCB%')
            ->where('RemainCount', '>', 0)
            ->whereIn('NationalCode', $nationalCodes->all())
            ->pluck('NationalCode')
            ->map(fn ($code) => trim((string) $code))
            ->filter()
            ->unique();

        return $nationalCodes->diff($blockedCodes)->count();
    }

    public static function hasUnusedCashBackCode(string $nationalCode): bool
    {
        return DiscountCode::query()
            ->where('NationalCode', $nationalCode)
            ->where('Code', 'like', 'SRSCB%')
            ->where('RemainCount', '>', 0)
            ->exists();
    }

    /**
     * @return array{code: string, from: Carbon, to: Carbon}
     */
    public static function createCashBackCode(
        ?string $nationalCode,
        int $discountAmount,
        int $usageDurationDays,
        bool $forSpecialOffer = false,
    ): array {
        $from = now()->startOfDay();
        $to = $from->copy()->addDays($usageDurationDays)->endOfDay();
        $code = self::generateUniqueCode();

        DiscountCode::query()->create([
            'ProductGroupId' => null,
            'ProductSubGroupId' => null,
            'ProductId' => null,
            'BrandId' => null,
            'Code' => $code,
            'FromDate' => $from,
            'ToDate' => $to,
            'DiscountAmount' => $discountAmount,
            'IsPercent' => false,
            'RemainCount' => 1,
            'NationalCode' => $nationalCode !== null && $nationalCode !== '' ? $nationalCode : null,
            'ForSpecialOffer' => $forSpecialOffer,
            'Status' => true,
            'ForPackage' => false,
            'ShowInHomePage' => false,
        ]);

        return [
            'code' => $code,
            'from' => $from,
            'to' => $to,
        ];
    }

    public static function generateUniqueCode(): string
    {
        do {
            $code = 'SRSCB'.Str::upper(Str::random(10));
        } while (DiscountCode::query()->where('Code', $code)->exists());

        return $code;
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
        ?string $amountLabel = null,
        ?string $amountCharacter = null,
    ): string {
        $message = str_replace(
            [':code', ':amount_character', ':amount', ':from', ':to', ':name', ':duration_days', ':duration_hours'],
            [
                $code,
                $amountCharacter ?? PersianAmountFormatter::formatCharacter($discountAmount),
                $amountLabel ?? (number_format($discountAmount).' '.__('app.toman')),
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
}
