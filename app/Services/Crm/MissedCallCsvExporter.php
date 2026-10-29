<?php

namespace App\Services\Crm;

use App\Models\Issabel\Cdr;
use App\Models\User;
use App\Services\Crm\Concerns\ResolvesUserExtensionKeys;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class MissedCallCsvExporter
{
    use ResolvesUserExtensionKeys;

    public const MAX_ROWS = 1500;

    /** @var list<string> */
    private const MISSED_DISPOSITIONS = [
        'NO ANSWER',
        'BUSY',
        'FAILED',
        'CONGESTION',
    ];

    /**
     * @param  Collection<int, User>  $users
     * @return array{csv: string, row_count: int, truncated: bool}
     */
    public function export(Collection $users, CarbonInterface $start, CarbonInterface $end): array
    {
        $keys = $this->extensionKeysForUsers($users);
        if ($keys === []) {
            return [
                'csv' => "calldate,src,dst,cnum,disposition,billsec\n",
                'row_count' => 0,
                'truncated' => false,
            ];
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
                ->where(function ($q): void {
                    foreach (self::MISSED_DISPOSITIONS as $disposition) {
                        $q->orWhereRaw('UPPER(TRIM(disposition)) = ?', [$disposition]);
                    }
                })
                ->orderByDesc('calldate')
                ->limit(self::MAX_ROWS + 1)
                ->get(['calldate', 'src', 'dst', 'cnum', 'disposition', 'billsec']);
        } catch (\Throwable $e) {
            report($e);

            return [
                'csv' => "calldate,src,dst,cnum,disposition,billsec\n",
                'row_count' => 0,
                'truncated' => false,
            ];
        }

        $truncated = $rows->count() > self::MAX_ROWS;
        if ($truncated) {
            $rows = $rows->take(self::MAX_ROWS);
        }

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['calldate', 'src', 'dst', 'cnum', 'disposition', 'billsec']);

        foreach ($rows as $row) {
            $calldate = $row->calldate;
            fputcsv($handle, [
                $calldate instanceof \DateTimeInterface
                    ? $calldate->format('Y-m-d H:i:s')
                    : (string) $calldate,
                (string) $row->src,
                (string) $row->dst,
                (string) $row->cnum,
                (string) $row->disposition,
                (int) $row->billsec,
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle) ?: "calldate,src,dst,cnum,disposition,billsec\n";
        fclose($handle);

        return [
            'csv' => $csv,
            'row_count' => $rows->count(),
            'truncated' => $truncated,
        ];
    }
}
