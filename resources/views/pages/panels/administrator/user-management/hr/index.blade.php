<?php

use App\Models\Calender\Day;
use App\Models\Hr\DayRecord;
use App\Models\Hr\Record;
use App\Models\User;
use App\Services\Hr\DayRecordBaleNotifier;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

new #[Layout('layouts.panels.administrator')] class extends Component
{
    public ?int $userId = null;
    public ?string $dateStart = null;
    public ?string $dateEnd = null;
    public string $userSearch = '';

    protected function queryString(): array
    {
        $today = Jalalian::now()->format('Y/m/d');

        return [
            'userId' => ['except' => null],
            'dateStart' => ['except' => $today],
            'dateEnd' => ['except' => $today],
        ];
    }

    public function mount(): void
    {
        $this->authorize('administrator_user_management_hr');

        if (! request()->hasAny(['dateStart', 'dateEnd'])) {
            $today = Jalalian::now()->format('Y/m/d');
            $this->dateStart = $today;
            $this->dateEnd = $today;
        }
    }

    #[Computed]
    public function users()
    {
        $users = User::query()
            ->when($this->userSearch !== '', function ($q) {
                $like = "%{$this->userSearch}%";
                $q->where(fn ($inner) => $inner
                    ->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('mobile', 'like', $like));
            })
            ->orderBy('first_name')
            ->limit(20)
            ->get();

        if ($this->userId && ! $users->contains('id', $this->userId)) {
            $selected = User::find($this->userId);
            if ($selected) {
                $users->prepend($selected);
            }
        }

        return $users;
    }

    #[Computed]
    public function activePreset(): ?string
    {
        $now = Jalalian::now();
        $today = $now->format('Y/m/d');

        return match (true) {
            $this->dateStart === $today && $this->dateEnd === $today => 'today',
            $this->dateStart === $now->getFirstDayOfWeek()->format('Y/m/d')
                && $this->dateEnd === $now->getEndDayOfWeek()->format('Y/m/d') => 'week',
            $this->dateStart === $now->getFirstDayOfMonth()->format('Y/m/d')
                && $this->dateEnd === $now->getEndDayOfMonth()->format('Y/m/d') => 'month',
            default => null,
        };
    }

    #[Computed]
    public function records()
    {
        if (! $this->userId) {
            return collect();
        }

        $query = Record::where('user_id', $this->userId)
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
        if (! $this->userId) {
            return collect();
        }

        $query = DayRecord::query()
            ->where('user_id', $this->userId)
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

    public function approveDayRecord(int $id, DayRecordBaleNotifier $notifier): void
    {
        $this->authorize('administrator_user_management_days');

        $dayRecord = DayRecord::query()
            ->where('status', DayRecord::STATUS_PENDING)
            ->findOrFail($id);

        $dayRecord->update([
            'status' => DayRecord::STATUS_APPROVED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $notifier->notifyApproved($dayRecord);

        unset($this->dayRecords, $this->dailyReport, $this->totalHours);
        Flux::toast(__('app.hr_day_record_approved'));
    }

    public function rejectDayRecord(int $id, DayRecordBaleNotifier $notifier): void
    {
        $this->authorize('administrator_user_management_days');

        $dayRecord = DayRecord::query()
            ->where('status', DayRecord::STATUS_PENDING)
            ->findOrFail($id);

        $dayRecord->update([
            'status' => DayRecord::STATUS_REJECTED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $notifier->notifyRejected($dayRecord);

        unset($this->dayRecords, $this->dailyReport, $this->totalHours);
        Flux::toast(__('app.hr_day_record_rejected'), variant: 'warning');
    }

    public function setRange(string $preset): void
    {
        $now = Jalalian::now();

        [$start, $end] = match ($preset) {
            'week' => [$now->getFirstDayOfWeek(), $now->getEndDayOfWeek()],
            'month' => [$now->getFirstDayOfMonth(), $now->getEndDayOfMonth()],
            default => [$now, $now],
        };

        $this->dateStart = $start->format('Y/m/d');
        $this->dateEnd = $end->format('Y/m/d');
        unset($this->records, $this->dayRecords, $this->dailyReport, $this->totalHours, $this->activePreset);
    }

    public function clearFilters(): void
    {
        $today = Jalalian::now()->format('Y/m/d');

        $this->userId = null;
        $this->userSearch = '';
        $this->dateStart = $today;
        $this->dateEnd = $today;

        unset($this->users, $this->records, $this->dayRecords, $this->dailyReport, $this->totalHours, $this->activePreset);
        Flux::toast(__('app.filters_cleared'));
    }

    public function updatedUserId(): void
    {
        unset($this->records, $this->dayRecords, $this->dailyReport, $this->totalHours);
    }
};

?>

<x-slot name="title">
    {{ __('app.hr_report') }}
</x-slot>
<div>
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('app.hr_report') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('app.hr_report_description') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <flux:card class="panel-filter-card mb-6">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
            <flux:select
                variant="combobox"
                :filter="false"
                wire:model.live="userId"
                :label="__('app.select_user')"
                :placeholder="__('app.select_user')"
            >
                <x-slot name="input">
                    <flux:select.input wire:model.live.debounce.400ms="userSearch" placeholder="{{ __('app.search_user_placeholder') }}" />
                </x-slot>

                @foreach($this->users as $user)
                    <flux:select.option value="{{ $user->id }}" wire:key="hr-user-{{ $user->id }}">
                        {{ $user->name }} ({{ $user->mobile }})
                    </flux:select.option>
                @endforeach
            </flux:select>

            <div wire:key="hr-date-range-{{ $dateStart ?? 'all' }}-{{ $dateEnd ?? 'all' }}">
                <x-jalali-date-range
                    :label="__('app.date_range')"
                    :start="$dateStart"
                    :end="$dateEnd"
                    wire:model.start="dateStart"
                    wire:model.end="dateEnd"
                />
            </div>

            <div class="flex items-end">
                <flux:button variant="primary" color="zinc" class="w-full" wire:click="clearFilters">
                    {{ __('app.clear_all_filters') }}
                </flux:button>
            </div>
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-2">
            <flux:button
                size="xs"
                variant="{{ $this->activePreset === 'today' ? 'primary' : 'subtle' }}"
                color="teal"
                wire:click="setRange('today')"
            >{{ __('app.today') }}</flux:button>
            <flux:button
                size="xs"
                variant="{{ $this->activePreset === 'week' ? 'primary' : 'subtle' }}"
                color="teal"
                wire:click="setRange('week')"
            >{{ __('app.this_week') }}</flux:button>
            <flux:button
                size="xs"
                variant="{{ $this->activePreset === 'month' ? 'primary' : 'subtle' }}"
                color="teal"
                wire:click="setRange('month')"
            >{{ __('app.this_month') }}</flux:button>
        </div>
    </flux:card>

    @if($this->userId)
        @if($this->dailyReport->isNotEmpty())
            <flux:card class="mb-4">
                <div class="flex justify-between items-center">
                    <span class="font-bold">{{ __('app.total_hours') }}:</span>
                    <flux:badge color="blue" size="lg">{{ $this->totalHours }}</flux:badge>
                </div>
            </flux:card>

            <flux:card>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column class="w-40 whitespace-nowrap">{{ __('app.date') }}</flux:table.column>
                        <flux:table.column class="w-56">{{ __('app.type') }}</flux:table.column>
                        <flux:table.column class="w-40">{{ __('app.attendance_category') }}</flux:table.column>
                        <flux:table.column class="w-28 whitespace-nowrap">{{ __('app.time') }}</flux:table.column>
                        <flux:table.column>{{ __('app.device_name') }}</flux:table.column>
                        <flux:table.column class="w-32">{{ __('app.status') }}</flux:table.column>
                        <flux:table.column class="w-24 text-end whitespace-nowrap">{{ __('app.total_hours') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($this->dailyReport as $day)
                            <flux:table.row wire:key="day-{{ $day->date }}" class="bg-zinc-50 dark:bg-zinc-800/40">
                                <flux:table.cell colspan="6" class="font-medium">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span>{{ $day->jalali }}</span>
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
                                                @can('administrator_user_management_days')
                                                    <flux:tooltip content="{{ __('app.approve') }}">
                                                        <flux:button size="xs" variant="primary" color="green" icon="check" icon:variant="outline" wire:click="approveDayRecord({{ $day->day_record->id }})" />
                                                    </flux:tooltip>
                                                    <flux:tooltip content="{{ __('app.reject') }}">
                                                        <flux:button size="xs" variant="primary" color="red" icon="x" icon:variant="outline" wire:click="rejectDayRecord({{ $day->day_record->id }})" wire:confirm="{{ __('common.are_you_sure') }}" />
                                                    </flux:tooltip>
                                                @endcan
                                            @elseif($day->day_record->status === 'approved')
                                                <flux:badge color="green" size="sm">{{ __('app.approved') }}</flux:badge>
                                            @endif
                                        @endif
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell class="text-end whitespace-nowrap">
                                    <flux:badge color="sky" size="sm">{{ $day->total_hours }}</flux:badge>
                                </flux:table.cell>
                            </flux:table.row>

                            @if($day->day_record && $day->records->isEmpty())
                                <flux:table.row wire:key="day-rec-only-{{ $day->day_record->id }}">
                                    <flux:table.cell class="w-40"></flux:table.cell>
                                    <flux:table.cell>
                                        <flux:badge color="zinc" size="sm">{{ __('app.hr_day_record_no_punch') }}</flux:badge>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        @if($day->day_record->type === 'leave')
                                            <flux:badge color="amber" size="sm">{{ __('app.hr_day_record_type_leave') }}</flux:badge>
                                        @else
                                            <flux:badge color="sky" size="sm">{{ __('app.hr_day_record_type_mission') }}</flux:badge>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell class="whitespace-nowrap">—</flux:table.cell>
                                    <flux:table.cell>—</flux:table.cell>
                                    <flux:table.cell>
                                        @if($day->day_record->status === 'approved')
                                            <flux:badge color="green" size="sm">{{ __('app.approved') }}</flux:badge>
                                        @else
                                            <flux:badge color="orange" size="sm">{{ __('app.pending_approval') }}</flux:badge>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell></flux:table.cell>
                                </flux:table.row>
                            @endif

                            @foreach($day->records as $record)
                                <flux:table.row wire:key="rec-{{ $record->id }}">
                                    <flux:table.cell class="w-40"></flux:table.cell>
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
                                    <flux:table.cell class="whitespace-nowrap">
                                        {{ \Morilog\Jalali\Jalalian::fromDateTime($record->recorded_at)->format('H:i:s') }}
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        {{ $record->device?->name ? \Illuminate\Support\Str::limit($record->device->name, 30) : ($record->is_manual ? __('app.manual_record') : __('app.unknown')) }}
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        @if($record->is_device_approved)
                                            <flux:badge color="green" size="sm">{{ __('app.approved') }}</flux:badge>
                                        @else
                                            <flux:badge color="orange" size="sm">{{ __('app.pending_approval') }}</flux:badge>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell></flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </flux:card>
        @else
            <flux:card>
                <p class="text-sm text-zinc-500 py-8 text-center">{{ __('app.no_records_today') }}</p>
            </flux:card>
        @endif
    @else
        <flux:card>
            <p class="text-sm text-zinc-500 py-8 text-center">{{ __('app.no_user_selected') }}</p>
        </flux:card>
    @endif
</div>
