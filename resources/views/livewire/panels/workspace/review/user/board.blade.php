<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div class="flex items-center gap-4">
            <flux:button variant="subtle" icon="chevron-right" href="{{ route('panels.workspace.review.user.index') }}" />
            <div class="flex items-center gap-3">
                @if($user->avatar)
                    <flux:avatar src="{{ $user->getAvatarUrl() }}" size="lg" />
                @else
                    <flux:avatar name="{{ $user->name }}" color="auto" size="lg" />
                @endif
                <div>
                    <flux:heading size="xl">{{ __('app.workspace_review.user_board') }}: {{ $user->name }}</flux:heading>
                    <flux:subheading size="sm">{{ $user->email }}</flux:subheading>
                </div>
            </div>
        </div>
    </div>

    <livewire:panels.workspace.review.user.task.create :user="$user" />
    <livewire:panels.workspace.review.user.task.edit :user="$user" />
    <livewire:panels.workspace.review.user.task.activity :user="$user" />

    @php
        $allTasks = $this->tasks;
    @endphp

    <flux:kanban>
        @foreach ($statuses as $status)
            @php
                $handler = match($status) {
                    'planning' => 'sortPlanning',
                    'doing' => 'sortDoing',
                    'done' => 'sortDone',
                };
                $colTasks = $allTasks->where('status', $status)->sortBy('order');
            @endphp

            <flux:kanban.column>
                <flux:kanban.column.header :heading="__('app.statuses.'.$status)" :count="$colTasks->count()" />

                <flux:kanban.column.cards wire:sort="{{ $handler }}" wire:sort:group="tasks" wire:key="col-{{ $status }}">
                    @foreach ($colTasks as $task)
                        <flux:kanban.card wire:sort:item="{{ $task->id }}" wire:sort:handle wire:key="task-{{ $task->id }}">
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <div class="cursor-grab">
                                        <flux:icon name="bars-3" variant="micro" class="text-zinc-400" />
                                    </div>

                                    @php
                                        $priorityColor = match($task->priority) {
                                            'low' => 'blue',
                                            'medium' => 'yellow',
                                            'high' => 'orange',
                                            'urgent' => 'red',
                                            default => 'zinc'
                                        };
                                    @endphp

                                    <flux:badge :color="$priorityColor" size="sm">
                                        {{ __('app.priorities.'.$task->priority) }}
                                    </flux:badge>

                                    @if($status === 'done')
                                        @if($task->approval_status === 'pending')
                                            <flux:badge color="yellow" size="sm">{{ __('app.pending') }}</flux:badge>
                                        @elseif($task->approval_status === 'rejected')
                                            <flux:badge color="red" size="sm">{{ __('app.rejected') }}</flux:badge>
                                        @elseif($task->approval_status === 'approved')
                                            <flux:badge color="green" size="sm">{{ __('app.task.review.status_approved') }}</flux:badge>
                                        @endif
                                    @endif
                                </div>

                                <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                    <div wire:sort:ignore>
                                        <div class="font-medium cursor-pointer"
                                             wire:click="$dispatch('panels.workspace.review.user.task.activity.assign-data', { id: {{ $task->id }} })">
                                            {{ $task->title }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <x-slot name="footer">
                                <div class="flex justify-between items-center w-full">
                                    <div class="flex items-center gap-2">
                                        @if($task->due_at)
                                            <div class="text-xs text-zinc-500">
                                                {{ $task->due_at->format('Y/m/d') }}
                                            </div>
                                        @endif

                                        <flux:avatar.group>
                                            @foreach($task->users as $user)
                                                 <flux:avatar circle size="xs" src="{{ $user->getAvatarUrl() }}" :name="$user->name" :tooltip="$user->name" />
                                            @endforeach
                                        </flux:avatar.group>
                                    </div>

                                    <div wire:sort:ignore>
                                        <flux:dropdown>
                                            <flux:button variant="subtle" icon="ellipsis-vertical" size="xs" />
                                            <flux:menu>
                                                <flux:menu.item icon="pencil"
                                                                wire:click="$dispatch('panels.workspace.review.user.task.edit.assign-data', { id: {{ $task->id }} })">
                                                    {{ __('app.edit_task') }}
                                                </flux:menu.item>
                                                <flux:menu.item icon="chat-bubble-left-right"
                                                                wire:click="$dispatch('panels.workspace.review.user.task.activity.assign-data', { id: {{ $task->id }} })">
                                                    {{ __('app.task.activity.modal_title') }}
                                                </flux:menu.item>
                                                <flux:menu.item icon="trash" variant="danger" wire:click="deleteTask({{ $task->id }})">
                                                    {{ __('app.delete') }}
                                                </flux:menu.item>
                                            </flux:menu>
                                        </flux:dropdown>
                                    </div>
                                </div>
                            </x-slot>
                        </flux:kanban.card>
                    @endforeach
                </flux:kanban.column.cards>

                <flux:kanban.column.footer>
                    <div wire:sort:ignore>
                        <flux:button variant="subtle" icon="plus" size="sm" class="w-full justify-start!"
                                     wire:click="$dispatch('panels.workspace.review.user.task.create.assign-data', { status: '{{ $status }}' })">
                            {{ __('app.create_task') }}
                        </flux:button>
                    </div>
                </flux:kanban.column.footer>
            </flux:kanban.column>
        @endforeach
    </flux:kanban>
</div>
