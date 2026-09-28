<?php

use App\Jobs\Hr\SendAttendanceWelcomeBaleJob;
use App\Livewire\Forms\Hr\ManualAttendanceForm;
use App\Models\Calender\Day;
use App\Models\Hr\DayRecord;
use App\Models\Hr\Record;
use App\Models\UserDevice;
use App\Support\HrAccess;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

new #[Layout('layouts.panels.hr')] class extends Component
{
    public ManualAttendanceForm $form;

    public string $message = '';

    public string $statusType = '';

    public function mount(): void
    {
        $this->form->resetForm();
    }

    #[Computed]
    public function todayJalali(): string
    {
        return Jalalian::now()->format('l Y/m/d');
    }

    #[Computed]
    public function isAccessAllowed(): bool
    {
        return HrAccess::isAllowed();
    }

    #[Computed]
    public function todayRecords()
    {
        return Record::where('user_id', auth()->id())
            ->whereDate('recorded_at', today())
            ->with('device')
            ->latest('recorded_at')
            ->get();
    }

    #[Computed]
    public function isOddRecords(): bool
    {
        return $this->todayRecords->count() % 2 !== 0;
    }

    #[Computed]
    public function isTodayHoliday(): bool
    {
        return Day::isNonWorkingDay(now());
    }

    #[Computed]
    public function overview(): array
    {
        $userId = (int) auth()->id();
        $todayRecords = $this->todayRecords->sortBy('recorded_at')->values();

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
        $isHoliday = $this->isTodayHoliday;
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
            'records_count' => $todayRecords->count(),
        ];
    }

    public function clockIn(string $deviceToken, string $category = Record::CATEGORY_WORK): void
    {
        $this->record($deviceToken, 'clock_in', $category);
    }

    public function clockOut(string $deviceToken, string $category = Record::CATEGORY_WORK): void
    {
        $this->record($deviceToken, 'clock_out', $category);
    }

    public function saveManual(): void
    {
        $this->form->validate();

        $recordedAt = $this->form->toRecordedAt();

        if ($error = $this->attendancePunchError((int) auth()->id(), $recordedAt, $this->form->type)) {
            Flux::toast(__($error), variant: 'danger');

            return;
        }

        $record = Record::create([
            'user_id' => auth()->id(),
            'user_device_id' => null,
            'type' => $this->form->type,
            'category' => $this->form->category,
            'is_manual' => true,
            'recorded_at' => $recordedAt,
            'is_device_approved' => true,
        ]);

        SendAttendanceWelcomeBaleJob::dispatch($record->id);

        $this->form->resetForm();
        Flux::modal('panels.hr.dashboard.manual.modal')->close();
        Flux::toast(__('app.manual_attendance_success'));
        $this->forgetAttendanceComputed();
    }

    private function record(string $deviceToken, string $type, string $category): void
    {
        if (! $this->isAccessAllowed) {
            $this->message = __('app.access_denied_ip');
            $this->statusType = 'error';
            Flux::toast($this->message);

            return;
        }

        if (! in_array($category, Record::CATEGORIES, true)) {
            $category = Record::CATEGORY_WORK;
        }

        $device = UserDevice::where('token', $deviceToken)->first();

        if ($device === null) {
            $device = UserDevice::create([
                'token' => $deviceToken,
                'user_id' => auth()->id(),
                'name' => request()->userAgent(),
                'ip' => request()->ip(),
                'is_approved' => false,
            ]);
        } elseif ($device->user_id !== auth()->id()) {
            $deviceToken = 'dev_'.bin2hex(random_bytes(12));
            $device = UserDevice::create([
                'token' => $deviceToken,
                'user_id' => auth()->id(),
                'name' => request()->userAgent(),
                'ip' => request()->ip(),
                'is_approved' => false,
            ]);
            $this->dispatch('device-token-updated', token: $deviceToken);
        }

        if ($device->ip !== request()->ip()) {
            $device->update(['ip' => request()->ip()]);
        }

        $recordedAt = now();

        if ($error = $this->attendancePunchError((int) auth()->id(), $recordedAt, $type)) {
            Flux::toast(__($error), variant: 'danger');

            return;
        }

        $record = Record::create([
            'user_id' => auth()->id(),
            'user_device_id' => $device->id,
            'type' => $type,
            'category' => $category,
            'is_manual' => false,
            'recorded_at' => $recordedAt,
            'is_device_approved' => (bool) ($device->is_approved ?? false),
        ]);

        SendAttendanceWelcomeBaleJob::dispatch($record->id);

        $this->message = $type === 'clock_in' ? __('app.clock_in_success') : __('app.clock_out_success');
        $this->statusType = 'success';

        if (! $device->is_approved) {
            $this->message .= ' '.__('app.device_not_approved_warning');
        }

        Flux::toast($this->message);
        $this->forgetAttendanceComputed();
    }

    private function forgetAttendanceComputed(): void
    {
        unset($this->todayRecords);
        unset($this->isOddRecords);
        unset($this->overview);

        $userId = (int) auth()->id();
        $monthKey = Jalalian::now()->format('Y-m');
        Cache::forget("hr_dashboard_month_minutes_{$userId}_{$monthKey}");
    }

    /**
     * @return string|null Translation key when invalid, otherwise null.
     */
    private function attendancePunchError(int $userId, Carbon $recordedAt, string $type): ?string
    {
        $minuteStart = $recordedAt->copy()->startOfMinute();
        $minuteEnd = $minuteStart->copy()->addMinute();

        $hasRecordInSameMinute = Record::query()
            ->where('user_id', $userId)
            ->where('recorded_at', '>=', $minuteStart)
            ->where('recorded_at', '<', $minuteEnd)
            ->exists();

        if ($hasRecordInSameMinute) {
            return 'app.attendance_one_record_per_minute';
        }

        $existing = Record::query()
            ->where('user_id', $userId)
            ->whereDate('recorded_at', $recordedAt->toDateString())
            ->orderBy('recorded_at')
            ->orderBy('id')
            ->get(['id', 'type', 'recorded_at']);

        $proposed = (object) [
            'id' => PHP_INT_MAX,
            'type' => $type,
            'recorded_at' => $recordedAt,
        ];

        $sorted = $existing
            ->push($proposed)
            ->sortBy([
                fn ($r) => Carbon::parse($r->recorded_at)->timestamp,
                fn ($r) => $r->id,
            ])
            ->values();

        $index = $sorted->search(fn ($r) => $r->id === PHP_INT_MAX);

        if ($index === false) {
            return null;
        }

        if ($index > 0) {
            $previous = $sorted[$index - 1];

            if ($type === 'clock_in' && $previous->type === 'clock_in') {
                return 'app.attendance_pair_sequence_invalid';
            }
        }

        if ($index < $sorted->count() - 1) {
            $next = $sorted[$index + 1];

            if ($type === 'clock_in' && $next->type === 'clock_in') {
                return 'app.attendance_pair_sequence_invalid';
            }
        }

        return null;
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

<div
    x-data="{
        deviceToken: '',
        currentTime: '',
        tokenKey: 'attendance_device_token_{{ auth()->id() }}',
        pendingType: null,
        countdown: 0,
        timer: null,
        submitting: false,
        init() {
            let token = localStorage.getItem(this.tokenKey);
            if (!token) {
                token = 'dev_' + Math.random().toString(36).substring(2) + Date.now().toString(36);
                localStorage.setItem(this.tokenKey, token);
            }
            this.deviceToken = token;
            this.updateTime();
            setInterval(() => this.updateTime(), 1000);
        },
        updateTime() {
            this.currentTime = new Date().toLocaleTimeString('fa-IR');
        },
        startCategoryPick(type) {
            if (this.submitting) return;
            this.clearTimer();
            this.pendingType = type;
            this.countdown = 5;
            this.timer = setInterval(() => {
                this.countdown -= 1;
                if (this.countdown <= 0) {
                    this.clearTimer();
                    this.confirmCategory('work');
                }
            }, 1000);
        },
        confirmCategory(category) {
            if (!this.pendingType || this.submitting) return;
            this.submitting = true;
            const type = this.pendingType;
            this.clearTimer();
            this.pendingType = null;
            this.countdown = 0;
            const request = type === 'clock_in'
                ? $wire.clockIn(this.deviceToken, category)
                : $wire.clockOut(this.deviceToken, category);
            Promise.resolve(request).finally(() => {
                this.submitting = false;
            });
        },
        cancelCategoryPick() {
            if (this.submitting) return;
            this.clearTimer();
            this.pendingType = null;
            this.countdown = 0;
        },
        clearTimer() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        }
    }"
    @device-token-updated.window="deviceToken = $event.detail.token; localStorage.setItem(tokenKey, $event.detail.token)"
