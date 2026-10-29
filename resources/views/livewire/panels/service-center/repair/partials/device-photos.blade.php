@props([
    'existing' => null,
])

<div class="space-y-3">
    <div class="flex items-center justify-between gap-2">
        <flux:heading size="sm">{{ __('app.repair_device_photos') }}</flux:heading>
        <flux:button
            type="button"
            size="xs"
            variant="primary"
            color="teal"
            icon="photo"
            wire:click="$upload('devicePhotos', { accept: 'image/*', multiple: true })"
        >
            {{ __('app.repair_add_device_photos') }}
        </flux:button>
    </div>

    @if ($existing && $existing->count())
        <div class="flex flex-wrap gap-2">
            @foreach ($existing as $media)
                <div wire:key="existing-device-photo-{{ $media->id }}" class="relative size-20 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <img src="{{ $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : $media->getUrl() }}" alt="" class="size-full object-cover">
                    <button
                        type="button"
                        class="absolute end-1 top-1 rounded-full bg-zinc-900/70 p-0.5 text-white"
                        wire:click="removeExistingMedia({{ $media->id }})"
                        wire:confirm="{{ __('common.are_you_sure') }}"
                    >
                        <flux:icon name="x-mark" variant="micro" />
                    </button>
                </div>
            @endforeach
        </div>
    @endif
</div>
