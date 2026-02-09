<flux:modal name="review-user-task-edit-modal" class="md:w-[600px] space-y-6">
    <div>
        <flux:heading size="lg">{{ __('app.edit_task') }}</flux:heading>
        <flux:subheading>{{ __('app.workspace.review.edit_task_for_user') }} {{ $user->name }}</flux:subheading>
    </div>

    @if($task)
        <form wire:submit="update" class="space-y-6">
            <flux:input wire:model="title" label="{{ __('app.title') }}" />
            <flux:textarea wire:model="description" label="{{ __('app.description') }}" />

            <div class="grid grid-cols-2 gap-4">
                <flux:select wire:model="status" label="{{ __('app.status') }}">
                    <flux:select.option value="planning">{{ __('app.statuses.planning') }}</flux:select.option>
                    <flux:select.option value="doing">{{ __('app.statuses.doing') }}</flux:select.option>
                    <flux:select.option value="done">{{ __('app.statuses.done') }}</flux:select.option>
                </flux:select>

                <flux:select wire:model="priority" label="{{ __('app.priority') }}">
                    <flux:select.option value="low">{{ __('app.priorities.low') }}</flux:select.option>
                    <flux:select.option value="medium">{{ __('app.priorities.medium') }}</flux:select.option>
                    <flux:select.option value="high">{{ __('app.priorities.high') }}</flux:select.option>
                    <flux:select.option value="urgent">{{ __('app.priorities.urgent') }}</flux:select.option>
                </flux:select>
            </div>

            <flux:input type="date" wire:model="due_at" label="{{ __('app.due_date') }}" />

            <div class="flex">
                <flux:spacer />
                <flux:button type="submit" variant="primary">{{ __('app.save') }}</flux:button>
            </div>
        </form>
    @endif
</flux:modal>
