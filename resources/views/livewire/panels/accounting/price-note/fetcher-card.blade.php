<div class="mt-2 space-y-1">
    @foreach($fetchers as $itemFetcher)
        <div class="flex items-center justify-between gap-2 p-1 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center gap-2 overflow-hidden">
                <span class="text-[10px] font-medium text-zinc-500 truncate w-16" title="{{ $itemFetcher->fetcher }}">{{ $itemFetcher->fetcher }}</span>
                <span class="text-xs font-bold text-zinc-700 dark:text-zinc-300">
                    {{ $itemFetcher->price ? number_format($itemFetcher->price) : '-' }}
                </span>
            </div>
            <div class="flex gap-1 shrink-0">
                <flux:button size="xs" variant="ghost" href="{{ $itemFetcher->link }}" target="_blank" class="!p-1">
                    <flux:icon.link class="w-3 h-3" />
                </flux:button>
                <flux:button size="xs" variant="ghost" wire:click="run({{ $itemFetcher->id }})" wire:loading.attr="disabled" class="!p-1">
                    <flux:icon.play class="w-3 h-3 text-green-600" />
                </flux:button>
                <flux:button size="xs" variant="ghost" wire:click="delete({{ $itemFetcher->id }})" wire:confirm="{{ __('app.are_you_sure') }}" class="!p-1">
                    <flux:icon.trash class="w-3 h-3 text-red-600" />
                </flux:button>
            </div>
        </div>
    @endforeach
</div>
