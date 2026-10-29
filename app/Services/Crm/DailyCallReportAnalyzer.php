<?php

namespace App\Services\Crm;

use App\Ai\Agents\CallConversationAnalyst;
use App\Models\Crm\CallReport;
use App\Models\Crm\CallReportParty;
use App\Models\Issabel\Cdr;
use App\Models\Sepidar\SLS\Invoice;
use App\Support\IranPhoneNumberNormalizer;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Transcription;
use Throwable;

class DailyCallReportAnalyzer
{
    public function __construct(
        private readonly PhoneDirectoryService $directory,
        private readonly IssabelRecordingFetcher $recordings,
    ) {}

    public function analyze(CarbonInterface $reportDate): CallReport
    {
        $day = $reportDate->copy()->startOfDay();
        $topCount = max(1, (int) config('crm.call_report.top_count', 3));

        $report = CallReport::query()->firstOrCreate(
            ['report_date' => $day->toDateString()],
            [
                'status' => 'pending',
                'top_count' => $topCount,
            ]
        );

        $report->update([
            'status' => 'processing',
            'top_count' => $topCount,
            'error' => null,
        ]);

        $report->parties()->delete();

        try {
            $topPhones = $this->topExternalPhones($day, $topCount);

            if ($topPhones === []) {
                $report->update([
                    'status' => 'completed',
                    'summary' => __('app.call_report_empty_day'),
                ]);

                return $report->fresh(['parties']) ?? $report;
            }

            $lookbackStart = $day->copy()->endOfDay()->subHours(
                (int) config('crm.call_report.invoice_lookback_hours', 48)
            );

            $rank = 1;
            foreach ($topPhones as $phone => $callCount) {
                $this->analyzeParty($report, $rank, (string) $phone, (int) $callCount, $day, $lookbackStart);
                $rank++;
            }

            $report->update([
                'status' => 'completed',
                'summary' => __('app.call_report_completed_summary', [
                    'count' => count($topPhones),
                    'date' => $day->toDateString(),
                ]),
            ]);
        } catch (Throwable $e) {
            report($e);
            $report->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);
        }

