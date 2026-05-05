<div>
    <flux:main>
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
            <flux:heading size="xl">{{ __('app.task.my_tasks') }}</flux:heading>
            <div class="flex flex-wrap items-center gap-4">
                <flux:select wire:model.live="filter_status" placeholder="{{ __('app.task.status') }}" class="w-40">
                    <flux:select.option value="">{{ __('app.all') }}</flux:select.option>
                    <flux:select.option value="planning">{{ __('app.task.statuses.planning') }}</flux:select.option>
                    <flux:select.option value="doing">{{ __('app.task.statuses.doing') }}</flux:select.option>
                    <flux:select.option value="done">{{ __('app.task.statuses.done') }}</flux:select.option>
                </flux:select>

                <flux:select wire:model.live="filter_approval_status" placeholder="{{ __('app.task.review.approval_status') }}" class="w-40">
                    <flux:select.option value="">{{ __('app.all') }}</flux:select.option>
                    <flux:select.option value="pending">{{ __('app.pending') }}</flux:select.option>
                    <flux:select.option value="approved">{{ __('app.task.review.status_approved') }}</flux:select.option>
                    <flux:select.option value="rejected">{{ __('app.rejected') }}</flux:select.option>
                </flux:select>

                <flux:button href="{{ route('panels.workspace.task.create') }}" icon="plus" variant="primary" wire:navigate>
                    {{ __('app.task.create') }}
                </flux:button>
            </div>
        </div>

        <div class="space-y-4">
            @forelse ($tasks as $task)
                <flux:card>
                    <x-workspace.task.card
                        :task="$task"
                        :show-drag-handle="false"
                        :show-status-badge="true"
                        :show-edit-action="false"
                        :show-assign-action="false"
                        :show-checklist-action="false"
                        :show-activity-action="false"
                        :show-delete-action="false"
                    >
                        <flux:button href="{{ route('panels.workspace.task.assigns', $task) }}" icon="user-plus" variant="ghost" size="sm" wire:navigate />
                        @if($task->approval_status !== 'approved')
                            <flux:button href="{{ route('panels.workspace.task.edit', $task) }}" icon="pencil-square" variant="ghost" size="sm" wire:navigate />
                        @endif
                        @if($task->status !== 'done' && $task->approval_status !== 'approved')
                            <flux:modal.trigger name="delete-task-{{ $task->id }}">
                                <flux:button icon="trash" variant="ghost" size="sm" color="red" />
                            </flux:modal.trigger>

                            <flux:modal name="delete-task-{{ $task->id }}" flyout position="right" class="min-w-[22rem]">
                                <form class="space-y-6" wire:submit="deleteTask({{ $task->id }})">
                                    <div>
                                        <flux:heading size="lg">{{ __('app.task.delete') }}</flux:heading>
                                        <flux:subheading>{{ __('app.are_you_sure') }}</flux:subheading>
                                    </div>

                                    <div class="flex gap-2">
                                        <flux:spacer />
                                        <flux:modal.close>
                                            <flux:button variant="ghost">{{ __('app.task.cancel') }}</flux:button>
                                        </flux:modal.close>
                                        <flux:button type="submit" variant="danger">{{ __('app.task.delete') }}</flux:button>
                                    </div>
                                </form>
                            </flux:modal>
                        @endif
                    </x-workspace.task.card>
                </flux:card>
            @empty
                <div class="p-12 text-center border-2 border-dashed border-zinc-200 dark:border-zinc-700 rounded-xl">
                    <flux:icon.clipboard-document-list class="mx-auto h-12 w-12 text-zinc-400" />
                    <flux:heading class="mt-4">{{ __('app.task.no_tasks') }}</flux:heading>
                    <div class="mt-6">
                        <flux:button href="{{ route('panels.workspace.task.create') }}" icon="plus" variant="primary" wire:navigate>
                            {{ __('app.task.create') }}
                        </flux:button>
                    </div>
                </div>
            @endforelse

            <div class="mt-4">
                {{ $tasks->links() }}
            </div>
        </div>
    </flux:main>
</div>
