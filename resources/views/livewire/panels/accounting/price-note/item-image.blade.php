<div class="flex flex-col items-center gap-2">
    @if($hasImage)
        <img src="{{ route('item.image', $itemId) }}?v={{ time() }}" class="w-16 h-16 object-cover rounded shadow-sm" alt="Item Image">
    @else
        <div class="w-16 h-16 bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center rounded border border-dashed border-zinc-300 dark:border-zinc-700">
            <flux:icon icon="photo" class="text-zinc-400" />
        </div>
    @endif

    <label class="cursor-pointer">
        <input type="file" wire:model="image" class="hidden" accept="image/*">
        <flux:button size="xs" variant="subtle" icon="arrow-up-tray" as="span">
            {{ __('app.upload') }}
        </flux:button>
    </label>

    <div wire:loading wire:target="image" class="text-xs text-zinc-500">
        {{ __('app.uploading') }}
    </div>
</div>
