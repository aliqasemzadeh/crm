<?php

namespace App\Console\Commands\Crm;

use App\Jobs\Crm\AnalyzeDailyCallReportJob;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class AnalyzeDailyCallReportCommand extends Command
{
    protected $signature = 'app:crm:analyze-daily-call-report
                            {date? : Gregorian date Y-m-d (defaults to yesterday)}
                            {--sync : Run analyzer synchronously instead of queue}';

    protected $description = 'Analyze top callers for a day: transcribe Issabel recordings and save CRM call report';

    public function handle(): int
    {
        $dateArg = $this->argument('date');
        $day = $dateArg
            ? Carbon::parse($dateArg)->startOfDay()
            : now('Asia/Tehran')->subDay()->startOfDay();

        $dateString = $day->toDateString();

        Log::info('Command app:crm:analyze-daily-call-report started.', ['date' => $dateString]);
        $this->info("Analyzing call report for {$dateString}...");

        try {
            if ($this->option('sync')) {
                AnalyzeDailyCallReportJob::dispatchSync($dateString);
                $this->info('Call report analysis completed (sync).');
            } else {
                AnalyzeDailyCallReportJob::dispatch($dateString);
                $this->info('Call report analysis job queued.');
            }
        } catch (Throwable $e) {
            report($e);
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        Log::info('Command app:crm:analyze-daily-call-report finished.', ['date' => $dateString]);

        return self::SUCCESS;
    }
}
