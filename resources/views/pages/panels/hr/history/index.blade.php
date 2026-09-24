<?php

use App\Models\Calender\Day;
use App\Models\Hr\DayRecord;
use App\Models\Hr\Record;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

new #[Layout('layouts.panels.hr')] class extends Component
{
    public ?string $dateStart = null;
    public ?string $dateEnd = null;

    #[Computed]
    public function records()
    {
        $query = Record::where('user_id', auth()->id())
            ->with('device')
            ->orderBy('recorded_at');

        if ($this->dateStart) {
            $startGregorian = Jalalian::fromFormat('Y/m/d', $this->dateStart)->toCarbon()->startOfDay();
            $query->where('recorded_at', '>=', $startGregorian);
        }
        if ($this->dateEnd) {
            $endGregorian = Jalalian::fromFormat('Y/m/d', $this->dateEnd)->toCarbon()->endOfDay();
            $query->where('recorded_at', '<=', $endGregorian);
        }

        return $query->get();
    }

    #[Computed]
    public function dayRecords()
    {
        $query = DayRecord::query()
            ->where('user_id', auth()->id())
            ->whereIn('status', [DayRecord::STATUS_PENDING, DayRecord::STATUS_APPROVED]);

        if ($this->dateStart) {
            $startGregorian = Jalalian::fromFormat('Y/m/d', $this->dateStart)->toCarbon()->startOfDay();
            $query->whereDate('date', '>=', $startGregorian);
        }
        if ($this->dateEnd) {
            $endGregorian = Jalalian::fromFormat('Y/m/d', $this->dateEnd)->toCarbon()->endOfDay();
            $query->whereDate('date', '<=', $endGregorian);
        }

        return $query->get()->keyBy(fn (DayRecord $r) => $r->date->toDateString());
    }

    #[Computed]
    public function dailyReport()
    {
        $records = $this->records;
        $dayRecords = $this->dayRecords;

        if ($records->isEmpty() && $dayRecords->isEmpty()) {
            return collect();
        }

        $dates = $records
            ->map(fn ($r) => $r->recorded_at->toDateString())
            ->merge($dayRecords->keys())
            ->unique()
            ->sort()
            ->values();

        $grouped = $records->groupBy(fn ($r) => $r->recorded_at->toDateString());

        return $dates->mapWithKeys(function ($date) use ($grouped, $dayRecords) {
            $dayPunchRecords = $grouped->get($date, collect());
            $dayRecord = $dayRecords->get($date);
            $carbon = \Carbon\Carbon::parse($date);
            $jalali = Jalalian::fromCarbon($carbon)->format('Y/m/d');
            $isHoliday = Day::isNonWorkingDay($carbon);
            $isOdd = $dayPunchRecords->isNotEmpty() && $dayPunchRecords->count() % 2 !== 0;

            $totalMinutes = 0;
            $sorted = $dayPunchRecords->sortBy('recorded_at')->values();
            for ($i = 0; $i < $sorted->count() - 1; $i += 2) {
                if (
                    $sorted[$i]->type === 'clock_in'
                    && $sorted[$i + 1]->type === 'clock_out'
                    && ($sorted[$i]->category ?? 'work') === 'work'
                    && ($sorted[$i + 1]->category ?? 'work') === 'work'
                ) {
                    $totalMinutes += $sorted[$i]->recorded_at->diffInMinutes($sorted[$i + 1]->recorded_at);
                }
            }

            $hours = floor($totalMinutes / 60);
            $minutes = $totalMinutes % 60;

            return [$date => (object) [
                'date' => $date,
                'jalali' => $jalali,
                'is_holiday' => $isHoliday,
                'is_odd' => $isOdd,
                'records' => $sorted,
                'day_record' => $dayRecord,
                'total_hours' => sprintf('%d:%02d', $hours, $minutes),
                'total_minutes' => $totalMinutes,
            ]];
        });
    }

    #[Computed]
    public function totalHours()
    {
        $totalMinutes = $this->dailyReport->sum('total_minutes');
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%d:%02d', $hours, $minutes);
    }
};

