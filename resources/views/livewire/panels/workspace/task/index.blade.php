<div>
    <flux:main>
        <div class="flex justify-between items-center mb-6">
            <flux:heading size="xl">{{ __('app.task.my_tasks') }}</flux:heading>
            <flux:button href="{{ route('panels.workspace.tasks.create') }}" icon="plus" variant="primary" wire:navigate>
                {{ __('app.task.create') }}
            </flux:button>
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
                                    <flux:badge color="red" size="sm">{{ __('app.rejected') }}</flux:badge>
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
                        <flux:button href="{{ route('panels.workspace.tasks.edit', $task) }}" icon="pencil-square" variant="ghost" size="sm" wire:navigate />
                        @if($task->status !== 'done')
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
                        <flux:button href="{{ route('panels.workspace.tasks.create') }}" icon="plus" variant="primary" wire:navigate>
                            {{ __('app.task.create') }}
                        </flux:button>
                    </div>
                </div>
            @endforelse
        </div>
    </flux:main>
</div>
