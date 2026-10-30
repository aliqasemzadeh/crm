<?php

use App\Enums\AssemblyStatusEnum;
use App\Enums\StatusEnum;
use App\Models\ServiceCenter\Assembly;
use App\Models\ServiceCenter\Repair;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.panels.service-center')] class extends Component
{
    public function mount(): void
    {
        $this->authorize('service_center_dashboard_index');
    }

    public function sortItem($item, $position): void
    {
        $repair = Repair::findOrFail($item);
        $oldStatus = StatusEnum::tryFromSafe($repair->status);
        $targetStatus = StatusEnum::New;

        if ($oldStatus !== $targetStatus) {
            $repair->status = $targetStatus->value;
            $repair->status_date = now();
            $repair->save();
        }
    }

    public function sortItemDoing($item, $position): void
    {
        $repair = Repair::findOrFail($item);
        $oldStatus = StatusEnum::tryFromSafe($repair->status);
        $targetStatus = StatusEnum::InProgress;

        if ($oldStatus !== $targetStatus) {
            $repair->status = $targetStatus->value;
            $repair->status_date = now();
            $repair->save();
        }
    }

    public function sortItemDone($item, $position): void
    {
        $repair = Repair::findOrFail($item);
        $oldStatus = StatusEnum::tryFromSafe($repair->status);
        $targetStatus = StatusEnum::Completed;

        if ($oldStatus !== $targetStatus) {
            $repair->status = $targetStatus->value;
            $repair->status_date = now();
            $repair->save();
            $repair->sendCompletedSms();
        }
    }

    public function sortAssemblyPending($item, $position): void
    {
        $this->moveAssemblyStatus((int) $item, AssemblyStatusEnum::Pending);
    }

    public function sortAssemblyDoing($item, $position): void
    {
        $this->moveAssemblyStatus((int) $item, AssemblyStatusEnum::InProgress);
    }

    public function sortAssemblyReady($item, $position): void
    {
        $this->moveAssemblyStatus((int) $item, AssemblyStatusEnum::Ready);
    }

    protected function moveAssemblyStatus(int $id, AssemblyStatusEnum $target): void
    {
        $assembly = Assembly::query()->findOrFail($id);
        $current = $assembly->status instanceof AssemblyStatusEnum
            ? $assembly->status
            : AssemblyStatusEnum::tryFromSafe((string) $assembly->status);

        if ($current !== $target) {
            $assembly->applyStatus($target);
        }
    }

    #[Computed]
    public function plannedRepairs(): Collection
    {
        return Repair::where('status', StatusEnum::New->value)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    #[Computed]
    public function doingRepairs(): Collection
    {
        return Repair::whereIn('status', [
            StatusEnum::InProgress->value,
            StatusEnum::UnderRepair->value,
            StatusEnum::UnderReview->value,
        ])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    #[Computed]
    public function doneRepairs(): Collection
    {
        return Repair::where('status', StatusEnum::Completed->value)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    #[Computed]
    public function pendingAssemblies(): Collection
    {
        return Assembly::query()
            ->with(['order', 'assembledBy'])
            ->where('status', AssemblyStatusEnum::Pending->value)
            ->orderByDesc('created_at')
            ->get();
    }

    #[Computed]
    public function doingAssemblies(): Collection
    {
        return Assembly::query()
            ->with(['order', 'assembledBy'])
            ->where('status', AssemblyStatusEnum::InProgress->value)
            ->orderByDesc('created_at')
            ->get();
    }

    #[Computed]
    public function readyAssemblies(): Collection
    {
        return Assembly::query()
            ->with(['order', 'assembledBy'])
            ->where('status', AssemblyStatusEnum::Ready->value)
            ->orderByDesc('created_at')
            ->get();
    }

    #[Computed]
    public function assemblySummary(): array
    {
        $total = Assembly::query()->count();
        $ready = Assembly::query()->whereIn('status', [
            AssemblyStatusEnum::Ready->value,
            AssemblyStatusEnum::Delivered->value,
        ])->count();
        $inProgress = Assembly::query()->where('status', AssemblyStatusEnum::InProgress->value)->count();

        $avgMinutes = Assembly::query()
            ->whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, started_at, completed_at)) as avg_minutes')
            ->value('avg_minutes');

        return [
            'total' => $total,
            'ready' => $ready,
            'in_progress' => $inProgress,
            'avg_minutes' => $avgMinutes !== null ? (int) round((float) $avgMinutes) : null,
        ];
    }
};
?>

<x-slot name="title">
    {{ __('app.service_center_dashboard') ?? 'Service Center Dashboard' }}
</x-slot>

<div class="space-y-8">
    <div>
        <flux:heading size="lg" class="mb-4">{{ __('app.repairs') }}</flux:heading>
        <flux:kanban>
            <flux:kanban.column>
                <flux:kanban.column.header heading="برنامه ریزی شده" :count="$this->plannedRepairs->count()" />
                <flux:kanban.column.cards wire:sort="sortItem" wire:sort:group="repairs">
                    @foreach ($this->plannedRepairs as $repair)
                        @php
                            $statusEnum = $repair->status_enum;
                            $displayDate = $repair->status_date ?? $repair->created_at;
                        @endphp
                        <flux:kanban.card wire:sort:item="{{ $repair->id }}" :heading="$repair->admission_code">
                            <div class="space-y-2">
                                <flux:badge :color="$statusEnum->color()" size="sm">{{ $statusEnum->label() }}</flux:badge>
                                <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                    <div class="font-medium">{{ $repair->owner_name ?? __('app.unknown_owner') }}</div>
                                    <div class="text-xs mt-1">{{ $repair->device_name }}</div>
                                </div>
                            </div>
                            <x-slot name="footer">
                                <flux:icon name="calendar" variant="micro" class="text-zinc-400" />
                                <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ \Morilog\Jalali\Jalalian::fromCarbon($displayDate)->format('Y/m/d') }}
                                </span>
                            </x-slot>
                        </flux:kanban.card>
                    @endforeach
                </flux:kanban.column.cards>
            </flux:kanban.column>

            <flux:kanban.column>
                <flux:kanban.column.header heading="در حال انجام" :count="$this->doingRepairs->count()" />
                <flux:kanban.column.cards wire:sort="sortItemDoing" wire:sort:group="repairs">
                    @foreach ($this->doingRepairs as $repair)
                        @php
                            $statusEnum = $repair->status_enum;
                            $displayDate = $repair->status_date ?? $repair->created_at;
                        @endphp
                        <flux:kanban.card wire:sort:item="{{ $repair->id }}" :heading="$repair->admission_code">
                            <div class="space-y-2">
                                <flux:badge :color="$statusEnum->color()" size="sm">{{ $statusEnum->label() }}</flux:badge>
                                <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                    <div class="font-medium">{{ $repair->owner_name ?? __('app.unknown_owner') }}</div>
                                    <div class="text-xs mt-1">{{ $repair->device_name }}</div>
                                </div>
                            </div>
                            <x-slot name="footer">
                                <flux:icon name="calendar" variant="micro" class="text-zinc-400" />
                                <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ \Morilog\Jalali\Jalalian::fromCarbon($displayDate)->format('Y/m/d') }}
                                </span>
                            </x-slot>
                        </flux:kanban.card>
                    @endforeach
                </flux:kanban.column.cards>
            </flux:kanban.column>

            <flux:kanban.column>
                <flux:kanban.column.header heading="انجام شده" :count="$this->doneRepairs->count()" />
                <flux:kanban.column.cards wire:sort="sortItemDone" wire:sort:group="repairs">
                    @foreach ($this->doneRepairs as $repair)
                        @php
                            $statusEnum = $repair->status_enum;
                            $displayDate = $repair->status_date ?? $repair->created_at;
                        @endphp
                        <flux:kanban.card wire:sort:item="{{ $repair->id }}" :heading="$repair->admission_code">
                            <div class="space-y-2">
                                <flux:badge :color="$statusEnum->color()" size="sm">{{ $statusEnum->label() }}</flux:badge>
                                <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                    <div class="font-medium">{{ $repair->owner_name ?? __('app.unknown_owner') }}</div>
                                    <div class="text-xs mt-1">{{ $repair->device_name }}</div>
                                </div>
                            </div>
                            <x-slot name="footer">
                                <flux:icon name="calendar" variant="micro" class="text-zinc-400" />
                                <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ \Morilog\Jalali\Jalalian::fromCarbon($displayDate)->format('Y/m/d') }}
                                </span>
                            </x-slot>
                        </flux:kanban.card>
                    @endforeach
                </flux:kanban.column.cards>
            </flux:kanban.column>
        </flux:kanban>
    </div>

    <div>
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <flux:heading size="lg">{{ __('app.assembly_dashboard') }}</flux:heading>
            <div class="flex flex-wrap gap-2 text-sm">
                <flux:badge color="blue">{{ __('app.assembly_quantity') }}: {{ $this->assemblySummary['total'] }}</flux:badge>
                <flux:badge color="amber">{{ __('app.assembly_status_in_progress') }}: {{ $this->assemblySummary['in_progress'] }}</flux:badge>
                <flux:badge color="green">{{ __('app.assembly_status_ready') }}: {{ $this->assemblySummary['ready'] }}</flux:badge>
                @if ($this->assemblySummary['avg_minutes'] !== null)
                    <flux:badge color="violet">
                        {{ __('app.assembly_duration') }}:
                        {{ __('app.assembly_duration_minutes', ['minutes' => $this->assemblySummary['avg_minutes']]) }}
                    </flux:badge>
                @endif
            </div>
        </div>

        <flux:kanban>
            <flux:kanban.column>
                <flux:kanban.column.header heading="{{ __('app.assembly_status_pending') }}" :count="$this->pendingAssemblies->count()" />
                <flux:kanban.column.cards wire:sort="sortAssemblyPending" wire:sort:group="assemblies">
                    @foreach ($this->pendingAssemblies as $assembly)
                        <flux:kanban.card
                            wire:sort:item="{{ $assembly->id }}"
                            :heading="($assembly->order?->code ?? '-').' #'.$assembly->unit_number"
                        >
                            <div class="space-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                                <div class="font-medium">{{ $assembly->order?->customer_name ?: '-' }}</div>
                                <div class="text-xs">{{ __('app.invoice_number') }}: {{ $assembly->order?->invoice_number }}</div>
                            </div>
                            <x-slot name="footer">
                                <flux:icon name="calendar" variant="micro" class="text-zinc-400" />
                                <span class="text-xs text-zinc-500">
                                    {{ \Morilog\Jalali\Jalalian::fromCarbon($assembly->created_at)->format('Y/m/d') }}
                                </span>
                            </x-slot>
                        </flux:kanban.card>
                    @endforeach
                </flux:kanban.column.cards>
            </flux:kanban.column>

            <flux:kanban.column>
                <flux:kanban.column.header heading="{{ __('app.assembly_status_in_progress') }}" :count="$this->doingAssemblies->count()" />
                <flux:kanban.column.cards wire:sort="sortAssemblyDoing" wire:sort:group="assemblies">
                    @foreach ($this->doingAssemblies as $assembly)
                        <flux:kanban.card
                            wire:sort:item="{{ $assembly->id }}"
                            :heading="($assembly->order?->code ?? '-').' #'.$assembly->unit_number"
                        >
                            <div class="space-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                                <div class="font-medium">{{ $assembly->order?->customer_name ?: '-' }}</div>
                                <div class="text-xs">{{ __('app.assembled_by') }}: {{ $assembly->assembledBy?->name ?: '-' }}</div>
                            </div>
                            <x-slot name="footer">
                                <flux:icon name="calendar" variant="micro" class="text-zinc-400" />
                                <span class="text-xs text-zinc-500">
                                    {{ \Morilog\Jalali\Jalalian::fromCarbon($assembly->started_at ?? $assembly->created_at)->format('Y/m/d') }}
                                </span>
                            </x-slot>
                        </flux:kanban.card>
                    @endforeach
                </flux:kanban.column.cards>
            </flux:kanban.column>

            <flux:kanban.column>
                <flux:kanban.column.header heading="{{ __('app.assembly_status_ready') }}" :count="$this->readyAssemblies->count()" />
                <flux:kanban.column.cards wire:sort="sortAssemblyReady" wire:sort:group="assemblies">
                    @foreach ($this->readyAssemblies as $assembly)
                        @php $duration = $assembly->durationMinutes(); @endphp
                        <flux:kanban.card
                            wire:sort:item="{{ $assembly->id }}"
                            :heading="($assembly->order?->code ?? '-').' #'.$assembly->unit_number"
                        >
                            <div class="space-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                                <div class="font-medium">{{ $assembly->system_serial ?: __('app.assembly_system_serial') }}</div>
                                <div class="text-xs">{{ __('app.assembled_by') }}: {{ $assembly->assembledBy?->name ?: '-' }}</div>
                                @if ($duration !== null)
                                    <div class="text-xs">{{ __('app.assembly_duration_minutes', ['minutes' => $duration]) }}</div>
                                @endif
                            </div>
                            <x-slot name="footer">
                                <flux:icon name="calendar" variant="micro" class="text-zinc-400" />
                                <span class="text-xs text-zinc-500">
                                    {{ \Morilog\Jalali\Jalalian::fromCarbon($assembly->completed_at ?? $assembly->created_at)->format('Y/m/d') }}
                                </span>
                            </x-slot>
                        </flux:kanban.card>
                    @endforeach
                </flux:kanban.column.cards>
            </flux:kanban.column>
        </flux:kanban>
    </div>
</div>