        return $report->fresh(['parties']) ?? $report;
    }

    /**
     * @return array<string, int> phone => call_count
     */
    private function topExternalPhones(CarbonInterface $day, int $topCount): array
    {
        $start = $day->copy()->startOfDay()->format('Y-m-d H:i:s');
        $end = $day->copy()->endOfDay()->format('Y-m-d H:i:s');

        $rows = Cdr::query()
            ->validCalldate()
            ->onlyExternalCalls()
            ->where('calldate', '>=', $start)
            ->where('calldate', '<=', $end)
            ->get(['src', 'dst', 'cnum', 'calldate', 'billsec', 'disposition', 'uniqueid']);

        $counts = [];
        foreach ($rows as $row) {
            $external = $this->resolveExternalNumber($row);
            if ($external === '') {
                continue;
            }
            $key = $this->canonicalPhoneKey($external);
            if ($key === '') {
                continue;
            }
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        arsort($counts);

        return array_slice($counts, 0, $topCount, true);
    }

    private function analyzeParty(
        CallReport $report,
        int $rank,
        string $phone,
        int $callCount,
        CarbonInterface $day,
        CarbonInterface $lookbackStart,
    ): void {
        $meta = $this->directory->resolvePartyMeta($phone);
        $partyId = $meta['party_id'] ?? null;
        $partyName = $meta['name'] ?? null;

        $hasInvoice = false;
        $invoiceSummary = null;
        $identityNote = null;
        $outcome = 'pending';

        if ($partyId === null) {
            $identityNote = __('app.call_report_unknown_party_note', ['phone' => $phone]);
            $outcome = 'unknown_party';
        } else {
            $invoiceData = $this->invoiceInLookback((int) $partyId, $lookbackStart, $day->copy()->endOfDay());
            $hasInvoice = $invoiceData['exists'];
            $invoiceSummary = $invoiceData['summary'];
        }

        $transcripts = [];
        $analysis = null;

        try {
            $transcripts = $this->transcribePhoneCalls($phone, $day);

            if ($transcripts === []) {
                if ($outcome !== 'unknown_party') {
                    $outcome = 'no_audio';
                }
            } else {
                $analysis = $this->analyzeTranscripts($phone, $partyName, $transcripts, $hasInvoice);

                if ($partyId === null) {
                    $outcome = 'unknown_party';
                } elseif ($hasInvoice) {
                    $outcome = 'sold';
                } else {
                    $outcome = 'no_invoice';
                }
            }
        } catch (Throwable $e) {
            report($e);
            $outcome = 'failed';
            $identityNote = trim(($identityNote ?? '').' '.__('app.call_report_party_failed', ['error' => $e->getMessage()]));
        }

        CallReportParty::query()->create([
            'call_report_id' => $report->id,
            'rank' => $rank,
            'phone' => $phone,
            'call_count' => $callCount,
            'party_id' => $partyId,
            'party_name' => $partyName,
            'has_invoice_48h' => $hasInvoice,
            'invoice_summary' => $invoiceSummary,
            'identity_note' => $identityNote,
            'transcripts' => $transcripts,
            'analysis' => $analysis,
            'outcome' => $outcome,
        ]);
    }

    /**
     * @return array{exists: bool, summary: ?string}
     */
    private function invoiceInLookback(int $partyId, CarbonInterface $from, CarbonInterface $to): array
    {
        try {
            $invoices = Invoice::query()
                ->where('CustomerPartyRef', $partyId)
                ->where(function ($q) use ($from, $to): void {
                    $q->whereBetween('CreationDate', [
                        $from->format('Y-m-d H:i:s'),
                        $to->format('Y-m-d H:i:s'),
                    ])->orWhere(function ($q2) use ($from, $to): void {
                        $q2->whereDate('Date', '>=', $from->toDateString())
                            ->whereDate('Date', '<=', $to->toDateString());
                    });
                })
                ->orderByDesc('CreationDate')
                ->limit(5)
                ->get(['InvoiceId', 'Number', 'NetPrice', 'CreationDate', 'Date']);
        } catch (Throwable $e) {
            report($e);

            return ['exists' => false, 'summary' => null];
        }

        if ($invoices->isEmpty()) {
            return ['exists' => false, 'summary' => null];
        }

        $parts = $invoices->map(function (Invoice $invoice): string {
            $number = $invoice->Number ?? $invoice->InvoiceId;
            $price = $invoice->NetPrice !== null ? number_format((float) $invoice->NetPrice) : '—';

            return "#{$number} ({$price})";
        })->all();

        return [
            'exists' => true,
            'summary' => implode('، ', $parts),
        ];
    }

    /**
     * @return list<array{uniqueid: string, calldate: string, billsec: int, recordingfile: ?string, text: string}>
     */
    private function transcribePhoneCalls(string $phone, CarbonInterface $day): array
    {
        $minBillsec = (int) config('crm.call_report.min_billsec', 15);
        $maxRecordings = max(1, (int) config('crm.call_report.max_recordings_per_phone', 3));
        $variants = IranPhoneNumberNormalizer::lookupKeyVariants($phone);

        $start = $day->copy()->startOfDay()->format('Y-m-d H:i:s');
        $end = $day->copy()->endOfDay()->format('Y-m-d H:i:s');

        $query = Cdr::query()
            ->validCalldate()
            ->onlyExternalCalls()
            ->where('calldate', '>=', $start)
            ->where('calldate', '<=', $end)
            ->whereRaw('UPPER(TRIM(disposition)) = ?', ['ANSWERED'])
            ->where('billsec', '>=', $minBillsec)
            ->where(function ($q) use ($variants): void {
                foreach ($variants as $variant) {
                    $q->orWhere('src', $variant)
                        ->orWhere('dst', $variant)
                        ->orWhere('cnum', $variant)
                        ->orWhere('src', 'like', '%'.$variant)
                        ->orWhere('dst', 'like', '%'.$variant)
                        ->orWhere('cnum', 'like', '%'.$variant);
                }
            })
            ->orderByDesc('billsec')
            ->limit($maxRecordings);

        // recordingfile may be absent on older schemas; select * safely via get then filter
        $rows = $query->get();

        $results = [];
        $phoneKey = $this->canonicalPhoneKey($phone);

        foreach ($rows as $row) {
            $external = $this->resolveExternalNumber($row);
            if ($external !== '' && $this->canonicalPhoneKey($external) !== $phoneKey) {
                continue;
            }

            $recordingFile = trim((string) ($row->recordingfile ?? ''));
            if ($recordingFile === '') {
                continue;
            }

            $callDate = $row->calldate instanceof CarbonInterface
                ? Carbon::instance($row->calldate)
                : Carbon::parse((string) $row->calldate);

            $localPath = $this->recordings->fetchToLocal(
                $recordingFile,
                $callDate,
                (string) $row->uniqueid
            );

            if ($localPath === null) {
                continue;
            }

            try {
                $transcript = Transcription::fromPath($localPath)
                    ->language('fa')
                    ->timeout(180)
                    ->generate('gap', (string) config('ai.providers.gap.models.transcription.default', 'whisper-1'));

                $text = trim((string) $transcript);
                if ($text === '') {
                    continue;
                }

                $results[] = [
                    'uniqueid' => (string) $row->uniqueid,
                    'calldate' => $callDate->format('Y-m-d H:i:s'),
                    'billsec' => (int) $row->billsec,
                    'recordingfile' => $recordingFile,
                    'text' => $text,
                ];
            } catch (Throwable $e) {
                report($e);
                Log::warning('Call transcription failed.', [
                    'uniqueid' => $row->uniqueid,
                    'error' => $e->getMessage(),
                ]);
            } finally {
                $this->recordings->deleteLocal($localPath);
            }
        }

        return $results;
    }

    /**
     * @param  list<array{uniqueid: string, calldate: string, billsec: int, recordingfile: ?string, text: string}>  $transcripts
     * @return array{wanted_summary: string, key_points: list<string>, sale_outcome_hint: string, follow_up_suggestion: string, sold_message: ?string}
     */
    private function analyzeTranscripts(string $phone, ?string $partyName, array $transcripts, bool $hasInvoice): array
    {
        $joined = collect($transcripts)
            ->map(fn (array $t, int $i): string => '--- تماس '.($i + 1).' ('.$t['calldate'].', '.$t['billsec']."s) ---\n".$t['text'])
            ->implode("\n\n");

        $context = $hasInvoice
            ? 'این شماره در ۴۸ ساعت اخیر فاکتور سپیدار داشته است. بگو آیا مکالمه با فروش هم‌خوان است.'
            : 'این شماره در ۴۸ ساعت اخیر فاکتور نداشته. فقط بگو مشتری چه می‌خواست.';

        $prompt = __('app.call_report_ai_prompt', [
            'phone' => $phone,
            'name' => $partyName ?: __('app.unknown_call_party'),
            'context' => $context,
        ])."\n\n".$joined;

        try {
            $response = (new CallConversationAnalyst)->prompt(
                $prompt,
                provider: 'gap',
                model: config('ai.default_model'),
                timeout: 120,
            );

            $data = [
                'wanted_summary' => (string) ($response['wanted_summary'] ?? ''),
                'key_points' => array_values(array_map('strval', $response['key_points'] ?? [])),
                'sale_outcome_hint' => (string) ($response['sale_outcome_hint'] ?? ''),
                'follow_up_suggestion' => (string) ($response['follow_up_suggestion'] ?? ''),
                'sold_message' => null,
            ];
        } catch (Throwable $e) {
            report($e);
            $data = [
                'wanted_summary' => __('app.call_report_ai_failed_fallback'),
                'key_points' => [],
                'sale_outcome_hint' => '',
                'follow_up_suggestion' => '',
                'sold_message' => null,
            ];
        }

        if ($hasInvoice) {
            $data['sold_message'] = __('app.call_report_sold_message');
        }

        return $data;
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

    private function canonicalPhoneKey(string $raw): string
    {
        $norm = IranPhoneNumberNormalizer::normalize($raw);

        return $norm ?? (preg_replace('/\D+/', '', $raw) ?? '');
    }
}
