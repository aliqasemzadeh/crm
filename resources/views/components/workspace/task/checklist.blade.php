@props([
    'checklistItems' => [],
    'addModalName' => 'task-checklist-add-modal',
    'showControls' => true,
])

@php
    $checklistCount = count($checklistItems);
@endphp

<div class="space-y-3">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <flux:heading size="sm">{{ __('app.task.checklist') }}</flux:heading>
            <flux:badge size="sm" variant="outline">{{ $checklistCount }}</flux:badge>
        </div>
        @if($showControls)
            <flux:button type="button" size="xs" variant="primary" color="teal" icon="plus" x-on:click="$flux.modal('{{ $addModalName }}').show()">
                {{ __('app.task.checklist_add') }}
            </flux:button>
        @endif
    </div>

    <div class="space-y-2" @if($showControls) wire:sort="sortChecklist" @endif>
        @forelse($checklistItems as $index => $item)
            @php
                $itemId = data_get($item, 'id');
                $itemTitle = data_get($item, 'title');
            @endphp
            <div wire:key="checklist-{{ $itemId }}" @if($showControls) wire:sort:item="{{ $itemId }}" @endif class="flex items-center gap-2 rounded-lg border border-zinc-200 p-2 dark:border-zinc-700">
                @if($showControls)
                    <button type="button" wire:sort:handle class="cursor-grab text-zinc-400 hover:text-zinc-600">
                        <flux:icon name="grip-vertical" variant="micro" />
                    </button>
                @endif

                <span class="flex-1 truncate text-sm text-zinc-700 dark:text-zinc-200">
                    {{ $itemTitle }}
                </span>

                @if($showControls)
                    <flux:button
                        type="button"
                        size="xs"
                        variant="ghost"
                        color="red"
                        icon="trash"
                        wire:click="removeChecklistItem('{{ $itemId }}')"
                    />
                @endif
            </div>
        @empty
            <div class="rounded-lg border border-dashed border-zinc-300 p-4 text-center text-sm text-zinc-500 dark:border-zinc-700">
                {{ __('app.task.checklist_empty') }}
            </div>
        @endforelse
    </div>
</div>