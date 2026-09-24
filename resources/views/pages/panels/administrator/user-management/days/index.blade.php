<?php

use App\Models\Hr\DayRecord;
use App\Models\User;
use App\Services\Hr\DayRecordBaleNotifier;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

new #[Layout('layouts.panels.administrator')] class extends Component
{
    use WithPagination;

    public ?int $filterUserId = null;

    public ?string $dateStart = null;

    public ?string $dateEnd = null;

    public string $filterType = '';

    public string $filterStatus = 'pending';

    public string $userSearch = '';

    protected function queryString(): array
    {
        $today = Jalalian::now()->format('Y/m/d');

        return [
            'filterUserId' => ['except' => null],
            'dateStart' => ['except' => $today],
            'dateEnd' => ['except' => $today],
            'filterType' => ['except' => ''],
            'filterStatus' => ['except' => 'pending'],
        ];
    }

    public function mount(): void
    {
        $this->authorize('administrator_user_management_days');

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

        if ($this->filterUserId && ! $users->contains('id', $this->filterUserId)) {
            $selected = User::find($this->filterUserId);
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

    private function filteredQuery()
    {
        return DayRecord::query()
            ->with(['user', 'reviewer'])
            ->when($this->filterUserId, fn ($q) => $q->where('user_id', $this->filterUserId))
            ->when($this->dateStart, function ($q) {
                $start = Jalalian::fromFormat('Y/m/d', $this->dateStart)->toCarbon()->startOfDay();
                $q->whereDate('date', '>=', $start);
            })
            ->when($this->dateEnd, function ($q) {
                $end = Jalalian::fromFormat('Y/m/d', $this->dateEnd)->toCarbon()->endOfDay();
                $q->whereDate('date', '<=', $end);
            })
            ->when($this->filterType !== '', fn ($q) => $q->where('type', $this->filterType))
            ->when($this->filterStatus !== '', fn ($q) => $q->where('status', $this->filterStatus));
    }

    #[Computed]
    public function dayRecords()
    {
        return $this->filteredQuery()
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20);
    }

    #[Computed]
    public function pendingCount(): int
    {
        return $this->filteredQuery()
            ->where('status', DayRecord::STATUS_PENDING)
            ->count();
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
        $this->resetPage();
        unset($this->dayRecords, $this->pendingCount, $this->activePreset);
    }

    public function approve(int $id, DayRecordBaleNotifier $notifier): void
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

        unset($this->dayRecords, $this->pendingCount);
        Flux::toast(__('app.hr_day_record_approved'));
    }

    public function reject(int $id, DayRecordBaleNotifier $notifier): void
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

        unset($this->dayRecords, $this->pendingCount);
        Flux::toast(__('app.hr_day_record_rejected'), variant: 'warning');
    }

    public function clearFilters(): void
    {
        $today = Jalalian::now()->format('Y/m/d');

        $this->filterUserId = null;
        $this->userSearch = '';
        $this->dateStart = $today;
        $this->dateEnd = $today;
        $this->filterType = '';
        $this->filterStatus = 'pending';

        $this->resetPage();
        unset($this->dayRecords, $this->pendingCount, $this->users, $this->activePreset);
        Flux::toast(__('app.filters_cleared'));
    }

    public function updatingFilterUserId(): void
    {
        $this->resetPage();
    }

    public function updatingDateStart(): void
    {
        $this->resetPage();
    }

    public function updatingDateEnd(): void
    {
        $this->resetPage();
    }

    public function updatingFilterType(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }
};

?>

<x-slot name="title">
    {{ __('app.hr_day_records_manage') }}
