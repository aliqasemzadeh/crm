<?php

use App\Models\Calender\Day;
use App\Models\Hr\DayRecord;
use App\Models\Hr\Record;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

new #[Layout('layouts.panels.hr')] class extends Component
{
    #[Computed]
    public function todayJalali(): string
    {
        return Jalalian::now()->format('l Y/m/d');
    }

    #[Computed]
    public function overview(): array
    {
        $userId = (int) auth()->id();

        $todayRecords = Record::query()
            ->where('user_id', $userId)
            ->whereDate('recorded_at', today())
            ->orderBy('recorded_at')
            ->get();

        $now = Jalalian::now();
        $monthStart = $now->getFirstDayOfMonth()->toCarbon()->startOfDay();
        $monthEnd = $now->getEndDayOfMonth()->toCarbon()->endOfDay();

        $monthMinutes = Cache::remember(
            "hr_dashboard_month_minutes_{$userId}_{$now->format('Y-m')}",
            now()->addMinutes(5),
            function () use ($userId, $monthStart, $monthEnd) {
                $monthRecords = Record::query()
                    ->where('user_id', $userId)
                    ->whereBetween('recorded_at', [$monthStart, $monthEnd])
                    ->orderBy('recorded_at')
                    ->get();

                return $this->workMinutesFromRecords($monthRecords);
            }
        );

        $pendingDayRequests = DayRecord::query()
            ->where('user_id', $userId)
            ->where('status', DayRecord::STATUS_PENDING)
            ->count();

        $todayMinutes = $this->workMinutesFromRecords($todayRecords);
        $isHoliday = Day::isNonWorkingDay(now());
        $isOdd = $todayRecords->isNotEmpty() && $todayRecords->count() % 2 !== 0;
        $lastRecord = $todayRecords->last();

        [$statusKey, $statusColor] = $this->resolveTodayStatus($todayRecords, $isHoliday, $isOdd, $lastRecord);

        return [
            'today_status_key' => $statusKey,
            'today_status_color' => $statusColor,
            'today_hours' => $this->formatMinutes($todayMinutes),
            'month_hours' => $this->formatMinutes($monthMinutes),
            'pending_day_requests' => $pendingDayRequests,
            'is_holiday' => $isHoliday,
            'is_odd' => $isOdd,
            'last_type' => $lastRecord?->type,
            'records_count' => $todayRecords->count(),
        ];
    }

    private function workMinutesFromRecords(Collection $records): int
    {
        $totalMinutes = 0;
        $grouped = $records->groupBy(fn (Record $record) => $record->recorded_at->toDateString());

        foreach ($grouped as $dayRecords) {
            $sorted = $dayRecords->sortBy('recorded_at')->values();

            for ($i = 0; $i < $sorted->count() - 1; $i += 2) {
                if (
                    $sorted[$i]->type === 'clock_in'
                    && $sorted[$i + 1]->type === 'clock_out'
                    && ($sorted[$i]->category ?? Record::CATEGORY_WORK) === Record::CATEGORY_WORK
                    && ($sorted[$i + 1]->category ?? Record::CATEGORY_WORK) === Record::CATEGORY_WORK
                ) {
                    $totalMinutes += $sorted[$i]->recorded_at->diffInMinutes($sorted[$i + 1]->recorded_at);
                }
            }
        }

        return $totalMinutes;
    }

    private function formatMinutes(int $totalMinutes): string
    {
        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%d:%02d', $hours, $minutes);
    }

    private function resolveTodayStatus(Collection $todayRecords, bool $isHoliday, bool $isOdd, ?Record $lastRecord): array
    {
        if ($isHoliday && $todayRecords->isEmpty()) {
            return ['hr_status_holiday', 'amber'];
        }

        if ($todayRecords->isEmpty()) {
            return ['hr_status_not_clocked_in', 'zinc'];
        }

        if ($isOdd && $lastRecord?->type === 'clock_in') {
            return ['hr_status_present', 'teal'];
        }

        if ($isOdd) {
            return ['hr_status_incomplete', 'rose'];
        }

        if ($lastRecord?->type === 'clock_out') {
            return ['hr_status_clocked_out', 'sky'];
        }

        return ['hr_status_incomplete', 'rose'];
    }
};
?>

<x-slot name="title">
    {{ __('app.hr_dashboard_title') }}
</x-slot>

