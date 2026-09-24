<?php

use App\Livewire\Forms\Hr\DayRecordForm;
use App\Models\Hr\DayRecord;
use App\Services\Hr\DayRecordBaleNotifier;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

new #[Layout('layouts.panels.hr')] class extends Component
{
    use WithPagination;

    public DayRecordForm $form;

    public string $filterStatus = '';

    public ?string $dateStart = null;

    public ?string $dateEnd = null;

    protected function queryString(): array
    {
        return [
            'filterStatus' => ['except' => ''],
            'dateStart' => ['except' => null],
            'dateEnd' => ['except' => null],
        ];
    }

    public function mount(): void
    {
        $this->form->resetForm();
    }

    #[Computed]
    public function dayRecords()
    {
        return DayRecord::query()
            ->where('user_id', auth()->id())
            ->when($this->filterStatus !== '', fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->dateStart, function ($q) {
                $start = Jalalian::fromFormat('Y/m/d', $this->dateStart)->toCarbon()->startOfDay();
                $q->whereDate('date', '>=', $start);
            })
            ->when($this->dateEnd, function ($q) {
                $end = Jalalian::fromFormat('Y/m/d', $this->dateEnd)->toCarbon()->endOfDay();
                $q->whereDate('date', '<=', $end);
            })
            ->orderByDesc('date')
            ->paginate(20);
    }

    public function save(DayRecordBaleNotifier $notifier): void
    {
        $this->form->validate();
        $this->form->assertUniqueForUser((int) auth()->id());

        $dayRecord = DayRecord::create([
            'user_id' => auth()->id(),
            'date' => $this->form->toDateString(),
            'type' => $this->form->type,
            'status' => DayRecord::STATUS_PENDING,
            'user_note' => $this->form->user_note ?: null,
        ]);

        $notifier->notifySubmitted($dayRecord);

        $this->form->resetForm();
        unset($this->dayRecords);
        Flux::modal('panels.hr.days.create.modal')->close();
        Flux::toast(__('app.hr_day_record_created'));
    }

    public function delete(int $id): void
    {
        $dayRecord = DayRecord::query()
            ->where('user_id', auth()->id())
            ->where('status', DayRecord::STATUS_PENDING)
            ->findOrFail($id);

        $dayRecord->delete();

        unset($this->dayRecords);
        Flux::toast(__('app.hr_day_record_deleted'));
    }

    public function updatingFilterStatus(): void
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
};

?>

<x-slot name="title">
    {{ __('app.hr_day_records') }}
</x-slot>
<div>
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.hr_day_records') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.hr_day_records_description') }}</flux:subheading>
            </div>
            <flux:modal.trigger name="panels.hr.days.create.modal">
                <flux:button variant="primary" color="teal" icon="plus">{{ __('app.hr_day_record_create') }}</flux:button>
            </flux:modal.trigger>
        </div>
        <flux:separator variant="subtle" />
    </div>

    <flux:card class="panel-filter-card mb-6">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <div wire:key="hr-days-date-range-{{ $dateStart ?? 'all' }}-{{ $dateEnd ?? 'all' }}">
                <x-jalali-date-range
                    :label="__('app.date_range')"
                    :start="$dateStart"
                    :end="$dateEnd"
                    wire:model.start="dateStart"
                    wire:model.end="dateEnd"
                />
            </div>
            <flux:select wire:model.live="filterStatus" :label="__('app.status')" searchable>
                <flux:select.option value="">{{ __('app.all') }}</flux:select.option>
                <flux:select.option value="pending">{{ __('app.pending_approval') }}</flux:select.option>
                <flux:select.option value="approved">{{ __('app.approved') }}</flux:select.option>
                <flux:select.option value="rejected">{{ __('app.rejected') }}</flux:select.option>
            </flux:select>
        </div>
    </flux:card>

    <flux:table :paginate="$this->dayRecords">
        <flux:table.columns>
            <flux:table.column class="whitespace-nowrap">{{ __('app.date') }}</flux:table.column>
            <flux:table.column>{{ __('app.type') }}</flux:table.column>
            <flux:table.column>{{ __('app.status') }}</flux:table.column>
            <flux:table.column>{{ __('app.description') }}</flux:table.column>
            <flux:table.column class="text-end">{{ __('app.actions') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->dayRecords as $dayRecord)
                <flux:table.row :key="$dayRecord->id">
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
                        {{ $dayRecord->user_note ? \Illuminate\Support\Str::limit($dayRecord->user_note, 60) : '—' }}
                    </flux:table.cell>
                    <flux:table.cell class="text-end whitespace-nowrap">
                        @if($dayRecord->isPending())
                            <flux:tooltip content="{{ __('app.delete') }}">
                                <flux:button
                                    size="xs"
                                    variant="primary"
                                    color="red"
                                    icon="trash"
                                    icon:variant="outline"
                                    wire:click="delete({{ $dayRecord->id }})"
                                    wire:confirm="{{ __('common.are_you_sure') }}"
                                />
                            </flux:tooltip>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">
                        <p class="text-sm text-zinc-500 py-4 text-center">{{ __('app.no_results_found') }}</p>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="panels.hr.days.create.modal" flyout position="right" class="md:w-96">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ __('app.hr_day_record_create') }}</flux:heading>

            <flux:select wire:model="form.type" :label="__('app.type')" searchable>
                <flux:select.option value="leave">{{ __('app.hr_day_record_type_leave') }}</flux:select.option>
                <flux:select.option value="mission">{{ __('app.hr_day_record_type_mission') }}</flux:select.option>
            </flux:select>
            <flux:error name="form.type" />

            <x-date-select wire:model="form.date" :label="__('app.date')" :required="true" />
            <flux:error name="form.date" />

            <flux:textarea wire:model="form.user_note" :label="__('app.description')" rows="3" />
            <flux:error name="form.user_note" />

            <flux:button type="submit" variant="primary" color="orange" class="w-full">
                {{ __('app.save') }}
            </flux:button>
        </form>
    </flux:modal>
</div>
