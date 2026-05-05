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
    <livewire:panels.workspace.review.user.task.assign />

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
                            <x-workspace.task.card :task="$task" event-prefix="panels.workspace.review.user.task" :show-checklist-action="true" />

                            @if($task->checklists->isNotEmpty())
                                <livewire:panels.workspace.review.user.task.checklist :task="$task" :key="'checklist-'.$task->id" />
                            @endif
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
