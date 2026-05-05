@props([
    'checklistItems' => [],
])

<div class="space-y-3">
    <div class="flex items-center justify-between">
        <flux:heading size="sm">{{ __('app.task.checklist') }}</flux:heading>
        <flux:button type="button" size="xs" variant="primary" color="teal" icon="plus" wire:click="addChecklistItem">
            {{ __('app.task.checklist_add') }}
        </flux:button>
    </div>

    <div class="space-y-2" wire:sort="sortChecklist">
        @forelse($checklistItems as $index => $item)
            <div wire:key="checklist-{{ $item['id'] }}" wire:sort:item="{{ $item['id'] }}" class="flex items-center gap-2 rounded-lg border border-zinc-200 p-2 dark:border-zinc-700">
                <button type="button" wire:sort:handle class="cursor-grab text-zinc-400 hover:text-zinc-600">
                    <flux:icon name="grip-vertical" variant="micro" />
                </button>

                <flux:input wire:model="checklistItems.{{ $index }}.title" />

                <flux:button
                    type="button"
                    size="xs"
                    variant="ghost"
                    color="red"
                    icon="trash"
                    wire:click="removeChecklistItem('{{ $item['id'] }}')"
                />
            </div>
        @empty
            <div class="rounded-lg border border-dashed border-zinc-300 p-4 text-center text-sm text-zinc-500 dark:border-zinc-700">
                {{ __('app.task.checklist_empty') }}
            </div>
        @endforelse
    </div>
</div>