@props([
    'task',
    'eventPrefix' => null,
    'showDragHandle' => true,
    'showDescription' => true,
    'showStatusBadge' => false,
    'showEditAction' => true,
    'showAssignAction' => true,
    'showChecklistAction' => true,
    'showActivityAction' => true,
    'showDeleteAction' => true,
    'deleteAction' => 'deleteTask',
])

@php
    $priorityColor = match($task->priority) {
        'low' => 'blue',
        'medium' => 'yellow',
        'high' => 'orange',
        'urgent' => 'red',
        default => 'zinc',
    };

    $activityEvent = $eventPrefix ? $eventPrefix . '.activity.assign-data' : null;
    $checklists = $task->relationLoaded('checklists') ? $task->checklists : collect();
    $checklistTotal = $checklists->count();
    $checklistDone = $checklists->where('is_done', true)->count();
    $checklistProgress = $checklistTotal > 0 ? (int) round(($checklistDone / $checklistTotal) * 100) : 0;
@endphp

<div class="space-y-2">
    <div class="flex items-center gap-2">
        @if($showDragHandle)
            <div class="cursor-grab">
                <flux:icon name="bars-3" variant="micro" class="text-zinc-400" />
            </div>
        @endif

        <flux:badge :color="$priorityColor" size="sm">
            {{ __('app.task.priorities.' . $task->priority) }}
        </flux:badge>

        @if($showStatusBadge)
            <flux:badge size="sm" variant="outline" :color="match($task->status) {
                'planning' => 'zinc',
                'doing' => 'yellow',
                'done' => 'green',
                default => 'zinc',
            }">
                {{ __('app.task.statuses.' . $task->status) }}
            </flux:badge>
        @endif

        @if($task->status === 'done')
            @if($task->approval_status === 'pending')
                <flux:badge color="yellow" size="sm">{{ __('app.pending') }}</flux:badge>
            @elseif($task->approval_status === 'rejected')
                <flux:dropdown hover position="bottom" align="start" offset="-16" gap="10">
                    <button type="button">
                        <flux:badge color="red" size="sm" class="cursor-help">{{ __('app.rejected') }}</flux:badge>
                    </button>
                    <flux:popover class="max-w-xs p-4">
                        <div class="space-y-2">
                            <flux:heading size="sm">{{ __('app.rejection_reason') }}</flux:heading>
                            <flux:text size="sm">{{ $task->review_note ?: __('app.no_reason_provided') }}</flux:text>
                        </div>
                    </flux:popover>
                </flux:dropdown>
            @elseif($task->approval_status === 'approved')
                <flux:badge color="green" size="sm">{{ __('app.task.review.status_approved') }}</flux:badge>
            @endif
        @elseif($task->approval_status === 'rejected')
            <flux:dropdown hover position="bottom" align="start" offset="-16" gap="10">
                <button type="button">
                    <flux:badge color="red" size="sm" class="cursor-help">{{ __('app.rejected') }}</flux:badge>
                </button>
                <flux:popover class="max-w-xs p-4">
                    <div class="space-y-2">
                        <flux:heading size="sm">{{ __('app.rejection_reason') }}</flux:heading>
                        <flux:text size="sm">{{ $task->review_note ?: __('app.no_reason_provided') }}</flux:text>
                    </div>
                </flux:popover>
            </flux:dropdown>
        @endif
    </div>

    <div class="text-sm text-zinc-600 dark:text-zinc-400">
        <div wire:sort:ignore>
            <div
                class="flex items-center gap-1 font-medium {{ $activityEvent ? 'cursor-pointer' : '' }}"
                @if($activityEvent) wire:click="$dispatch('{{ $activityEvent }}', { id: {{ $task->id }} })" @endif
            >
                @if($task->is_locked)
                    <flux:icon name="lock-closed" variant="micro" class="text-rose-500" />
                @endif
                {{ $task->title }}
            </div>

            @if($showDescription && filled($task->description))
                <div class="text-xs mt-1">{{ \Illuminate\Support\Str::limit($task->description, 80) }}</div>
            @endif
        </div>
    </div>

    <div class="flex justify-between items-center w-full">
        <div class="flex items-center gap-2">
            @if($task->due_at)
                <div class="flex items-center gap-1">
                    <flux:icon name="calendar" variant="micro" class="text-zinc-400" />
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">
                        {{ \Morilog\Jalali\Jalalian::fromDateTime($task->due_at)->format('Y/m/d') }}
                    </span>
                    @if($task->is_locked)
                        <flux:tooltip :content="__('app.task.is_locked')">
                            <flux:icon name="lock-closed" variant="micro" class="text-rose-500" />
                        </flux:tooltip>
                    @endif
                </div>
            @endif

            @if($checklistTotal > 0)
                <div
                    class="flex items-center gap-1 cursor-pointer"
                    @if($activityEvent) wire:click="$dispatch('{{ $eventPrefix }}.activity.checklist.assign-data', { id: {{ $task->id }} })" @endif
                >
                    <flux:icon name="list-checks" variant="micro" class="text-zinc-400" />
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $checklistTotal }}</span>
                    <flux:badge color="lime" size="sm">New</flux:badge>
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $checklistProgress }}%</span>
                </div>
            @endif

            <flux:avatar.group>
                @foreach($task->users as $user)
                    <flux:avatar circle size="xs" src="{{ $user->getAvatarUrl() }}" :name="$user->name" :tooltip="$user->name" />
                @endforeach
            </flux:avatar.group>
        </div>

        <div class="flex items-center gap-2" wire:sort:ignore>
            {{ $slot }}

            @if($eventPrefix && ($showEditAction || $showAssignAction || $showChecklistAction || $showActivityAction || $showDeleteAction))
                <flux:dropdown>
                    <flux:button variant="subtle" icon="ellipsis-vertical" size="xs" />
                    <flux:menu>
                        @if($showEditAction && $task->approval_status !== 'approved')
                            <flux:menu.item icon="pencil" wire:click="$dispatch('{{ $eventPrefix }}.edit.assign-data', { id: {{ $task->id }} })">
                                {{ __('app.edit_task') }}
                            </flux:menu.item>
                        @endif

                        @if($showAssignAction && $task->approval_status !== 'approved')
                            <flux:menu.item icon="user-plus" wire:click="$dispatch('{{ $eventPrefix }}.assign.assign-data', { id: {{ $task->id }} })">
                                {{ __('app.task.assign_users') }}
                            </flux:menu.item>
                        @endif

                        @if($showChecklistAction && $task->approval_status !== 'approved')
                            <flux:menu.item icon="list-checks" wire:click="$dispatch('{{ $eventPrefix }}.activity.checklist.assign-data', { id: {{ $task->id }} })">
                                {{ __('app.task.checklist') }}
                            </flux:menu.item>
                        @endif

                        @if($showActivityAction)
                            <flux:menu.item icon="chat-bubble-left-right" wire:click="$dispatch('{{ $eventPrefix }}.activity.assign-data', { id: {{ $task->id }} })">
                                {{ __('app.task.activity.modal_title') }}
                            </flux:menu.item>
                        @endif

                        @if($showDeleteAction && $task->status !== 'done' && $task->approval_status !== 'approved')
                            <flux:menu.item icon="trash" variant="danger" wire:click="{{ $deleteAction }}({{ $task->id }})">
                                {{ __('app.delete') }}
                            </flux:menu.item>
                        @endif
                    </flux:menu>
                </flux:dropdown>
            @endif
        </div>
    </div>
</div>
