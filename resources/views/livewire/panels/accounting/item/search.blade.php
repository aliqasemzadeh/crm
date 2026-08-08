<div
    class="relative w-full max-w-2xl"
    x-data="{ open: @entangle('open').live }"
    @keydown.escape.window="open = false; $wire.close()"
    @click.outside="open = false; $wire.close()"
>
    <flux:input
        wire:model.live.debounce.300ms="search"
        icon="magnifying-glass"
        :placeholder="__('app.item_search_placeholder')"
        class="w-full"
        autocomplete="off"
        x-on:focus="if ($wire.search.trim().length >= 2) open = true"
    />

    <div
        wire:loading.flex
        wire:target="search"
        class="absolute inset-x-0 top-full z-50 mt-1 hidden items-center justify-center rounded-lg border border-zinc-200 bg-white p-3 shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
    >
        <flux:icon name="loader-circle" class="size-5 animate-spin text-zinc-400" />
    </div>

    <div
        x-show="open"
        x-cloak
        x-transition
        wire:loading.remove
        wire:target="search"
        class="absolute inset-x-0 top-full z-50 mt-1 max-h-[70vh] overflow-y-auto rounded-lg border border-zinc-200 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-900"
    >
        @if (strlen(trim($search)) >= 2 && $this->results->isEmpty())
            <div class="p-4 text-center text-sm text-zinc-500">
                {{ __('app.no_item_found') }}
            </div>
        @endif

        <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @foreach ($this->results as $result)
                <li>
                    <a
                        href="{{ $result['url'] }}"
                        wire:navigate
                        wire:click="$set('open', false)"
                        class="flex gap-3 p-3 transition hover:bg-zinc-50 dark:hover:bg-zinc-800/80"
                    >
                        <div class="shrink-0">
                            @if ($result['thumbnail'])
                                <img
                                    src="data:image/jpeg;base64,{{ base64_encode($result['thumbnail']) }}"
                                    alt="{{ $result['title'] }}"
                                    class="size-14 rounded-md object-cover shadow-sm"
                                >
                            @else
                                <div class="flex size-14 items-center justify-center rounded-md bg-zinc-100 dark:bg-zinc-800">
                                    <flux:icon name="package" class="size-6 text-zinc-400" />
                                </div>
                            @endif
                        </div>

                        <div class="min-w-0 flex-1 space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="truncate font-medium text-zinc-900 dark:text-zinc-100">{{ $result['title'] }}</span>
                                <span class="text-xs text-zinc-500">{{ $result['code'] }}</span>
                            </div>

                            @if ($result['main_grouping'])
                                <flux:badge size="sm" color="zinc" class="max-w-full truncate">
                                    {{ $result['main_grouping'] }}
                                </flux:badge>
                            @endif

                            <div class="grid grid-cols-2 gap-x-3 gap-y-1 text-xs sm:grid-cols-4">
                                <div>
                                    <span class="text-zinc-500">{{ __('app.stock') }}:</span>
                                    <span class="font-semibold text-teal-600 dark:text-teal-400">{{ number_format($result['stock']) }}</span>
                                </div>
                                <div>
                                    <span class="text-zinc-500">{{ __('app.last_sale_price') }}:</span>
                                    <span class="font-semibold text-sky-600 dark:text-sky-400">{{ number_format($result['last_sale_price']) }}</span>
                                </div>
                                <div>
                                    <span class="text-zinc-500">{{ __('app.last_purchase_price') }}:</span>
                                    <span class="font-semibold text-amber-600 dark:text-amber-400">{{ number_format($result['last_purchase_price']) }}</span>
                                </div>
                                <div>
                                    <span class="text-zinc-500">{{ __('app.site_price') }}:</span>
                                    <span class="font-semibold text-rose-600 dark:text-rose-400">
                                        {{ $result['site_price'] !== null ? number_format($result['site_price']) : '—' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        @if ($result['site_url'])
                            <div class="flex shrink-0 items-start" @click.stop>
                                <flux:tooltip content="{{ __('app.website') }}">
                                    <flux:button
                                        size="xs"
                                        variant="filled"
                                        color="rose"
                                        icon="external-link"
                                        href="{{ $result['site_url'] }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    />
                                </flux:tooltip>
                            </div>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</div>
