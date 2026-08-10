<?php

use App\Models\Calender\Day;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

new #[Layout('layouts.panels.administrator')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        $this->authorize('administrator_calender_index');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[On('panels.administrator.calender.index.render')]
    public function refresh(): void
    {
        unset($this->days);
    }

    #[Computed]
    public function days()
    {
        return Day::query()
            ->when($this->search !== '', function ($query) {
                $search = '%'.$this->search.'%';
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', $search)
                        ->orWhere('date', 'like', $search)
                        ->orWhere('source', 'like', $search);
                });
            })
            ->orderByDesc('date')
            ->paginate(20);
    }

    public function delete(int $id): void
    {
        $this->authorize('administrator_calender_delete');

        Day::query()->findOrFail($id)->delete();

        Flux::toast(__('app.deleted_successfully', ['name' => __('app.calender_day')]));
        unset($this->days);
    }
};
?>

<x-slot name="title">
    {{ __('app.calender_days') }}
</x-slot>

<div>
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.calender_days') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.calender_days_description') }}</flux:subheading>
            </div>
            @can('administrator_calender_create')
                <flux:modal.trigger name="panels.administrator.calender.create.modal">
                    <flux:button variant="primary" color="teal" icon="calendar">{{ __('app.create_calender_day') }}</flux:button>
                </flux:modal.trigger>
            @endcan
        </div>
        <flux:separator variant="subtle" />
    </div>

    <livewire:calender.create :key="'calender-create'" />
    <livewire:calender.edit :key="'calender-edit'" />

    <flux:table :paginate="$this->days">
        <flux:table.columns sticky class="bg-white dark:bg-zinc-900">
            <flux:table.column colspan="5" class="bg-white dark:bg-zinc-900">
                <div class="flex flex-col gap-1 pe-2 items-end">
                    <flux:input
                        size="sm"
                        placeholder="{{ __('app.search_placeholder') }}"
                        wire:model.live="search"
                    />
                </div>
            </flux:table.column>
        </flux:table.columns>
        <flux:table.columns>
            <flux:table.column>{{ __('app.id') }}</flux:table.column>
            <flux:table.column sortable>{{ __('app.date') }}</flux:table.column>
            <flux:table.column>{{ __('app.title') }}</flux:table.column>
            <flux:table.column>{{ __('app.calender_source') }}</flux:table.column>
            <flux:table.column>{{ __('app.options') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->days as $day)
                <flux:table.row :key="$day->id">
                    <flux:table.cell>{{ $day->id }}</flux:table.cell>
                    <flux:table.cell>
                        {{ Jalalian::fromDateTime($day->date)->format('Y/m/d') }}
                    </flux:table.cell>
                    <flux:table.cell>{{ $day->title }}</flux:table.cell>
                    <flux:table.cell>
                        @if($day->source === 'official')
                            <flux:badge color="blue" size="sm">{{ __('app.calender_source_official') }}</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">{{ __('app.calender_source_manual') }}</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">
                        @can('administrator_calender_edit')
                            <flux:tooltip content="{{ __('app.edit') }}">
                                <flux:button
                                    size="xs"
                                    variant="primary"
                                    color="orange"
                                    icon="pencil"
                                    icon:variant="outline"
                                    wire:click="$dispatch('panels.administrator.calender.edit.assign-data', { id: {{ $day->id }} })"
                                />
                            </flux:tooltip>
                        @endcan
                        @can('administrator_calender_delete')
                            <flux:tooltip content="{{ __('app.delete') }}">
                                <flux:button
                                    size="xs"
                                    variant="primary"
                                    color="red"
                                    icon="trash"
                                    icon:variant="outline"
                                    wire:click="delete({{ $day->id }})"
                                    wire:confirm="{{ __('app.are_you_sure') }}"
                                />
                            </flux:tooltip>
                        @endcan
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center py-4">
                        {{ __('app.no_records_found') }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
