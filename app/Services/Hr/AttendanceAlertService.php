<?php

namespace App\Services\Hr;

use App\Jobs\Notification\BaleSendMessageJob;
use App\Models\Calender\Day;
use App\Models\Hr\DayRecord;
use App\Models\Hr\Record;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\LazyCollection;

class AttendanceAlertService
{
    public const PHASE_REMINDER = 'reminder';

    public const PHASE_ABSENT = 'absent';

    public const PHASES = [
        self::PHASE_REMINDER,
        self::PHASE_ABSENT,
    ];

    public function __construct(
        private AttendanceWelcomeMessageBuilder $welcomeBuilder,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) config('main.hr_attendance_alert');
    }

    public function isWorkingDay(): bool
    {
        return ! Day::isNonWorkingDay(now());
    }

    /**
     * @return int Number of users messaged.
     */
    public function sendPhase(string $phase): int
    {
        $excluded = $this->userIdsWithTodayRecord();
        $url = route('panels.hr.attendance.index');
        $key = $phase === self::PHASE_ABSENT
            ? 'app.hr_attendance_absent_bale_message'
            : 'app.hr_attendance_clock_in_reminder_bale_message';

        $sent = 0;

        $this->recipients()->each(function (User $user) use ($excluded, $key, $url, $phase, &$sent): void {
            if (in_array($user->id, $excluded, true)) {
                return;
            }

            if (! $this->markOnce("hr.attendance.alert.{$phase}.{$user->id}.".today()->toDateString())) {
                return;
            }

            BaleSendMessageJob::dispatch(
                __($key, [
                    'name' => $user->name,
                    'url' => $url,
                ]),
                'crm',
                (string) $user->bale_code,
            );

            $sent++;
        });

        return $sent;
    }

    public function sendWelcomeForRecord(Record $record): void
    {
        if (! $this->isEnabled()
            || $record->type !== 'clock_in'
            || $record->category !== Record::CATEGORY_WORK
            || ! $record->recorded_at?->isToday()) {
            return;
        }

        $user = $record->relationLoaded('user') ? $record->user : $record->user()->first();

        if (! $user || blank($user->bale_code)) {
            return;
        }

        $earlier = Record::query()
            ->where('user_id', $record->user_id)
            ->where('type', 'clock_in')
            ->whereDate('recorded_at', $record->recorded_at->toDateString())
            ->where('id', '!=', $record->id)
            ->exists();

        if ($earlier) {
            return;
        }

        if (! $this->markOnce("hr.attendance.welcome.{$user->id}.".$record->recorded_at->toDateString())) {
            return;
        }

        BaleSendMessageJob::dispatch(
            $this->welcomeBuilder->build($user, $record),
            'crm',
            (string) $user->bale_code,
        );
    }

    /**
     * @return LazyCollection<int, User>
     */
    public function recipients(): LazyCollection
    {
        return User::query()
            ->whereNotNull('bale_code')
            ->where('bale_code', '!=', '')
            ->select(['id', 'first_name', 'last_name', 'bale_code'])
            ->cursor();
    }

    /**
     * @return array<int, int>
     */
    public function userIdsWithTodayRecord(): array
    {
        $fromPunches = Record::query()
            ->whereDate('recorded_at', today())
            ->where(function ($query): void {
                $query->where('type', 'clock_in')
                    ->orWhereIn('category', [
                        Record::CATEGORY_LEAVE,
                        Record::CATEGORY_MISSION,
                    ]);
            })
            ->distinct()
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $fromDayRecords = DayRecord::query()
            ->whereDate('date', today())
            ->whereIn('status', [DayRecord::STATUS_PENDING, DayRecord::STATUS_APPROVED])
            ->distinct()
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_values(array_unique([...$fromPunches, ...$fromDayRecords]));
    }

    private function markOnce(string $cacheKey): bool
    {
        return Cache::add($cacheKey, true, now()->endOfDay());
    }
}
