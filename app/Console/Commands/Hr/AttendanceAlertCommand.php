<?php

namespace App\Console\Commands\Hr;

use App\Services\Hr\AttendanceAlertService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AttendanceAlertCommand extends Command
{
    protected $signature = 'app:hr:attendance-alert-command {phase : reminder|absent}';

    protected $description = 'Send Bale clock-in reminder (08:45) or absent notice (09:15) to users with a Bale code';

    public function handle(AttendanceAlertService $service): int
    {
        $phase = (string) $this->argument('phase');

        if (! in_array($phase, AttendanceAlertService::PHASES, true)) {
            $this->error('Invalid phase. Use: '.implode(' | ', AttendanceAlertService::PHASES));

            return self::FAILURE;
        }

        Log::info("Command app:hr:attendance-alert-command ({$phase}) started.");

        if (! $service->isEnabled()) {
            $this->info('HR attendance alert is disabled. Skipping command execution.');
            Log::info('Command app:hr:attendance-alert-command skipped (disabled).');

            return self::SUCCESS;
        }

        if (! $service->isWorkingDay()) {
            $this->info('Today is a non-working day (Friday or holiday). Skipping command execution.');
            Log::info('Command app:hr:attendance-alert-command skipped (non-working day).');

            return self::SUCCESS;
        }

        $sent = $service->sendPhase($phase);

        $this->info("Dispatched {$sent} Bale message(s) for phase {$phase}.");
        Log::info("Command app:hr:attendance-alert-command ({$phase}) finished. Sent: {$sent}.");

        return self::SUCCESS;
    }
}