<div>
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('app.hr_dashboard_title') }}</flux:heading>
        <flux:subheading size="lg" class="mb-2">{{ __('app.hr_dashboard_subtitle') }}</flux:subheading>
        <flux:text class="mb-6 text-zinc-500 dark:text-zinc-400">{{ $this->todayJalali }}</flux:text>
        <flux:separator variant="subtle" />
    </div>

    @php($overview = $this->overview)

    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <flux:card class="p-5">
            <div class="flex items-start justify-between gap-3">
                <flux:subheading size="sm" class="text-zinc-500 dark:text-zinc-400">{{ __('app.hr_today_status') }}</flux:subheading>
                <flux:icon name="clock" variant="outline" class="size-5 text-zinc-400" />
            </div>
            <flux:heading size="lg" @class([
                'mt-3',
                'text-teal-600 dark:text-teal-400' => $overview['today_status_color'] === 'teal',
                'text-sky-600 dark:text-sky-400' => $overview['today_status_color'] === 'sky',
                'text-amber-600 dark:text-amber-400' => $overview['today_status_color'] === 'amber',
                'text-rose-600 dark:text-rose-400' => $overview['today_status_color'] === 'rose',
                'text-zinc-600 dark:text-zinc-300' => $overview['today_status_color'] === 'zinc',
            ])>
                {{ __('app.'.$overview['today_status_key']) }}
            </flux:heading>
            <flux:text class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                {{ __('app.today_records') }}: {{ number_format($overview['records_count']) }}
            </flux:text>
        </flux:card>

        <flux:card class="p-5">
            <div class="flex items-start justify-between gap-3">
                <flux:subheading size="sm" class="text-zinc-500 dark:text-zinc-400">{{ __('app.hr_hours_today') }}</flux:subheading>
                <flux:icon name="timer" variant="outline" class="size-5 text-sky-500" />
            </div>
            <flux:heading size="xl" class="mt-3 text-sky-600 dark:text-sky-400">{{ $overview['today_hours'] }}</flux:heading>
            <flux:text class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('app.work_hours') }}</flux:text>
        </flux:card>

        <flux:card class="p-5">
            <div class="flex items-start justify-between gap-3">
                <flux:subheading size="sm" class="text-zinc-500 dark:text-zinc-400">{{ __('app.hr_hours_this_month') }}</flux:subheading>
                <flux:icon name="hourglass" variant="outline" class="size-5 text-indigo-500" />
            </div>
            <flux:heading size="xl" class="mt-3 text-indigo-600 dark:text-indigo-400">{{ $overview['month_hours'] }}</flux:heading>
            <flux:text class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('app.this_month') }}</flux:text>
        </flux:card>

        <flux:card class="p-5">
            <div class="flex items-start justify-between gap-3">
                <flux:subheading size="sm" class="text-zinc-500 dark:text-zinc-400">{{ __('app.hr_pending_day_requests') }}</flux:subheading>
                <flux:icon name="calendar-days" variant="outline" class="size-5 text-amber-500" />
            </div>
            <flux:heading size="xl" class="mt-3 text-amber-600 dark:text-amber-400">{{ number_format($overview['pending_day_requests']) }}</flux:heading>
            <flux:text class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('app.status_pending') }}</flux:text>
        </flux:card>
    </div>

    @if ($overview['is_odd'])
        <flux:callout icon="triangle-alert" variant="warning" class="mb-8">
            <flux:callout.heading>{{ __('app.odd_records_warning') }}</flux:callout.heading>
            <flux:callout.text>{{ __('app.hr_dashboard_odd_hint') }}</flux:callout.text>
        </flux:callout>
    @elseif ($overview['is_holiday'])
        <flux:callout icon="calendar-days" class="mb-8">
            <flux:callout.heading>{{ __('app.today_is_holiday') }}</flux:callout.heading>
            <flux:callout.text>{{ __('app.hr_dashboard_holiday_hint') }}</flux:callout.text>
        </flux:callout>
    @endif

    <div class="mb-4">
        <flux:heading size="lg">{{ __('app.hr_quick_links') }}</flux:heading>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <flux:card class="space-y-3 p-5">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-lg bg-teal-500/10 text-teal-600 dark:text-teal-400">
                    <flux:icon name="log-in" variant="outline" class="size-5" />
                </div>
                <flux:heading size="lg">{{ __('app.attendance') }}</flux:heading>
            </div>
            <flux:text>{{ __('app.attendance_description') }}</flux:text>
            <flux:button variant="primary" color="teal" href="{{ route('panels.hr.attendance.index') }}" wire:navigate class="w-full" icon="log-in" icon:variant="outline">
                {{ __('app.attendance') }}
            </flux:button>
        </flux:card>

        <flux:card class="space-y-3 p-5">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400">
                    <flux:icon name="calendar-days" variant="outline" class="size-5" />
                </div>
                <flux:heading size="lg">{{ __('app.hr_day_records') }}</flux:heading>
            </div>
            <flux:text>{{ __('app.hr_day_records_description') }}</flux:text>
            <flux:button variant="primary" color="amber" href="{{ route('panels.hr.days.index') }}" wire:navigate class="w-full" icon="calendar-days" icon:variant="outline">
                {{ __('app.hr_day_records') }}
            </flux:button>
        </flux:card>

        <flux:card class="space-y-3 p-5">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-lg bg-sky-500/10 text-sky-600 dark:text-sky-400">
                    <flux:icon name="history" variant="outline" class="size-5" />
                </div>
                <flux:heading size="lg">{{ __('app.attendance_history') }}</flux:heading>
            </div>
            <flux:text>{{ __('app.attendance_history_description') }}</flux:text>
            <flux:button variant="primary" color="sky" href="{{ route('panels.hr.history.index') }}" wire:navigate class="w-full" icon="history" icon:variant="outline">
                {{ __('app.attendance_history') }}
            </flux:button>
        </flux:card>
    </div>
</div>
