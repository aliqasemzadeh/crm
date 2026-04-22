<div class="flex flex-col items-center gap-2">
    @if($hasImage)
        <img src="{{ route('item.image', $itemId) }}?v={{ time() }}" class="w-16 h-16 object-cover rounded shadow-sm" alt="Item Image">
    @else
        <div class="w-16 h-16 bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center rounded border border-zinc-300 dark:border-zinc-700">
            <flux:icon icon="photo" class="text-zinc-400" />
        </div>
    @endif
</div>
