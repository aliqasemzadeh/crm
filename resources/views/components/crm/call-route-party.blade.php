@props([
    'party' => [],
])

@php
    $tooltipContent = isset($party['tooltip_number']) && $party['tooltip_number'] !== null && $party['tooltip_number'] !== ''
        ? (string) $party['tooltip_number']
        : null;
@endphp

@if (! empty($party['is_internal_user']))
    @if ($tooltipContent !== null)
        <flux:tooltip content="{{ $tooltipContent }}">
            <span class="inline-flex max-w-full min-w-0 cursor-default items-center gap-2 outline-none" tabindex="0">
                @if (! empty($party['avatar_url']))
                    <flux:avatar src="{{ $party['avatar_url'] }}" size="xs" />
                @else
                    <flux:avatar name="{{ $party['avatar_name'] ?? $party['display'] }}" color="auto" size="xs" />
                @endif
                <span dir="ltr" class="truncate font-medium text-zinc-800 dark:text-zinc-200">{{ $party['display'] }}</span>
            </span>
        </flux:tooltip>
    @else
        <span class="inline-flex max-w-full min-w-0 items-center gap-2">
            @if (! empty($party['avatar_url']))
                <flux:avatar src="{{ $party['avatar_url'] }}" size="xs" />
            @else
                <flux:avatar name="{{ $party['avatar_name'] ?? $party['display'] }}" color="auto" size="xs" />
            @endif
            <span dir="ltr" class="truncate font-medium text-zinc-800 dark:text-zinc-200">{{ $party['display'] }}</span>
        </span>
    @endif
@else
    @if ($tooltipContent !== null)
        <flux:tooltip content="{{ $tooltipContent }}">
            <span dir="ltr" class="cursor-default font-medium text-zinc-800 outline-none dark:text-zinc-200" tabindex="0">{{ $party['display'] }}</span>
        </flux:tooltip>
    @else
        <span dir="ltr" class="font-medium text-zinc-800 dark:text-zinc-200">{{ $party['display'] }}</span>
    @endif
@endif
