<?php

use App\Models\Hr\Record;
use App\Models\User;
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
    public string $filterManual = '';
    public string $filterCategory = '';
    public string $filterApproval = 'pending';
    public string $userSearch = '';

    protected function queryString(): array
    {
        $today = Jalalian::now()->format('Y/m/d');

        return [
            'filterUserId' => ['except' => null],
            'dateStart' => ['except' => $today],
            'dateEnd' => ['except' => $today],
            'filterType' => ['except' => ''],
            'filterManual' => ['except' => ''],
            'filterCategory' => ['except' => ''],
            'filterApproval' => ['except' => 'pending'],
        ];
    }

    public function mount(): void
    {
        $this->authorize('administrator_user_management_record');

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
        return Record::query()
            ->with(['user', 'device'])
            ->when($this->filterUserId, fn ($q) => $q->where('user_id', $this->filterUserId))
            ->when($this->dateStart, function ($q) {
                $start = Jalalian::fromFormat('Y/m/d', $this->dateStart)->toCarbon()->startOfDay();
                $q->where('recorded_at', '>=', $start);
            })
            ->when($this->dateEnd, function ($q) {
                $end = Jalalian::fromFormat('Y/m/d', $this->dateEnd)->toCarbon()->endOfDay();
                $q->where('recorded_at', '<=', $end);
            })
            ->when($this->filterType !== '', fn ($q) => $q->where('type', $this->filterType))
            ->when($this->filterManual !== '', fn ($q) => $q->where('is_manual', $this->filterManual === '1'))
            ->when($this->filterCategory !== '', fn ($q) => $q->where('category', $this->filterCategory))
            ->when($this->filterApproval === 'approved', fn ($q) => $q->where('is_device_approved', true))
            ->when($this->filterApproval === 'pending', fn ($q) => $q->where('is_device_approved', false));
    }

    #[Computed]
    public function records()
    {
        return $this->filteredQuery()
            ->orderByDesc('recorded_at')
            ->paginate(20);
    }

    #[Computed]
    public function pendingCount(): int
    {
        return $this->filteredQuery()
            ->where('is_device_approved', false)
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
        unset($this->records, $this->pendingCount, $this->activePreset);
    }

    public function approve(int $id): void
    {
        $this->authorize('administrator_user_management_record');

        $record = Record::findOrFail($id);
        $record->update(['is_device_approved' => true]);

        unset($this->records, $this->pendingCount);
        Flux::toast(__('app.attendance_record_approved'));
    }

    public function reject(int $id): void
    {
        $this->authorize('administrator_user_management_record');

        $record = Record::findOrFail($id);
        $record->update(['is_device_approved' => false]);

        unset($this->records, $this->pendingCount);
        Flux::toast(__('app.attendance_record_rejected'), variant: 'warning');
    }

    public function approveAll(): void
    {
        $this->authorize('administrator_user_management_record');

        $updated = $this->filteredQuery()
            ->where('is_device_approved', false)
            ->update(['is_device_approved' => true]);

        unset($this->records, $this->pendingCount);

        if ($updated === 0) {
            Flux::toast(__('app.no_results_found'), variant: 'danger');

            return;
        }

        Flux::toast(__('app.attendance_records_approve_all_success'));
    }

    public function clearFilters(): void
    {
        $today = Jalalian::now()->format('Y/m/d');

        $this->filterUserId = null;
        $this->userSearch = '';
        $this->dateStart = $today;
        $this->dateEnd = $today;
        $this->filterType = '';
        $this->filterManual = '';
        $this->filterCategory = '';
        $this->filterApproval = 'pending';

        $this->resetPage();
        unset($this->records, $this->pendingCount, $this->users, $this->activePreset);
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

    public function updatingFilterManual(): void
    {
        $this->resetPage();
    }

    public function updatingFilterCategory(): void
    {
        $this->resetPage();
    }

    public function updatingFilterApproval(): void
    {
        $this->resetPage();
    }
};

?>

<x-slot name="title">
    {{ __('app.attendance_records') }}
