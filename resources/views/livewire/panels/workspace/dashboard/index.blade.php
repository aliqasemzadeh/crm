<div>
    <livewire:panels.workspace.dashboard.task.create />
    <livewire:panels.workspace.dashboard.task.edit />
    <livewire:panels.workspace.dashboard.task.activity />
    <livewire:panels.workspace.dashboard.task.assign />

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
                            <x-workspace.task.card :task="$task" event-prefix="panels.workspace.dashboard.task" />
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
