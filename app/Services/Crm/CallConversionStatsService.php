<?php

namespace App\Services\Crm;

use App\Models\Issabel\Cdr;
use App\Models\Issabel\Device;
use App\Models\Sepidar\SLS\Invoice;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CallConversionStatsService
{
    private const CACHE_TTL_SECONDS = 90;

    /**
     * @param  Collection<int, User>  $users
     * @return array{
     *     totals: array{
     *         successful_calls: int,
     *         rejected_calls: int,
     *         invoices_count: int,
     *         rejection_rate: ?float,
     *         conversion_rate: ?float
     *     },
     *     rows: list<array{
     *         user_id: int,
     *         name: string,
     *         avatar_url: ?string,
     *         successful_calls: int,
     *         rejected_calls: int,
     *         invoices_count: int,
     *         rejection_rate: ?float,
     *         conversion_rate: ?float
     *     }>
     * }
     */
    public function forUsers(Collection $users, CarbonInterface $start, CarbonInterface $end): array
    {
        $userIds = $users->pluck('id')->sort()->values()->all();
        $cacheKey = sprintf(
            'crm.call_conversion_stats.v3.%s.%s.%s',
            $start->format('Y-m-d'),
            $end->format('Y-m-d'),
            md5(implode(',', $userIds))
        );

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($users, $start, $end): array {
            return $this->build($users, $start, $end);
        });
    }

    /**
     * @param  Collection<int, User>  $users
     * @return array{
     *     totals: array{
     *         successful_calls: int,
     *         rejected_calls: int,
     *         invoices_count: int,
     *         rejection_rate: ?float,
     *         conversion_rate: ?float
     *     },
     *     rows: list<array{
     *         user_id: int,
     *         name: string,
     *         avatar_url: ?string,
     *         successful_calls: int,
     *         rejected_calls: int,
     *         invoices_count: int,
     *         rejection_rate: ?float,
     *         conversion_rate: ?float
     *     }>
     * }
     */
    private function build(Collection $users, CarbonInterface $start, CarbonInterface $end): array
    {
        $users = $users->values();
        if ($users->isEmpty()) {
            return [
                'totals' => $this->emptyTotals(),
                'rows' => [],
            ];
        }

        $invoiceCounts = $this->invoiceCountsByCreator($users, $start, $end);

        $rows = [];
        $totalSuccessful = 0;
        $totalRejected = 0;
        $totalInvoices = 0;

        foreach ($users as $user) {
            $callCounts = $this->callCountsForUser($user, $start, $end);
            $successful = $callCounts['successful'];
            $rejected = $callCounts['rejected'];
            $creatorId = $user->sepidar_user_id ? (int) $user->sepidar_user_id : null;
            $invoices = $creatorId !== null ? (int) ($invoiceCounts[$creatorId] ?? 0) : 0;

            $totalSuccessful += $successful;
            $totalRejected += $rejected;
            $totalInvoices += $invoices;

            $rows[] = [
                'user_id' => (int) $user->id,
                'name' => $user->name,
                'avatar_url' => $user->getAvatarUrl(),
                'successful_calls' => $successful,
                'rejected_calls' => $rejected,
                'invoices_count' => $invoices,
                'rejection_rate' => $this->rejectionRate($rejected, $successful),
                'conversion_rate' => $this->conversionRate($invoices, $successful),
            ];
        }

        usort($rows, function (array $a, array $b): int {
            $aRate = $a['conversion_rate'];
            $bRate = $b['conversion_rate'];

            if ($aRate === null && $bRate === null) {
                return $b['successful_calls'] <=> $a['successful_calls'];
            }
            if ($aRate === null) {
                return 1;
            }
            if ($bRate === null) {
                return -1;
            }

            return $bRate <=> $aRate;
        });

        return [
            'totals' => [
                'successful_calls' => $totalSuccessful,
                'rejected_calls' => $totalRejected,
                'invoices_count' => $totalInvoices,
                'rejection_rate' => $this->rejectionRate($totalRejected, $totalSuccessful),
                'conversion_rate' => $this->conversionRate($totalInvoices, $totalSuccessful),
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @return array{
     *     successful_calls: int,
     *     rejected_calls: int,
     *     invoices_count: int,
     *     rejection_rate: ?float,
     *     conversion_rate: ?float
     * }
     */
    private function emptyTotals(): array
    {
        return [
            'successful_calls' => 0,
            'rejected_calls' => 0,
            'invoices_count' => 0,
            'rejection_rate' => null,
            'conversion_rate' => null,
        ];
    }

    /**
     * @param  Collection<int, User>  $users
     * @return array<int, int>
     */
    private function invoiceCountsByCreator(Collection $users, CarbonInterface $start, CarbonInterface $end): array
    {
        $creatorIds = $users
            ->pluck('sepidar_user_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($creatorIds === []) {
            return [];
        }

        try {
            return Invoice::query()
                ->whereIn('Creator', $creatorIds)
                ->whereDate('Date', '>=', $start->toDateString())
                ->whereDate('Date', '<=', $end->toDateString())
                ->selectRaw('Creator, COUNT(*) as aggregate_count')
                ->groupBy('Creator')
                ->pluck('aggregate_count', 'Creator')
                ->map(fn ($count): int => (int) $count)
                ->all();
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }

    /**
     * Counts across all internal extensions linked via user_internal_phones.
     *
     * @return array{successful: int, rejected: int}
     */
    private function callCountsForUser(User $user, CarbonInterface $start, CarbonInterface $end): array
    {
        $user->loadMissing('internalPhones');
        $devices = $user->internalPhoneDevices();
        if ($devices->isEmpty()) {
            return ['successful' => 0, 'rejected' => 0];
        }

        $keys = $this->extensionKeysFromDevices($devices);
        if ($keys === []) {
            return ['successful' => 0, 'rejected' => 0];
        }

        try {
            $row = Cdr::query()
                ->validCalldate()
                ->onlyExternalCalls()
                ->where('calldate', '>=', $start->copy()->startOfDay()->format('Y-m-d H:i:s'))
                ->where('calldate', '<=', $end->copy()->endOfDay()->format('Y-m-d H:i:s'))
                ->where(function ($q) use ($keys): void {
                    $q->whereIn('src', $keys)
                        ->orWhereIn('dst', $keys)
                        ->orWhereIn('cnum', $keys);
                })
                ->selectRaw("SUM(CASE WHEN UPPER(TRIM(disposition)) = 'ANSWERED' THEN 1 ELSE 0 END) as successful_count")
                ->selectRaw("SUM(CASE WHEN UPPER(TRIM(disposition)) = 'ANSWERED' THEN 0 ELSE 1 END) as rejected_count")
                ->first();

            return [
                'successful' => (int) ($row->successful_count ?? 0),
                'rejected' => (int) ($row->rejected_count ?? 0),
            ];
        } catch (\Throwable $e) {
            report($e);

            return ['successful' => 0, 'rejected' => 0];
        }
    }

    /**
     * @param  Collection<int, Device>  $devices
     * @return list<string>
     */
    private function extensionKeysFromDevices(Collection $devices): array
    {
        $keys = [];
        foreach ($devices as $device) {
            foreach ([trim((string) $device->user), trim((string) $device->dial)] as $col) {
                if ($col === '') {
                    continue;
                }

                $keys[] = $col;
                $digits = preg_replace('/\D+/', '', $col) ?? '';
                if ($digits !== '') {
                    $keys[] = $digits;
                    if (strlen($digits) <= 5) {
                        $stripped = ltrim($digits, '0');
                        if ($stripped !== '' && $stripped !== $digits) {
                            $keys[] = $stripped;
                        }
                    }
                }
            }
        }

        return array_values(array_unique(array_filter($keys)));
    }

    /** نرخ رد تماس = ردشده ÷ کل تماس‌ها × ۱۰۰ */
    private function rejectionRate(int $rejected, int $successful): ?float
    {
        $total = $rejected + $successful;
        if ($total <= 0) {
            return null;
        }

        return round(($rejected / $total) * 100, 1);
    }

    /** نرخ تبدیل به مشتری = فاکتور ÷ تماس موفق × ۱۰۰ */
    private function conversionRate(int $invoices, int $successfulCalls): ?float
    {
        if ($successfulCalls <= 0) {
            return null;
        }

        return round(($invoices / $successfulCalls) * 100, 1);
    }
}
