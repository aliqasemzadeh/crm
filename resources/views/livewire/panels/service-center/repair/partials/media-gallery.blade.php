@props([
    'repair',
])

@php
    $devicePhotos = $repair->getMedia('device_photos');
    $attachments = $repair->getMedia('attachments');
@endphp

@if ($devicePhotos->isNotEmpty() || $attachments->isNotEmpty())
    <div class="space-y-4">
        @if ($devicePhotos->isNotEmpty())
            <div class="rounded-md border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="sm" level="3" class="mb-3">{{ __('app.repair_device_photos') }}</flux:heading>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4">
                    @foreach ($devicePhotos as $media)
                        <a href="{{ $media->getUrl() }}" target="_blank" class="block overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                            <img
                                src="{{ $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : $media->getUrl() }}"
                                alt="{{ $media->file_name }}"
                                class="aspect-square w-full object-cover"
                            >
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($attachments->isNotEmpty())
            <div class="rounded-md border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="sm" level="3" class="mb-3">{{ __('app.repair_attachments') }}</flux:heading>
                <div class="flex flex-col gap-2">
                    @foreach ($attachments as $media)
                        <a
                            href="{{ $media->getUrl() }}"
                            target="_blank"
                            class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:bg-zinc-800/80"
                        >
                            @if (str_starts_with($media->mime_type ?? '', 'image/'))
                                <img
                                    src="{{ $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : $media->getUrl() }}"
                                    alt=""
                                    class="size-10 rounded object-cover"
                                >
                            @elseif (str_starts_with($media->mime_type ?? '', 'audio/'))
                                <flux:icon name="microphone" variant="mini" class="text-violet-600" />
                            @else
                                <flux:icon name="document" variant="mini" class="text-zinc-500" />
                            @endif
                            <div class="min-w-0 flex-1">
                                <div class="truncate font-medium">{{ $media->file_name }}</div>
                                <div class="text-xs text-zinc-500">{{ number_format($media->size / 1024, 1) }} KB</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endif
