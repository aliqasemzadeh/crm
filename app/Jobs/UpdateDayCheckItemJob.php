<?php

namespace App\Jobs;

use App\Models\Warehouse\DayCheck;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpdateDayCheckItemJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $checkId,
        public array $itemChecks,
        public ?string $userComment = null,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $check = DayCheck::find($this->checkId);

        if (!$check) {
            return;
        }

        if ($check->status !== 'check' && $check->status !== 'reject') {
            return;
        }

        $check->update([
            'item_checks' => $this->itemChecks,
            'user_comment' => $this->userComment,
        ]);
    }
}