</x-slot>
<div>
    <div class="relative mb-6 w-full">
        <div>
            <flux:heading size="xl" level="1">{{ __('app.hr_day_records_manage') }}</flux:heading>
            <flux:subheading size="lg" class="mb-6">{{ __('app.hr_day_records_manage_description') }}</flux:subheading>
        </div>
        <flux:separator variant="subtle" />
    </div>

    <flux:card class="panel-filter-card mb-6">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
            <flux:select
                variant="combobox"
                :filter="false"
                wire:model.live="filterUserId"
                :label="__('app.filter_by_user')"
                :placeholder="__('app.all')"
            >
                <x-slot name="input">
                    <flux:select.input wire:model.live.debounce.400ms="userSearch" placeholder="{{ __('app.search_user_placeholder') }}" />
                </x-slot>

                <flux:select.option value="">{{ __('app.all') }}</flux:select.option>
                @foreach($this->users as $user)
                    <flux:select.option value="{{ $user->id }}" wire:key="day-user-{{ $user->id }}">
                        {{ $user->name }} ({{ $user->mobile }})
                    </flux:select.option>
                @endforeach
            </flux:select>

            <div wire:key="days-date-range-{{ $dateStart ?? 'all' }}-{{ $dateEnd ?? 'all' }}">
                <x-jalali-date-range
                    :label="__('app.date_range')"
                    :start="$dateStart"
                    :end="$dateEnd"
                    wire:model.start="dateStart"
                    wire:model.end="dateEnd"
                />
            </div>

            <flux:select wire:model.live="filterStatus" :label="__('app.filter_status')" searchable>
                <flux:select.option value="">{{ __('app.all') }}</flux:select.option>
                <flux:select.option value="pending">{{ __('app.pending_approval') }}</flux:select.option>
                <flux:select.option value="approved">{{ __('app.approved') }}</flux:select.option>
                <flux:select.option value="rejected">{{ __('app.rejected') }}</flux:select.option>
            </flux:select>

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

        <div class="mt-3">
            <flux:select wire:model.live="filterType" :label="__('app.type')" searchable class="max-w-xs">
                <flux:select.option value="">{{ __('app.all') }}</flux:select.option>
                <flux:select.option value="leave">{{ __('app.hr_day_record_type_leave') }}</flux:select.option>
                <flux:select.option value="mission">{{ __('app.hr_day_record_type_mission') }}</flux:select.option>
            </flux:select>
        </div>
    </flux:card>

    <flux:table :paginate="$this->dayRecords">
        <flux:table.columns>
            <flux:table.column>{{ __('app.user') }}</flux:table.column>
            <flux:table.column class="whitespace-nowrap">{{ __('app.date') }}</flux:table.column>
            <flux:table.column>{{ __('app.type') }}</flux:table.column>
            <flux:table.column>{{ __('app.status') }}</flux:table.column>
            <flux:table.column>{{ __('app.description') }}</flux:table.column>
            <flux:table.column class="text-end">{{ __('app.actions') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->dayRecords as $dayRecord)
                <flux:table.row :key="$dayRecord->id">
                    <flux:table.cell>{{ $dayRecord->user?->name }}</flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">
                        {{ \Morilog\Jalali\Jalalian::fromDateTime($dayRecord->date)->format('Y/m/d') }}
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($dayRecord->type === 'leave')
                            <flux:badge color="amber" size="sm">{{ __('app.hr_day_record_type_leave') }}</flux:badge>
                        @else
                            <flux:badge color="sky" size="sm">{{ __('app.hr_day_record_type_mission') }}</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($dayRecord->status === 'approved')
                            <flux:badge color="green" size="sm">{{ __('app.approved') }}</flux:badge>
                        @elseif($dayRecord->status === 'rejected')
                            <flux:badge color="red" size="sm">{{ __('app.rejected') }}</flux:badge>
                        @else
                            <flux:badge color="orange" size="sm">{{ __('app.pending_approval') }}</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $dayRecord->user_note ? \Illuminate\Support\Str::limit($dayRecord->user_note, 50) : '—' }}
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap text-end">
                        @if($dayRecord->isPending())
                            <div class="flex gap-2 justify-end">
                                <flux:tooltip content="{{ __('app.approve') }}">
                                    <flux:button size="xs" variant="primary" color="green" icon="check" icon:variant="outline" wire:click="approve({{ $dayRecord->id }})" />
                                </flux:tooltip>
                                <flux:tooltip content="{{ __('app.reject') }}">
                                    <flux:button size="xs" variant="primary" color="red" icon="x" icon:variant="outline" wire:click="reject({{ $dayRecord->id }})" wire:confirm="{{ __('common.are_you_sure') }}" />
                                </flux:tooltip>
                            </div>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6">
                        <p class="text-sm text-zinc-500 py-4 text-center">{{ __('app.no_results_found') }}</p>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
