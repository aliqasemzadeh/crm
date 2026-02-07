<div>
    <flux:main>
        <flux:kanban>
            @foreach ($statuses as $status)
                <flux:kanban.column>
                    <flux:kanban.column.header :heading="__('app.tasks.statuses.'.$status)" :count="$this->tasks->where('status', $status)->count()" />

                    <flux:kanban.column.cards wire:sort="sort" wire:sort:group="{{ $status }}" :key="'col-'.$status">
                        @foreach ($this->tasks->where('status', $status)->sortBy('order') as $task)
                            <flux:kanban.card wire:sort:item="{{ $task->id }}" :key="'task-'.$task->id">
                                <div class="space-y-2">
                                    <div class="flex items-center gap-2">
                                        @php
                                            $priorityColor = match($task->priority) {
                                                'low' => 'blue',
                                                'medium' => 'yellow',
                                                'high' => 'orange',
                                                'urgent' => 'red',
                                                default => 'zinc'
                                            };
                                        @endphp
                                        <flux:badge :color="$priorityColor" size="sm">{{ __('app.tasks.priorities.'.$task->priority) }}</flux:badge>
                                    </div>
                                    <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                        <div class="font-medium cursor-pointer" wire:click="openEditModal({{ $task->id }})">{{ $task->title }}</div>
                                        <div class="text-xs mt-1">{{ Str::limit($task->description, 50) }}</div>
                                    </div>
                                </div>

                                <x-slot name="footer">
                                    <div class="flex justify-between items-center w-full">
                                         @if($task->due_at)
                                            <div class="flex items-center gap-1">
                                                <flux:icon name="calendar" variant="micro" class="text-zinc-400" />
                                                <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                                    {{ $task->due_at->format('Y/m/d') }}
                                                </span>
                                            </div>
                                         @else
                                            <div></div>
                                         @endif

                                        <flux:dropdown>
                                            <flux:button variant="subtle" icon="ellipsis-vertical" size="xs" />
                                            <flux:menu>
                                                <flux:menu.item icon="pencil" wire:click="openEditModal({{ $task->id }})">{{ __('app.tasks.edit_task') }}</flux:menu.item>
                                                <flux:menu.item icon="trash" variant="danger" wire:click="deleteTask({{ $task->id }})">{{ __('app.delete') }}</flux:menu.item>
                                            </flux:menu>
                                        </flux:dropdown>
                                    </div>
                                </x-slot>
                            </flux:kanban.card>
                        @endforeach
                    </flux:kanban.column.cards>
                    <flux:kanban.column.footer>
                         <flux:button variant="subtle" icon="plus" size="sm" class="w-full justify-start!" wire:click="openCreateModal('{{ $status }}')">{{ __('app.tasks.create_task') }}</flux:button>
                    </flux:kanban.column.footer>
                </flux:kanban.column>
            @endforeach
        </flux:kanban>

        {{-- Create Task Modal --}}
        <flux:modal name="create-task" variant="floating" class="md:w-lg">
            <form wire:submit="saveTask" class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('app.tasks.create_task') }}</flux:heading>
                    <flux:subheading>{{ __('app.tasks.create_task_description') }}</flux:subheading>
                </div>

                <flux:input wire:model="title" label="{{ __('app.tasks.title') }}" />

                <flux:textarea wire:model="description" label="{{ __('app.tasks.description') }}" />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select wire:model="status" label="{{ __('app.tasks.status') }}">
                        @foreach($this->statusOptions() as $val => $label)
                            <flux:select.option value="{{ $val }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="priority" label="{{ __('app.tasks.priority') }}">
                        <flux:select.option value="low"><flux:badge color="blue" size="sm" inset="top bottom">{{ __('app.tasks.priorities.low') }}</flux:badge></flux:select.option>
                        <flux:select.option value="medium"><flux:badge color="yellow" size="sm" inset="top bottom">{{ __('app.tasks.priorities.medium') }}</flux:badge></flux:select.option>
                        <flux:select.option value="high"><flux:badge color="orange" size="sm" inset="top bottom">{{ __('app.tasks.priorities.high') }}</flux:badge></flux:select.option>
                        <flux:select.option value="urgent"><flux:badge color="red" size="sm" inset="top bottom">{{ __('app.tasks.priorities.urgent') }}</flux:badge></flux:select.option>
                    </flux:select>
                </div>

                <flux:input type="date" wire:model="due_at" label="{{ __('app.tasks.due_at') }}" />

                <div class="flex items-center justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="filled">{{ __('app.tasks.cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">{{ __('app.tasks.save') }}</flux:button>
                </div>
            </form>
        </flux:modal>

        {{-- Edit Task Modal --}}
        <flux:modal name="edit-task" variant="floating" class="md:w-lg">
            <form wire:submit="saveTask" class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('app.tasks.edit_task') }}</flux:heading>
                    <flux:subheading>{{ __('app.tasks.edit_task_description') }}</flux:subheading>
                </div>

                <flux:input wire:model="title" label="{{ __('app.tasks.title') }}" />

                <flux:textarea wire:model="description" label="{{ __('app.tasks.description') }}" />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select wire:model="status" label="{{ __('app.tasks.status') }}">
                        @foreach($this->statusOptions() as $val => $label)
                            <flux:select.option value="{{ $val }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="priority" label="{{ __('app.tasks.priority') }}">
                        <flux:select.option value="low"><flux:badge color="blue" size="sm" inset="top bottom">{{ __('app.tasks.priorities.low') }}</flux:badge></flux:select.option>
                        <flux:select.option value="medium"><flux:badge color="yellow" size="sm" inset="top bottom">{{ __('app.tasks.priorities.medium') }}</flux:badge></flux:select.option>
                        <flux:select.option value="high"><flux:badge color="orange" size="sm" inset="top bottom">{{ __('app.tasks.priorities.high') }}</flux:badge></flux:select.option>
                        <flux:select.option value="urgent"><flux:badge color="red" size="sm" inset="top bottom">{{ __('app.tasks.priorities.urgent') }}</flux:badge></flux:select.option>
                    </flux:select>
                </div>

                <flux:input type="date" wire:model="due_at" label="{{ __('app.tasks.due_at') }}" />

                <div class="flex items-center justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="filled">{{ __('app.tasks.cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">{{ __('app.tasks.save') }}</flux:button>
                </div>
            </form>
        </flux:modal>
    </flux:main>
</div>
