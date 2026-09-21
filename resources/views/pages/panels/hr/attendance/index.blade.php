<?php

use App\Livewire\Forms\Hr\ManualAttendanceForm;
use App\Models\Calender\Day;
use App\Models\Hr\Record;
use App\Models\UserDevice;
use App\Support\HrAccess;
use Carbon\Carbon;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

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

        Record::create([
            'user_id' => auth()->id(),
            'user_device_id' => null,
            'type' => $this->form->type,
            'category' => $this->form->category,
            'is_manual' => true,
            'recorded_at' => $recordedAt,
            'is_device_approved' => true,
        ]);

        $this->form->resetForm();
        Flux::modal('panels.hr.attendance.manual.modal')->close();
        Flux::toast(__('app.manual_attendance_success'));
        unset($this->todayRecords);
        unset($this->isOddRecords);
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

        Record::create([
            'user_id' => auth()->id(),
            'user_device_id' => $device->id,
            'type' => $type,
            'category' => $category,
            'is_manual' => false,
            'recorded_at' => $recordedAt,
            'is_device_approved' => (bool) ($device->is_approved ?? false),
        ]);

        $this->message = $type === 'clock_in' ? __('app.clock_in_success') : __('app.clock_out_success');
        $this->statusType = 'success';

        if (! $device->is_approved) {
            $this->message .= ' '.__('app.device_not_approved_warning');
        }

        Flux::toast($this->message);
        unset($this->todayRecords);
        unset($this->isOddRecords);
    }

    /**
     * @return string|null Translation key when invalid, otherwise null.
     */
    private function attendancePunchError(int $userId, Carbon $recordedAt, string $type): ?string
    {
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

            if (
                $type === 'clock_out'
                && $previous->type === 'clock_in'
                && $this->isSameMinute($previous->recorded_at, $recordedAt)
            ) {
                return 'app.attendance_same_minute_in_out_invalid';
            }
        }

        if ($index < $sorted->count() - 1) {
            $next = $sorted[$index + 1];

            if ($type === 'clock_in' && $next->type === 'clock_in') {
                return 'app.attendance_pair_sequence_invalid';
            }

            if (
                $type === 'clock_in'
                && $next->type === 'clock_out'
                && $this->isSameMinute($recordedAt, $next->recorded_at)
            ) {
                return 'app.attendance_same_minute_in_out_invalid';
            }
        }

        return null;
    }

    private function isSameMinute(mixed $a, mixed $b): bool
    {
        return Carbon::parse($a)->format('Y-m-d H:i') === Carbon::parse($b)->format('Y-m-d H:i');
    }
};

?>

<x-slot name="title">
    {{ __('app.attendance') }}
</x-slot>
<div x-data="{
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
}" @device-token-updated.window="deviceToken = $event.detail.token; localStorage.setItem(tokenKey, $event.detail.token)">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('app.attendance') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('app.attendance_description') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    {{-- Live Clock --}}
    <div class="max-w-md mx-auto mt-4 text-center">
        <span class="text-3xl font-mono font-bold text-zinc-700 dark:text-zinc-200" x-text="currentTime"></span>
        @if($this->isTodayHoliday)
            <div class="mt-2">
                <flux:badge color="red">{{ __('app.today_is_holiday') }}</flux:badge>
            </div>
        @endif
    </div>

    @if($this->isAccessAllowed)
        <div class="max-w-md mx-auto mt-6">
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
                        (<span class="font-mono font-bold" x-text="countdown"></span>
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
        </div>
    @else
        <flux:card class="max-w-md mx-auto mt-6">
            <div class="text-center text-red-600">
                {{ __('app.access_denied_ip') }}
            </div>
        </flux:card>
    @endif

    {{-- Manual / Forgot attendance --}}
    <div class="max-w-md mx-auto mt-4">
        <flux:modal.trigger name="panels.hr.attendance.manual.modal">
            <flux:button variant="primary" color="orange" class="w-full" icon="clipboard-check">
                {{ __('app.forgot_attendance') }}
            </flux:button>
        </flux:modal.trigger>
    </div>

    {{-- Odd records warning --}}
    @if($this->todayRecords->count() > 0 && $this->isOddRecords)
        <div class="max-w-md mx-auto mt-4">
            <flux:badge color="orange" class="w-full justify-center py-2">{{ __('app.odd_records_warning') }}</flux:badge>
        </div>
    @endif

    {{-- Today's Records --}}
    <div class="max-w-md mx-auto mt-6">
        <flux:heading size="lg" class="mb-4">{{ __('app.today_records') }}</flux:heading>
        @if($this->todayRecords->count() > 0)
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('app.type') }}</flux:table.column>
                    <flux:table.column>{{ __('app.attendance_category') }}</flux:table.column>
                    <flux:table.column>{{ __('app.time') }}</flux:table.column>
                    <flux:table.column>{{ __('app.status') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($this->todayRecords as $record)
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
                                @if($record->category === 'leave')
                                    <flux:badge color="amber" size="sm">{{ __('app.attendance_category_leave') }}</flux:badge>
                                @elseif($record->category === 'mission')
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
        @else
            <p class="text-sm text-zinc-500">{{ __('app.no_records_today') }}</p>
        @endif
    </div>

    <flux:modal name="panels.hr.attendance.manual.modal" flyout position="right" class="md:w-96">
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
