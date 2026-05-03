@props([
    'party' => [],
])

@if (! empty($party['is_two_digit_party']))
    <span class="inline-flex max-w-full min-w-0 items-center gap-2">
        @if (! empty($party['has_avatar']) && ! empty($party['avatar_url']))
            <flux:avatar
                src="{{ $party['avatar_url'] }}"
                size="xs"
                class="size-8 shrink-0 rounded-lg [&_[data-slot]]:rounded-lg [&_img]:rounded-lg"
            />
        @else
            <flux:avatar
                name="{{ $party['avatar_name'] }}"
                color="auto"
                size="xs"
                class="size-8 shrink-0 rounded-lg [&_[data-slot]]:rounded-lg"
            />
        @endif
        <span dir="ltr" class="truncate font-medium text-zinc-800 dark:text-zinc-200">{{ $party['display'] }}</span>
    </span>
@else
    <span dir="ltr" class="font-medium text-zinc-800 dark:text-zinc-200">{{ $party['display'] }}</span>
@endif
