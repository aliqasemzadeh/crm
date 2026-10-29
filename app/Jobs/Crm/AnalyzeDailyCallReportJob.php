<?php

namespace App\Jobs\Crm;

use App\Models\Crm\CallReport;
use App\Services\Crm\DailyCallReportAnalyzer;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class AnalyzeDailyCallReportJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 1800;

    public int $uniqueFor = 3600;

    public function __construct(
        public string $reportDate,
    ) {}

    public function uniqueId(): string
    {
        return 'crm-call-report-'.$this->reportDate;
    }

    public function handle(DailyCallReportAnalyzer $analyzer): void
    {
        Log::info('AnalyzeDailyCallReportJob started.', ['date' => $this->reportDate]);

        $day = Carbon::parse($this->reportDate)->startOfDay();
        $analyzer->analyze($day);

        Log::info('AnalyzeDailyCallReportJob finished.', ['date' => $this->reportDate]);
    }

    public function failed(?Throwable $exception): void
    {
        CallReport::query()
            ->whereDate('report_date', $this->reportDate)
            ->update([
                'status' => 'failed',
                'error' => $exception?->getMessage() ?? 'Job failed',
            ]);
    }
}
