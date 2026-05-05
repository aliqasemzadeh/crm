<div class="mt-3 space-y-2">
    @foreach($task->checklists as $checklist)
        <label wire:key="board-checklist-{{ $checklist->id }}" class="flex items-center gap-2 rounded border border-zinc-200 px-2 py-1 transition dark:border-zinc-700 {{ $checklist->is_done ? 'bg-emerald-50 dark:bg-emerald-900/20' : 'bg-white dark:bg-zinc-900/30' }}">
            <flux:checkbox size="sm" :checked="$checklist->is_done" wire:change="toggleChecklist({{ $checklist->id }})" />
            <span class="text-xs {{ $checklist->is_done ? 'text-emerald-700 line-through dark:text-emerald-300' : 'text-zinc-700 dark:text-zinc-200' }}">
                {{ $checklist->title }}
            </span>
        </label>
    @endforeach
</div>
