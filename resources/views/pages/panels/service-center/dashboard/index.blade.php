<?php

use App\Enums\StatusEnum;
use App\Models\ServiceCenter\Repair;
use Flux\Flux;
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

    public function sortItem($item, $position)
    {
        $repair = Repair::findOrFail($item);
        $oldStatus = StatusEnum::tryFromSafe($repair->status);
        $targetStatus = StatusEnum::New;

        // Update the repair's status if it changed
        if ($oldStatus !== $targetStatus) {
            $repair->status = $targetStatus->value;
            $repair->status_date = now();
            $repair->save();
        }
    }

    public function sortItemDoing($item, $position)
    {
        $repair = Repair::findOrFail($item);
        $oldStatus = StatusEnum::tryFromSafe($repair->status);
        $targetStatus = StatusEnum::InProgress;

        // Update the repair's status if it changed
        if ($oldStatus !== $targetStatus) {
            $repair->status = $targetStatus->value;
            $repair->status_date = now();
            $repair->save();
        }
    }

    public function sortItemDone($item, $position)
    {
        $repair = Repair::findOrFail($item);
        $oldStatus = StatusEnum::tryFromSafe($repair->status);
        $targetStatus = StatusEnum::Completed;

        // Update the repair's status if it changed
        if ($oldStatus !== $targetStatus) {
            $repair->status = $targetStatus->value;
            $repair->status_date = now();
            $repair->save();
            $repair->sendCompletedSms();
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
            StatusEnum::UnderReview->value
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
};
?>

<x-slot name="title">
    {{ __('app.service_center_dashboard') ?? 'Service Center Dashboard' }}
</x-slot>

<div>
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
                            <div class="flex items-center gap-2">
                                <flux:badge :color="$statusEnum->color()" size="sm">
                                    {{ $statusEnum->label() }}
                                </flux:badge>
                            </div>
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
            <flux:kanban.column.footer>
                <form>
                    <flux:kanban.card>
                        <div class="flex items-center gap-1">
                            <flux:heading class="flex-1">
                                <input class="w-full outline-none" placeholder="New card...">
                            </flux:heading>
                            <flux:button type="submit" variant="filled" size="sm" inset="top bottom" class="-me-1.5">Add</flux:button>
                        </div>
                    </flux:kanban.card>
                </form>
                <flux:button variant="subtle" icon="plus" size="sm" align="start">
                    New card
                </flux:button>
            </flux:kanban.column.footer>
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
                            <div class="flex items-center gap-2">
                                <flux:badge :color="$statusEnum->color()" size="sm">
                                    {{ $statusEnum->label() }}
                                </flux:badge>
                            </div>
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
            <flux:kanban.column.footer>
                <form>
                    <flux:kanban.card>
                        <div class="flex items-center gap-1">
                            <flux:heading class="flex-1">
                                <input class="w-full outline-none" placeholder="New card...">
                            </flux:heading>
                            <flux:button type="submit" variant="filled" size="sm" inset="top bottom" class="-me-1.5">Add</flux:button>
                        </div>
                    </flux:kanban.card>
                </form>
                <flux:button variant="subtle" icon="plus" size="sm" align="start">
                    New card
                </flux:button>
            </flux:kanban.column.footer>
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
                            <div class="flex items-center gap-2">
                                <flux:badge :color="$statusEnum->color()" size="sm">
                                    {{ $statusEnum->label() }}
                                </flux:badge>
                            </div>
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
            <flux:kanban.column.footer>
                <form>
                    <flux:kanban.card>
                        <div class="flex items-center gap-1">
                            <flux:heading class="flex-1">
                                <input class="w-full outline-none" placeholder="New card...">
                            </flux:heading>
                            <flux:button type="submit" variant="filled" size="sm" inset="top bottom" class="-me-1.5">Add</flux:button>
                        </div>
                    </flux:kanban.card>
                </form>
                <flux:button variant="subtle" icon="plus" size="sm" align="start">
                    New card
                </flux:button>
            </flux:kanban.column.footer>
        </flux:kanban.column>
    </flux:kanban>
</div>
