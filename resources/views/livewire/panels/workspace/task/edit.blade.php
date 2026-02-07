<div>
    <flux:main>
        <div class="mb-6">
            <flux:heading size="xl">{{ __('app.task.edit') }}</flux:heading>
        </div>

        <form wire:submit="save" class="space-y-6 max-w-2xl">
            <flux:input wire:model="title" label="{{ __('app.task.title') }}" />

            <flux:textarea wire:model="description" label="{{ __('app.task.description') }}" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:select wire:model="status" label="{{ __('app.task.status') }}">
                    <flux:select.option value="planning">{{ __('app.task.statuses.planning') }}</flux:select.option>
                    <flux:select.option value="doing">{{ __('app.task.statuses.doing') }}</flux:select.option>
                    <flux:select.option value="done">{{ __('app.task.statuses.done') }}</flux:select.option>
                </flux:select>

                <flux:select wire:model="priority" label="{{ __('app.task.priority') }}">
                    <flux:select.option value="low">{{ __('app.task.priorities.low') }}</flux:select.option>
                    <flux:select.option value="medium">{{ __('app.task.priorities.medium') }}</flux:select.option>
                    <flux:select.option value="high">{{ __('app.task.priorities.high') }}</flux:select.option>
                    <flux:select.option value="urgent">{{ __('app.task.priorities.urgent') }}</flux:select.option>
                </flux:select>
            </div>

            <flux:input type="date" wire:model="due_at" label="{{ __('app.task.due_at') }}" />

            <div class="flex gap-2">
                <flux:button type="submit" variant="primary">{{ __('app.task.save') }}</flux:button>
                <flux:button href="{{ route('panels.workspace.task.index') }}" variant="ghost" wire:navigate>{{ __('app.task.cancel') }}</flux:button>
            </div>
        </form>
    </flux:main>
</div>
