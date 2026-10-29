<?php

namespace App\Services\Crm;

use App\Models\Issabel\Cdr;
use App\Models\User;
use App\Services\Crm\Concerns\ResolvesUserExtensionKeys;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class HourlyCallPeakService
{
    use ResolvesUserExtensionKeys;

    /**
     * @param  Collection<int, User>  $users
     * @return list<array{hour: string, total: int, missed: int}>
     */
    public function forUsers(Collection $users, CarbonInterface $start, CarbonInterface $end): array
    {
        $points = [];
        for ($h = 0; $h < 24; $h++) {
            $points[$h] = [
                'hour' => sprintf('%02d', $h),
                'total' => 0,
                'missed' => 0,
            ];
        }

        $keys = $this->extensionKeysForUsers($users);
        if ($keys === []) {
            return array_values($points);
        }

        try {
            $rows = Cdr::query()
                ->validCalldate()
                ->onlyExternalCalls()
                ->where('calldate', '>=', $start->copy()->startOfDay()->format('Y-m-d H:i:s'))
                ->where('calldate', '<=', $end->copy()->endOfDay()->format('Y-m-d H:i:s'))
                ->where(function ($q) use ($keys): void {
                    $q->whereIn('src', $keys)
                        ->orWhereIn('dst', $keys)
                        ->orWhereIn('cnum', $keys);
                })
                ->selectRaw('HOUR(calldate) as hour_of_day')
                ->selectRaw('COUNT(*) as total_count')
                ->selectRaw("SUM(CASE WHEN UPPER(TRIM(disposition)) = 'ANSWERED' THEN 0 ELSE 1 END) as missed_count")
                ->groupByRaw('HOUR(calldate)')
                ->get();
        } catch (\Throwable $e) {
            report($e);

            return array_values($points);
        }

        foreach ($rows as $row) {
            $hour = (int) $row->hour_of_day;
            if ($hour < 0 || $hour > 23) {
                continue;
            }

            $points[$hour]['total'] = (int) $row->total_count;
            $points[$hour]['missed'] = (int) $row->missed_count;
        }

        return array_values($points);
    }
}
