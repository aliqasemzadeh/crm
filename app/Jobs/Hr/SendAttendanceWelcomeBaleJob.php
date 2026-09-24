<?php

namespace App\Jobs\Hr;

use App\Models\Hr\Record;
use App\Services\Hr\AttendanceAlertService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendAttendanceWelcomeBaleJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $recordId) {}

    public function handle(AttendanceAlertService $service): void
    {
        $record = Record::with('user')->find($this->recordId);

        if (! $record) {
            return;
        }

        $service->sendWelcomeForRecord($record);
    }
}
