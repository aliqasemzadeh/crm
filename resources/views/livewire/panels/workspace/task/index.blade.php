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
                <div class="p-4 bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 flex justify-between items-center">
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center gap-2">
                            <flux:heading size="lg">{{ $task->title }}</flux:heading>
                            <flux:badge size="sm" :color="match($task->priority) {
                                'low' => 'blue',
                                'medium' => 'yellow',
                                'high' => 'orange',
                                'urgent' => 'red',
                                default => 'zinc',
                            }">{{ __('app.task.priorities.' . $task->priority) }}</flux:badge>
                            <flux:badge size="sm" variant="outline" :color="match($task->status) {
                                'planning' => 'zinc',
                                'doing' => 'yellow',
                                'done' => 'green',
                                default => 'zinc',
                            }">{{ __('app.task.statuses.' . $task->status) }}</flux:badge>
                            @if($task->status === 'done')
                                @if($task->approval_status === 'pending')
                                    <flux:badge color="yellow" size="sm">{{ __('app.pending') }}</flux:badge>
                                @elseif($task->approval_status === 'rejected')
                                    <flux:dropdown hover position="bottom" align="start" offset="-16" gap="10">
                                        <flux:badge color="red" size="sm" class="cursor-help">
                                            {{ __('app.rejected') }}
                                        </flux:badge>

                                        <flux:popover class="max-w-xs p-4">
                                            <div class="space-y-2">
                                                <flux:heading size="sm">{{ __('app.rejection_reason') }}</flux:heading>
                                                <flux:text size="sm">
                                                    {{ $task->review_note ?: __('app.no_reason_provided') }}
                                                </flux:text>
                                            </div>
                                        </flux:popover>
                                    </flux:dropdown>
                                @elseif($task->approval_status === 'approved')
                                    <flux:badge color="green" size="sm">{{ __('app.task.review.status_approved') }}</flux:badge>
                                @endif
                            @endif
                        </div>
                        <flux:subheading>{{ Str::limit($task->description, 100) }}</flux:subheading>
                        @if($task->due_at)
                            <div class="text-xs text-zinc-500 flex items-center gap-1">
                                <flux:icon.calendar variant="micro" />
                                {{ $task->due_at->format('Y-m-d') }}
                            </div>
                        @endif
                    </div>

                    <div class="flex gap-2">
                        <flux:button href="{{ route('panels.workspace.task.assigns', $task) }}" icon="user-plus" variant="ghost" size="sm" wire:navigate />
                        @if($task->approval_status !== 'approved')
                            <flux:button href="{{ route('panels.workspace.task.edit', $task) }}" icon="pencil-square" variant="ghost" size="sm" wire:navigate />
                        @endif
                        @if($task->status !== 'done' && $task->approval_status !== 'approved')
                            <flux:modal.trigger name="delete-task-{{ $task->id }}">
                                <flux:button icon="trash" variant="ghost" size="sm" color="red" />
                            </flux:modal.trigger>

                            <flux:modal name="delete-task-{{ $task->id }}" class="min-w-[22rem]">
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
                    </div>
                </div>
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