?>

<x-slot name="title">
    {{ __('app.attendance_history') }}
</x-slot>
<div>
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('app.attendance_history') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('app.attendance_history_description') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <flux:card class="mb-6">
        <x-jalali-date-range wire:model.start="dateStart" wire:model.end="dateEnd" />
    </flux:card>

    @if($this->dailyReport->isNotEmpty())
        <flux:card class="mb-4">
            <div class="flex justify-between items-center">
                <span class="font-bold">{{ __('app.total_hours') }}:</span>
                <flux:badge color="blue" size="lg">{{ $this->totalHours }}</flux:badge>
            </div>
        </flux:card>

        @foreach($this->dailyReport as $day)
            <flux:card class="mb-3">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-bold">{{ $day->jalali }}</span>
                        @if($day->is_holiday)
                            <flux:badge color="red" size="sm">{{ __('app.holiday') }}</flux:badge>
                        @endif
                        @if($day->is_odd)
                            <flux:badge color="orange" size="sm">{{ __('app.odd_records_warning') }}</flux:badge>
                        @endif
                        @if($day->day_record)
                            @if($day->day_record->type === 'leave')
                                <flux:badge color="amber" size="sm">{{ __('app.hr_day_record_type_leave') }}</flux:badge>
                            @else
                                <flux:badge color="sky" size="sm">{{ __('app.hr_day_record_type_mission') }}</flux:badge>
                            @endif
                            @if($day->day_record->status === 'pending')
                                <flux:badge color="orange" size="sm">{{ __('app.pending_approval') }}</flux:badge>
                            @else
                                <flux:badge color="green" size="sm">{{ __('app.approved') }}</flux:badge>
                            @endif
                        @endif
                    </div>
                    <flux:badge color="sky">{{ $day->total_hours }}</flux:badge>
                </div>
                @if($day->day_record && $day->records->isEmpty())
                    <p class="text-sm text-zinc-500 mb-2">{{ __('app.hr_day_record_no_punch') }}</p>
                @endif
                @if($day->records->isNotEmpty())
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>{{ __('app.type') }}</flux:table.column>
                            <flux:table.column>{{ __('app.attendance_category') }}</flux:table.column>
                            <flux:table.column>{{ __('app.time') }}</flux:table.column>
                            <flux:table.column>{{ __('app.status') }}</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach($day->records as $record)
                                <flux:table.row :key="$record->id">
                                    <flux:table.cell>
                                        <div class="flex flex-wrap items-center gap-1">
                                            @if($record->type === 'clock_in')
                                                <flux:badge color="green">{{ __('app.clock_in') }}</flux:badge>
                                            @else
                                                <flux:badge color="red">{{ __('app.clock_out') }}</flux:badge>
                                            @endif
                                            @if($record->is_manual)
                                                <flux:badge color="zinc" size="sm">{{ __('app.manual_record') }}</flux:badge>
                                            @endif
                                        </div>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        @if(($record->category ?? 'work') === 'leave')
                                            <flux:badge color="amber" size="sm">{{ __('app.attendance_category_leave') }}</flux:badge>
                                        @elseif(($record->category ?? 'work') === 'mission')
                                            <flux:badge color="sky" size="sm">{{ __('app.attendance_category_mission') }}</flux:badge>
                                        @else
                                            <flux:badge color="zinc" size="sm">{{ __('app.attendance_category_work') }}</flux:badge>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell>{{ \Morilog\Jalali\Jalalian::fromDateTime($record->recorded_at)->format('H:i:s') }}</flux:table.cell>
                                    <flux:table.cell>
                                        @if($record->is_device_approved)
                                            <flux:badge color="green" size="sm">{{ __('app.approved') }}</flux:badge>
                                        @else
                                            <flux:badge color="orange" size="sm">{{ __('app.pending_approval') }}</flux:badge>
                                        @endif
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @endif
            </flux:card>
        @endforeach
    @else
        <p class="text-sm text-zinc-500">{{ __('app.no_records_today') }}</p>
    @endif
</div>