>
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

    <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div>
            <div class="mb-4 text-center">
                <span class="font-mono text-3xl font-bold text-zinc-700 dark:text-zinc-200" x-text="currentTime"></span>
                @if ($this->isTodayHoliday)
                    <div class="mt-2">
                        <flux:badge color="red">{{ __('app.today_is_holiday') }}</flux:badge>
                    </div>
                @endif
            </div>

            @if ($this->isAccessAllowed)
                <flux:card>
                    <div class="flex flex-col gap-4" x-show="!pendingType">
                        <flux:button variant="primary" color="green" class="w-full" icon="log-in" @click="startCategoryPick('clock_in')">
                            {{ __('app.clock_in') }}
                        </flux:button>
                        <flux:button variant="primary" color="red" class="w-full" icon="log-out" @click="startCategoryPick('clock_out')">
                            {{ __('app.clock_out') }}
                        </flux:button>
                    </div>

                    <div class="flex flex-col gap-3" x-show="pendingType" x-cloak>
                        <div class="text-center text-sm text-zinc-600 dark:text-zinc-300">
                            <span x-text="pendingType === 'clock_in' ? '{{ __('app.clock_in') }}' : '{{ __('app.clock_out') }}'"></span>
                            — {{ __('app.select_attendance_category') }}
                            (<span class="font-mono font-bold" x-text="countdown"></span>)
                        </div>
                        <flux:button variant="primary" color="green" class="w-full" @click="confirmCategory('work')">
                            {{ __('app.attendance_category_work') }}
                        </flux:button>
                        <flux:button variant="primary" color="amber" class="w-full" @click="confirmCategory('leave')">
                            {{ __('app.attendance_category_leave') }}
                        </flux:button>
                        <flux:button variant="primary" color="sky" class="w-full" @click="confirmCategory('mission')">
                            {{ __('app.attendance_category_mission') }}
                        </flux:button>
                        <flux:button variant="ghost" class="w-full" @click="cancelCategoryPick()">
                            {{ __('app.cancel') }}
                        </flux:button>
                    </div>
                </flux:card>
            @else
                <flux:card>
                    <div class="text-center text-red-600">
                        {{ __('app.access_denied_ip') }}
                    </div>
                </flux:card>
            @endif

            <div class="mt-4">
                <flux:modal.trigger name="panels.hr.dashboard.manual.modal">
                    <flux:button variant="primary" color="orange" class="w-full" icon="clipboard-check">
                        {{ __('app.forgot_attendance') }}
                    </flux:button>
                </flux:modal.trigger>
            </div>

            @if ($this->todayRecords->count() > 0 && $this->isOddRecords)
                <div class="mt-4">
                    <flux:badge color="orange" class="w-full justify-center py-2">{{ __('app.odd_records_warning') }}</flux:badge>
                </div>
            @endif
        </div>

        <div>
            <flux:heading size="lg" class="mb-4">{{ __('app.today_records') }}</flux:heading>
            @if ($this->todayRecords->count() > 0)
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('app.type') }}</flux:table.column>
                        <flux:table.column>{{ __('app.attendance_category') }}</flux:table.column>
                        <flux:table.column>{{ __('app.time') }}</flux:table.column>
                        <flux:table.column>{{ __('app.status') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->todayRecords as $record)
                            <flux:table.row :key="$record->id">
                                <flux:table.cell>
                                    <div class="flex flex-wrap items-center gap-1">
                                        @if ($record->type === 'clock_in')
                                            <flux:badge color="green">{{ __('app.clock_in') }}</flux:badge>
                                        @else
                                            <flux:badge color="red">{{ __('app.clock_out') }}</flux:badge>
                                        @endif
                                        @if ($record->is_manual)
                                            <flux:badge color="zinc" size="sm">{{ __('app.manual_record') }}</flux:badge>
                                        @endif
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if ($record->category === 'leave')
                                        <flux:badge color="amber" size="sm">{{ __('app.attendance_category_leave') }}</flux:badge>
                                    @elseif ($record->category === 'mission')
                                        <flux:badge color="sky" size="sm">{{ __('app.attendance_category_mission') }}</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="sm">{{ __('app.attendance_category_work') }}</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>{{ Jalalian::fromDateTime($record->recorded_at)->format('H:i:s') }}</flux:table.cell>
                                <flux:table.cell>
                                    @if ($record->is_device_approved)
                                        <flux:badge color="green" size="sm">{{ __('app.approved') }}</flux:badge>
                                    @else
                                        <flux:badge color="orange" size="sm">{{ __('app.pending_approval') }}</flux:badge>
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @else
                <p class="text-sm text-zinc-500">{{ __('app.no_records_today') }}</p>
            @endif
        </div>
    </div>

    <div class="mb-4">
        <flux:heading size="lg">{{ __('app.hr_quick_links') }}</flux:heading>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
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

    <flux:modal name="panels.hr.dashboard.manual.modal" flyout position="right" class="md:w-96">
        <form wire:submit="saveManual" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('app.forgot_attendance') }}</flux:heading>
                <flux:subheading>{{ __('app.forgot_attendance_description') }}</flux:subheading>
            </div>

            <x-date-select wire:model="form.date" :label="__('app.date')" :required="true" />
            <flux:error name="form.date" />

            <flux:input wire:model="form.time" label="{{ __('app.time') }}" mask="99:99" placeholder="08:30" />
            <flux:error name="form.time" />

            <flux:select wire:model="form.type" label="{{ __('app.type') }}" searchable>
                <flux:select.option value="clock_in">{{ __('app.clock_in') }}</flux:select.option>
                <flux:select.option value="clock_out">{{ __('app.clock_out') }}</flux:select.option>
            </flux:select>
            <flux:error name="form.type" />

            <flux:select wire:model="form.category" label="{{ __('app.attendance_category') }}" searchable>
                <flux:select.option value="work">{{ __('app.attendance_category_work') }}</flux:select.option>
                <flux:select.option value="leave">{{ __('app.attendance_category_leave') }}</flux:select.option>
                <flux:select.option value="mission">{{ __('app.attendance_category_mission') }}</flux:select.option>
            </flux:select>
            <flux:error name="form.category" />

            <flux:button type="submit" variant="primary" color="orange" class="w-full">{{ __('app.save') }}</flux:button>
        </form>
    </flux:modal>
</div>
