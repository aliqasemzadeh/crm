<div>
    <livewire:panels.workspace.dashboard.task.create />
    <livewire:panels.workspace.dashboard.task.edit />
    <livewire:panels.workspace.dashboard.task.activity />

    @php
        // یکبار خواندن برای جلوگیری از N+1 و تکرار filter
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
                <flux:kanban.column.header
                    :heading="__('app.statuses.'.$status)"
                    :count="$colTasks->count()"
                />

                <flux:kanban.column.cards
                    wire:sort="{{ $handler }}"
                    wire:sort:group="tasks"
                    wire:key="col-{{ $status }}"
                >
                    @foreach ($colTasks as $task)
                        <flux:kanban.card wire:sort:item="{{ $task->id }}" wire:sort:handle wire:key="task-{{ $task->id }}">
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    {{-- Drag handle --}}
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

                                    {{-- Done: approval badge --}}
                                    @if($status === 'done')
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
                                    @else
                                        @if($task->approval_status === 'rejected')
                                            <flux:dropdown hover position="bottom" align="start" offset="-16" gap="10">
                                                <button  type="button">
                                                <flux:badge color="red" size="sm" class="cursor-help">
                                                    {{ __('app.rejected') }}
                                                </flux:badge>
                                                </button>

                                                <flux:popover class="max-w-xs p-4">
                                                    <div class="space-y-2">
                                                        <flux:heading size="sm">{{ __('app.rejection_reason') }}</flux:heading>
                                                        <flux:text size="sm">
                                                            {{ $task->review_note ?: __('app.no_reason_provided') }}
                                                        </flux:text>
                                                    </div>
                                                </flux:popover>
                                            </flux:dropdown>
                                        @endif
                                    @endif
                                </div>

                                <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{-- clickable area must not start drag --}}
                                    <div wire:sort:ignore>
                                        <div
                                            class="font-medium @if($task->approval_status !== 'approved') cursor-pointer @endif"
                                            wire:click="$dispatch('panels.workspace.dashboard.task.activity.assign-data', { id: {{ $task->id }} })"
                                        >
                                            {{ $task->title }}
                                        </div>

                                        @if(!empty($task->description))
                                            <div class="text-xs mt-1">
                                                {{ \Illuminate\Support\Str::limit($task->description, 70) }}
                                            </div>
                                        @endif
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

                                    {{-- dropdown must not start drag --}}
                                    <div wire:sort:ignore>
                                        <flux:dropdown>
                                            <flux:button variant="subtle" icon="ellipsis-vertical" size="xs" />
                                            <flux:menu>
                                                @if($task->approval_status !== 'approved')
                                                    <flux:menu.item
                                                        icon="pencil"
                                                        wire:click="$dispatch('panels.workspace.dashboard.task.edit.assign-data', { id: {{ $task->id }} })"
                                                    >
                                                        {{ __('app.edit_task') }}
                                                    </flux:menu.item>
                                                @endif

                                                <flux:menu.item
                                                    icon="chat-bubble-left-right"
                                                    wire:click="$dispatch('panels.workspace.dashboard.task.activity.assign-data', { id: {{ $task->id }} })"
                                                >
                                                    {{ __('app.task.activity.modal_title') }}
                                                </flux:menu.item>

                                                @if($task->status !== 'done' && $task->approval_status !== 'approved')
                                                    <flux:menu.item
                                                        icon="trash"
                                                        variant="danger"
                                                        wire:click="deleteTask({{ $task->id }})"
                                                    >
                                                        {{ __('app.delete') }}
                                                    </flux:menu.item>
                                                @endif
                                            </flux:menu>
                                        </flux:dropdown>
                                    </div>
                                </div>
                            </x-slot>
                        </flux:kanban.card>
                    @endforeach
                </flux:kanban.column.cards>

                <flux:kanban.column.footer>
                    {{-- اگر می‌خواهی create داخل همان ستون status پیش‌فرض بگیرد --}}
                    <div wire:sort:ignore>
                        <flux:button
                            variant="subtle"
                            icon="plus"
                            size="sm"
                            class="w-full justify-start!"
                            wire:click="$dispatch('panels.workspace.dashboard.task.create.assign-data', { status: '{{ $status }}' })"
                        >
                            {{ __('app.create_task') }}
                        </flux:button>
                    </div>
                </flux:kanban.column.footer>
            </flux:kanban.column>
        @endforeach
    </flux:kanban>
</div>
