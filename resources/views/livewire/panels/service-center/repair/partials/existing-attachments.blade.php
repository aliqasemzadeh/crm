@props([
    'existing' => null,
])

@if ($existing && $existing->count())
    <div class="space-y-2">
        <flux:heading size="sm">{{ __('app.repair_attachments') }}</flux:heading>
        <div class="flex flex-wrap gap-2">
            @foreach ($existing as $media)
                <div
                    wire:key="existing-attachment-{{ $media->id }}"
                    class="flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-2 py-1.5 text-xs dark:border-zinc-700 dark:bg-zinc-800"
                >
                    @if (str_starts_with($media->mime_type ?? '', 'image/'))
                        <a href="{{ $media->getUrl() }}" target="_blank" class="block">
                            <img src="{{ $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : $media->getUrl() }}" alt="" class="size-8 rounded object-cover">
                        </a>
                    @else
                        <a href="{{ $media->getUrl() }}" target="_blank" class="flex items-center gap-1 text-blue-600 hover:underline">
                            <flux:icon name="document" variant="micro" />
                            <span class="max-w-28 truncate">{{ $media->file_name }}</span>
                        </a>
                    @endif
                    <button
                        type="button"
                        wire:click="removeExistingMedia({{ $media->id }})"
                        wire:confirm="{{ __('common.are_you_sure') }}"
                        class="text-zinc-500 hover:text-red-500"
                    >
                        <flux:icon name="x-mark" variant="micro" />
                    </button>
                </div>
            @endforeach
        </div>
    </div>
@endif
