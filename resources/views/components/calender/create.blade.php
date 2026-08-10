<?php

use App\Livewire\Forms\Calender\DayForm;
use App\Models\Calender\Day;
use Flux\Flux;
use Livewire\Component;

new class extends Component
{
    public DayForm $form;

    public function save(): void
    {
        $this->authorize('administrator_calender_create');

        $validated = $this->form->validateForSave();

        Day::query()->create([
            ...$validated,
            'source' => 'manual',
        ]);

        $this->form->reset();
        $this->form->dayId = null;

        $this->dispatch('panels.administrator.calender.index.render');
        Flux::modal('panels.administrator.calender.create.modal')->close();
        Flux::toast(__('app.saved_successfully', ['name' => __('app.calender_day')]));
    }
};
?>

<flux:modal name="panels.administrator.calender.create.modal" flyout position="right" class="md:w-96">
    <form wire:submit="save" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.create_calender_day') }}</flux:heading>
            <flux:subheading>{{ __('app.calender_days_description') }}</flux:subheading>
        </div>

        <x-date-select wire:model="form.date" :label="__('app.date')" :required="true" />
        <flux:error name="form.date" />
        <flux:input wire:model="form.title" label="{{ __('app.title') }}" />
        <flux:error name="form.title" />

        <flux:button type="submit" variant="primary" color="orange" class="w-full">{{ __('app.save') }}</flux:button>
    </form>
</flux:modal>
