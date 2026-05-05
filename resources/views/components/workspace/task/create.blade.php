@props([
    'submit' => 'save',
    'saveLabel' => __('app.task.save'),
    'showCancel' => true,
    'cancelLabel' => __('app.task.cancel'),
    'cancelRoute' => route('panels.workspace.task.index'),
    'formClass' => 'space-y-6 max-w-2xl',
    'checklistItems' => [],
])

<form wire:submit="{{ $submit }}" class="{{ $formClass }}">
    <flux:input wire:model="title" :label="__('app.task.title')" />

    <flux:textarea wire:model="description" :label="__('app.task.description')" />

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <flux:select wire:model="status" :label="__('app.task.status')">
            <flux:select.option value="planning">{{ __('app.task.statuses.planning') }}</flux:select.option>
            <flux:select.option value="doing">{{ __('app.task.statuses.doing') }}</flux:select.option>
            <flux:select.option value="done">{{ __('app.task.statuses.done') }}</flux:select.option>
        </flux:select>

        <flux:select wire:model="priority" :label="__('app.task.priority')">
            <flux:select.option value="low">{{ __('app.task.priorities.low') }}</flux:select.option>
            <flux:select.option value="medium">{{ __('app.task.priorities.medium') }}</flux:select.option>
            <flux:select.option value="high">{{ __('app.task.priorities.high') }}</flux:select.option>
            <flux:select.option value="urgent">{{ __('app.task.priorities.urgent') }}</flux:select.option>
        </flux:select>
    </div>

    <flux:input type="date" wire:model="due_at" :label="__('app.task.due_at')" />

    <div x-data class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <flux:select wire:model="repeat_type" :label="__('app.task.repeat_type')">
            <flux:select.option value="none">{{ __('app.task.repeat_types.none') }}</flux:select.option>
            <flux:select.option value="daily">{{ __('app.task.repeat_types.daily') }}</flux:select.option>
            <flux:select.option value="weekly">{{ __('app.task.repeat_types.weekly') }}</flux:select.option>
            <flux:select.option value="monthly">{{ __('app.task.repeat_types.monthly') }}</flux:select.option>
        </flux:select>

        <div x-show="$wire.repeat_type === 'weekly'" x-cloak>
            <flux:select wire:model="repeat_weekday" :label="__('app.task.repeat_weekday')">
                <flux:select.option value="0">{{ __('app.task.weekdays.saturday') }}</flux:select.option>
                <flux:select.option value="1">{{ __('app.task.weekdays.sunday') }}</flux:select.option>
                <flux:select.option value="2">{{ __('app.task.weekdays.monday') }}</flux:select.option>
                <flux:select.option value="3">{{ __('app.task.weekdays.tuesday') }}</flux:select.option>
                <flux:select.option value="4">{{ __('app.task.weekdays.wednesday') }}</flux:select.option>
                <flux:select.option value="5">{{ __('app.task.weekdays.thursday') }}</flux:select.option>
                <flux:select.option value="6">{{ __('app.task.weekdays.friday') }}</flux:select.option>
            </flux:select>
        </div>

        <div x-show="$wire.repeat_type === 'monthly'" x-cloak>
            <flux:input type="number" min="1" max="31" wire:model="repeat_monthday" :label="__('app.task.repeat_monthday')" />
        </div>
    </div>

    @if(auth()->user()?->hasRole('administrator'))
        <flux:checkbox wire:model="is_locked" :label="__('app.task.is_locked')" />
    @endif

    <x-workspace.task.checklist :checklist-items="$checklistItems" />

    <div class="flex gap-2">
        <flux:button type="submit" variant="primary" color="teal">{{ $saveLabel }}</flux:button>

        @if($showCancel)
            <flux:button href="{{ $cancelRoute }}" variant="ghost" wire:navigate>{{ $cancelLabel }}</flux:button>
        @endif
    </div>
</form>