<?php

use App\Models\Calender\Day;
use App\Models\Hr\Record;
use App\Models\User;
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

    #[Computed]
    public function users()
    {
        return User::query()
            ->when($this->userSearch, fn($q) => $q->where('first_name', 'like', "%{$this->userSearch}%")->orWhere('last_name', 'like', "%{$this->userSearch}%")->orWhere('mobile', 'like', "%{$this->userSearch}%"))
            ->limit(20)
            ->get();
    }

    #[Computed]
    public function records()
    {
        if (!$this->userId) return collect();

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
    public function dailyReport()
    {
        $records = $this->records;
        if ($records->isEmpty()) return collect();

        return $records->groupBy(fn($r) => $r->recorded_at->toDateString())->map(function ($dayRecords, $date) {
            $carbon = \Carbon\Carbon::parse($date);
            $jalali = Jalalian::fromCarbon($carbon)->format('Y/m/d');
            $isHoliday = Day::isNonWorkingDay($carbon);
            $isOdd = $dayRecords->count() % 2 !== 0;

            $totalMinutes = 0;
            $sorted = $dayRecords->sortBy('recorded_at')->values();
            for ($i = 0; $i < $sorted->count() - 1; $i += 2) {
                if ($sorted[$i]->type === 'clock_in' && $sorted[$i + 1]->type === 'clock_out') {
                    $totalMinutes += $sorted[$i]->recorded_at->diffInMinutes($sorted[$i + 1]->recorded_at);
                }
            }

            $hours = floor($totalMinutes / 60);
            $minutes = $totalMinutes % 60;

            return (object)[
                'date' => $date,
                'jalali' => $jalali,
                'is_holiday' => $isHoliday,
                'is_odd' => $isOdd,
                'records' => $dayRecords->sortBy('recorded_at'),
                'total_hours' => sprintf('%d:%02d', $hours, $minutes),
                'total_minutes' => $totalMinutes,
            ];
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

    public function mount(): void
    {
        $this->authorize('administrator_user_management_hr');
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

    <flux:card class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:select searchable wire:model.live="userId" placeholder="{{ __('app.select_user') }}">
                @foreach($this->users as $user)
                    <flux:select.option value="{{ $user->id }}">{{ $user->name }} ({{ $user->mobile }})</flux:select.option>
                @endforeach
            </flux:select>

            <x-jalali-date-range wire:model.start="dateStart" wire:model.end="dateEnd" />
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

            @foreach($this->dailyReport as $day)
                <flux:card class="mb-3">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="font-bold">{{ $day->jalali }}</span>
                            @if($day->is_holiday)
                                <flux:badge color="red" size="sm">{{ __('app.holiday') }}</flux:badge>
                            @endif
                            @if($day->is_odd)
                                <flux:badge color="orange" size="sm">{{ __('app.odd_records_warning') }}</flux:badge>
                            @endif
                        </div>
                        <flux:badge color="sky">{{ $day->total_hours }}</flux:badge>
                    </div>
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>{{ __('app.type') }}</flux:table.column>
                            <flux:table.column>{{ __('app.time') }}</flux:table.column>
                            <flux:table.column>{{ __('app.device_name') }}</flux:table.column>
                            <flux:table.column>{{ __('app.status') }}</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach($day->records as $record)
                                <flux:table.row :key="$record->id">
                                    <flux:table.cell>
                                        @if($record->type === 'clock_in')
                                            <flux:badge color="green">{{ __('app.clock_in') }}</flux:badge>
                                        @else
                                            <flux:badge color="red">{{ __('app.clock_out') }}</flux:badge>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell>{{ \Morilog\Jalali\Jalalian::fromDateTime($record->recorded_at)->format('H:i:s') }}</flux:table.cell>
                                    <flux:table.cell>{{ $record->device?->name ? \Illuminate\Support\Str::limit($record->device->name, 30) : __('app.unknown') }}</flux:table.cell>
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
                </flux:card>
            @endforeach
        @else
            <p class="text-sm text-zinc-500">{{ __('app.no_records_today') }}</p>
        @endif
    @else
        <p class="text-sm text-zinc-500">{{ __('app.no_user_selected') }}</p>
    @endif
</div>
