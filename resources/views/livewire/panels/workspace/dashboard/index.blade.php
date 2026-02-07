<flux:kanban>
    @foreach ($statuses as $status)
        @php
            $handler = match($status) {
                'planning' => 'sortPlanning',
                'doing' => 'sortDoing',
                'done' => 'sortDone',
            };
        @endphp

        <flux:kanban.column>
            <flux:kanban.column.header
                :heading="__('app.statuses.'.$status)"
                :count="$this->tasks->where('status', $status)->count()"
            />

            <flux:kanban.column.cards
                wire:sort="{{ $handler }}"
                wire:sort:group="tasks"
                :key="'col-'.$status"
            >
                @foreach ($this->tasks->where('status', $status)->sortBy('order') as $task)
                    <flux:kanban.card wire:sort:item="{{ $task->id }}" :key="'task-'.$task->id">
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                {{-- ✅ Drag handle (اختیاری) --}}
                                <div wire:sort:handle class="cursor-grab">
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
                            </div>

                            <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                {{-- ✅ این قسمت کلیکی است، پس ignore --}}
                                <div wire:sort:ignore>
                                    <div class="font-medium cursor-pointer"
                                         wire:click="openEditModal({{ $task->id }})">
                                        {{ $task->title }}
                                    </div>
                                    <div class="text-xs mt-1">
                                        {{ \Illuminate\Support\Str::limit($task->description, 50) }}
                                    </div>
                                </div>
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

                                {{-- ✅ dropdown باید ignore شود --}}
                                <div wire:sort:ignore>
                                    <flux:dropdown>
                                        <flux:button variant="subtle" icon="ellipsis-vertical" size="xs" />
                                        <flux:menu>
                                            <flux:menu.item icon="pencil" wire:click="openEditModal({{ $task->id }})">
                                                {{ __('app.edit_task') }}
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
                <flux:button variant="subtle" icon="plus" size="sm" class="w-full justify-start!"
                             wire:click="openCreateModal('{{ $status }}')">
                    {{ __('app.create_task') }}
                </flux:button>
            </flux:kanban.column.footer>
        </flux:kanban.column>
    @endforeach
</flux:kanban>
