<?php

use App\Livewire\Forms\Calender\DayForm;
use App\Models\Calender\Day;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

new class extends Component
{
    public DayForm $form;

    public ?int $dayId = null;

    #[On('panels.administrator.calender.edit.assign-data')]
    public function assignData(int $id): void
    {
        $day = Day::query()->findOrFail($id);

        $this->dayId = $day->id;
        $this->form->dayId = $day->id;
        $this->form->date = Jalalian::fromDateTime($day->date)->format('Y/m/d');
        $this->form->title = $day->title;

        Flux::modal('panels.administrator.calender.edit.modal')->show();
    }

    public function save(): void
    {
        $this->authorize('administrator_calender_edit');

        if (! $this->dayId) {
            return;
        }

        $validated = $this->form->validateForSave();

        Day::query()->findOrFail($this->dayId)->update($validated);

        $this->dispatch('panels.administrator.calender.index.render');
        Flux::modal('panels.administrator.calender.edit.modal')->close();
        Flux::toast(__('app.saved_successfully', ['name' => __('app.calender_day')]));
    }
};
?>

<flux:modal name="panels.administrator.calender.edit.modal" flyout position="right" class="md:w-96">
    <form wire:submit="save" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.edit_calender_day') }}</flux:heading>
            <flux:subheading>{{ __('app.calender_days_description') }}</flux:subheading>
        </div>

        <x-date-select wire:model="form.date" :label="__('app.date')" :required="true" />
        <flux:error name="form.date" />
        <flux:input wire:model="form.title" label="{{ __('app.title') }}" />
        <flux:error name="form.title" />

        <flux:button type="submit" variant="primary" color="orange" class="w-full">{{ __('app.save') }}</flux:button>
    </form>
</flux:modal>
