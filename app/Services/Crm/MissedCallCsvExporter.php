<?php

namespace App\Services\Crm;

use App\Models\Issabel\Cdr;
use App\Models\Sepidar\SLS\Invoice;
use App\Models\User;
use App\Services\Crm\Concerns\ResolvesUserExtensionKeys;
use App\Support\IranPhoneNumberNormalizer;
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

    public function __construct(
        private readonly PhoneDirectoryService $directory,
    ) {}

    /**
     * @param  Collection<int, User>  $users
     * @return array{csv: string, row_count: int, truncated: bool}
     */
    public function export(Collection $users, CarbonInterface $start, CarbonInterface $end): array
    {
        $header = 'calldate,external_number,party_name,party_id,had_invoice_same_day,src,dst,cnum,disposition,billsec';

        $keys = $this->extensionKeysForUsers($users);
        if ($keys === []) {
            return [
                'csv' => $header."\n",
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
                'csv' => $header."\n",
                'row_count' => 0,
                'truncated' => false,
            ];
        }

        $truncated = $rows->count() > self::MAX_ROWS;
        if ($truncated) {
            $rows = $rows->take(self::MAX_ROWS);
        }

        $metaMap = $this->directory->numberToPartyMetaMap();
        $invoicePairs = $this->invoicePairsForRows($rows, $metaMap);

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, [
            'calldate',
            'external_number',
            'party_name',
            'party_id',
            'had_invoice_same_day',
            'src',
            'dst',
            'cnum',
            'disposition',
            'billsec',
        ]);

        foreach ($rows as $row) {
            $calldate = $row->calldate;
            $dateString = $calldate instanceof \DateTimeInterface
                ? $calldate->format('Y-m-d H:i:s')
                : (string) $calldate;
            $day = $calldate instanceof \DateTimeInterface
                ? $calldate->format('Y-m-d')
                : substr($dateString, 0, 10);

            $external = $this->resolveExternalNumber($row);
            $meta = $external !== ''
                ? $this->directory->resolvePartyMeta($external, $metaMap)
                : null;
            $partyId = $meta['party_id'] ?? null;
            $hadInvoice = $partyId !== null && isset($invoicePairs[$partyId.'|'.$day]);

            fputcsv($handle, [
                $dateString,
                $external,
                $meta['name'] ?? '',
                $partyId ?? '',
                $hadInvoice ? '1' : '0',
                (string) $row->src,
                (string) $row->dst,
                (string) $row->cnum,
                (string) $row->disposition,
                (int) $row->billsec,
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle) ?: $header."\n";
        fclose($handle);

        return [
            'csv' => $csv,
            'row_count' => $rows->count(),
            'truncated' => $truncated,
        ];
    }

    /**
     * @param  Collection<int, Cdr>  $rows
     * @param  array<string, array{name: string, party_id: ?int}>  $metaMap
     * @return array<string, true> keys "partyId|Y-m-d"
     */
    private function invoicePairsForRows(Collection $rows, array $metaMap): array
    {
        $partyIds = [];
        $minDay = null;
        $maxDay = null;

        foreach ($rows as $row) {
            $external = $this->resolveExternalNumber($row);
            if ($external === '') {
                continue;
            }
            $meta = $this->directory->resolvePartyMeta($external, $metaMap);
            $partyId = $meta['party_id'] ?? null;
            if ($partyId === null) {
                continue;
            }
            $partyIds[$partyId] = true;

            $calldate = $row->calldate;
            $day = $calldate instanceof \DateTimeInterface
                ? $calldate->format('Y-m-d')
                : substr((string) $calldate, 0, 10);
            $minDay = $minDay === null || $day < $minDay ? $day : $minDay;
            $maxDay = $maxDay === null || $day > $maxDay ? $day : $maxDay;
        }

        if ($partyIds === [] || $minDay === null || $maxDay === null) {
            return [];
        }

        try {
            $pairs = [];
            Invoice::query()
                ->whereIn('CustomerPartyRef', array_keys($partyIds))
                ->whereDate('Date', '>=', $minDay)
                ->whereDate('Date', '<=', $maxDay)
                ->select(['CustomerPartyRef', 'Date'])
                ->get()
                ->each(function (Invoice $invoice) use (&$pairs): void {
                    $day = $invoice->Date instanceof \DateTimeInterface
                        ? $invoice->Date->format('Y-m-d')
                        : substr((string) $invoice->Date, 0, 10);
                    $pairs[((int) $invoice->CustomerPartyRef).'|'.$day] = true;
                });

            return $pairs;
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }

    private function resolveExternalNumber(Cdr $row): string
    {
        foreach ([(string) $row->cnum, (string) $row->src, (string) $row->dst] as $candidate) {
            $trim = trim($candidate);
            if ($trim === '') {
                continue;
            }
            $norm = IranPhoneNumberNormalizer::normalize($trim);
            if ($norm !== null && strlen(preg_replace('/\D+/', '', $norm) ?? '') >= 8) {
                return IranPhoneNumberNormalizer::formatForSepidar($norm) ?: $trim;
            }
            $digits = preg_replace('/\D+/', '', $trim) ?? '';
            if (strlen($digits) >= 8) {
                return $trim;
            }
        }

        return '';
    }
}
