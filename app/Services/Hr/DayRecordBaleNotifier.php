<?php

namespace App\Services\Hr;

use App\Jobs\Notification\BaleSendMessageJob;
use App\Models\Hr\DayRecord;
use App\Models\User;
use Morilog\Jalali\Jalalian;

class DayRecordBaleNotifier
{
    public function notifySubmitted(DayRecord $dayRecord): void
    {
        $dayRecord->loadMissing('user');

        $typeLabel = $dayRecord->isLeave()
            ? __('app.hr_day_record_type_leave')
            : __('app.hr_day_record_type_mission');

        $message = __('app.hr_day_record_submitted_bale_message', [
            'name' => $dayRecord->user?->name ?? '',
            'type' => $typeLabel,
            'date' => Jalalian::fromDateTime($dayRecord->date)->format('Y/m/d'),
            'url' => route('panels.administrator.user-management.days.index'),
        ]);

        User::permission('administrator_user_management_days')
            ->whereNotNull('bale_code')
            ->where('bale_code', '!=', '')
            ->select(['id', 'bale_code'])
            ->cursor()
            ->each(function (User $user) use ($message): void {
                BaleSendMessageJob::dispatch($message, 'crm', (string) $user->bale_code);
            });
    }

    public function notifyApproved(DayRecord $dayRecord): void
    {
        $dayRecord->loadMissing('user');

        $user = $dayRecord->user;

        if (! $user || blank($user->bale_code)) {
            return;
        }

        $typeLabel = $dayRecord->isLeave()
            ? __('app.hr_day_record_type_leave')
            : __('app.hr_day_record_type_mission');

        BaleSendMessageJob::dispatch(
            __('app.hr_day_record_approved_bale_message', [
                'name' => $user->name,
                'type' => $typeLabel,
                'date' => Jalalian::fromDateTime($dayRecord->date)->format('Y/m/d'),
            ]),
            'crm',
            (string) $user->bale_code,
        );
    }

    public function notifyRejected(DayRecord $dayRecord): void
    {
        $dayRecord->loadMissing('user');

        $user = $dayRecord->user;

        if (! $user || blank($user->bale_code)) {
            return;
        }

        $typeLabel = $dayRecord->isLeave()
            ? __('app.hr_day_record_type_leave')
            : __('app.hr_day_record_type_mission');

        BaleSendMessageJob::dispatch(
            __('app.hr_day_record_rejected_bale_message', [
                'name' => $user->name,
                'type' => $typeLabel,
                'date' => Jalalian::fromDateTime($dayRecord->date)->format('Y/m/d'),
            ]),
            'crm',
            (string) $user->bale_code,
        );
    }
}