</x-slot>
<div>
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.attendance_records') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.attendance_records_description') }}</flux:subheading>
            </div>
            @if($this->pendingCount > 0)
                <flux:button
                    size="sm"
                    variant="primary"
                    color="green"
                    icon="check-circle"
                    wire:click="approveAll"
                    wire:confirm="{{ __('app.attendance_records_approve_all_confirm') }}"
                >
                    {{ __('app.attendance_records_approve_all') }}
                </flux:button>
            @endif
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
                    <flux:select.option value="{{ $user->id }}" wire:key="record-user-{{ $user->id }}">
                        {{ $user->name }} ({{ $user->mobile }})
                    </flux:select.option>
                @endforeach
            </flux:select>

            <div wire:key="record-date-range-{{ $dateStart ?? 'all' }}-{{ $dateEnd ?? 'all' }}">
                <x-jalali-date-range
                    :label="__('app.date_range')"
                    :start="$dateStart"
                    :end="$dateEnd"
                    wire:model.start="dateStart"
                    wire:model.end="dateEnd"
                />
            </div>

            <flux:field>
                <flux:label>{{ __('app.filter_status') }}</flux:label>
                <flux:select wire:model.live="filterApproval">
                    <flux:select.option value="">{{ __('app.all') }}</flux:select.option>
                    <flux:select.option value="pending">{{ __('app.pending_approval') }}</flux:select.option>
                    <flux:select.option value="approved">{{ __('app.approved') }}</flux:select.option>
                </flux:select>
            </flux:field>

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

        <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-3">
            <flux:field>
                <flux:label>{{ __('app.filter_clock_type') }}</flux:label>
                <flux:select wire:model.live="filterType">
                    <flux:select.option value="">{{ __('app.all') }}</flux:select.option>
                    <flux:select.option value="clock_in">{{ __('app.clock_in') }}</flux:select.option>
                    <flux:select.option value="clock_out">{{ __('app.clock_out') }}</flux:select.option>
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>{{ __('app.filter_record_source') }}</flux:label>
                <flux:select wire:model.live="filterManual">
                    <flux:select.option value="">{{ __('app.all') }}</flux:select.option>
                    <flux:select.option value="1">{{ __('app.manual_record') }}</flux:select.option>
                    <flux:select.option value="0">{{ __('app.normal_record') }}</flux:select.option>
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>{{ __('app.attendance_category') }}</flux:label>
                <flux:select wire:model.live="filterCategory">
                    <flux:select.option value="">{{ __('app.all') }}</flux:select.option>
                    <flux:select.option value="work">{{ __('app.attendance_category_work') }}</flux:select.option>
                    <flux:select.option value="leave">{{ __('app.attendance_category_leave') }}</flux:select.option>
                    <flux:select.option value="mission">{{ __('app.attendance_category_mission') }}</flux:select.option>
                </flux:select>
            </flux:field>
        </div>
    </flux:card>

    <flux:table :paginate="$this->records">
        <flux:table.columns>
            <flux:table.column>{{ __('app.user') }}</flux:table.column>
            <flux:table.column class="whitespace-nowrap">{{ __('app.date') }}</flux:table.column>
            <flux:table.column class="whitespace-nowrap">{{ __('app.time') }}</flux:table.column>
            <flux:table.column>{{ __('app.type') }}</flux:table.column>
            <flux:table.column>{{ __('app.attendance_category') }}</flux:table.column>
            <flux:table.column>{{ __('app.device_name') }}</flux:table.column>
            <flux:table.column>{{ __('app.status') }}</flux:table.column>
            <flux:table.column class="text-end">{{ __('app.actions') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->records as $record)
                <flux:table.row :key="$record->id">
                    <flux:table.cell>{{ $record->user?->name }}</flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ \Morilog\Jalali\Jalalian::fromDateTime($record->recorded_at)->format('Y/m/d') }}</flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ \Morilog\Jalali\Jalalian::fromDateTime($record->recorded_at)->format('H:i:s') }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex flex-wrap items-center gap-1">
                            @if($record->type === 'clock_in')
                                <flux:badge color="green">{{ __('app.clock_in') }}</flux:badge>
                            @else
                                <flux:badge color="red">{{ __('app.clock_out') }}</flux:badge>
                            @endif
                            @if($record->is_manual)
                                <flux:badge color="zinc" size="sm">{{ __('app.manual_record') }}</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">{{ __('app.normal_record') }}</flux:badge>
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
                    <flux:table.cell class="whitespace-nowrap text-end">
                        <div class="flex gap-2 justify-end">
                            @if(!$record->is_device_approved)
                                <flux:tooltip content="{{ __('app.approve') }}">
                                    <flux:button size="xs" variant="primary" color="green" icon="check" icon:variant="outline" wire:click="approve({{ $record->id }})" />
                                </flux:tooltip>
                            @else
                                <flux:tooltip content="{{ __('app.reject') }}">
                                    <flux:button size="xs" variant="primary" color="orange" icon="x" icon:variant="outline" wire:click="reject({{ $record->id }})" />
                                </flux:tooltip>
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="8">
                        <p class="text-sm text-zinc-500 py-4 text-center">{{ __('app.no_results_found') }}</p>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
