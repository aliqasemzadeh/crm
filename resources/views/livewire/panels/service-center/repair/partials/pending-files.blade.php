@if (count($devicePhotos) || count($attachments))
    <div class="space-y-3">
        @if (count($devicePhotos))
            <div>
                <flux:text class="mb-2 text-sm font-medium">{{ __('app.repair_device_photos') }}</flux:text>
                <div class="flex flex-wrap gap-2">
                    @foreach ($devicePhotos as $index => $photo)
                        <div wire:key="pending-device-photo-{{ $index }}" class="relative size-20 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                            <img src="{{ $photo->temporaryUrl() }}" alt="" class="size-full object-cover">
                            <button
                                type="button"
                                class="absolute end-1 top-1 rounded-full bg-zinc-900/70 p-0.5 text-white"
                                wire:click="removePendingDevicePhoto({{ $index }})"
                            >
                                <flux:icon name="x-mark" variant="micro" />
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if (count($attachments))
            <div>
                <flux:text class="mb-2 text-sm font-medium">{{ __('app.repair_attachments') }}</flux:text>
                <div class="flex flex-wrap gap-2">
                    @foreach ($attachments as $index => $file)
                        <div
                            wire:key="pending-attachment-{{ $index }}"
                            class="flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-2 py-1.5 text-xs dark:border-zinc-700 dark:bg-zinc-800"
                        >
                            @if (str_starts_with($file->getMimeType() ?? '', 'image/'))
                                <img src="{{ $file->temporaryUrl() }}" alt="" class="size-8 rounded object-cover">
                            @else
                                <flux:icon name="document" variant="micro" />
                            @endif
                            <span class="max-w-28 truncate">{{ $file->getClientOriginalName() }}</span>
                            <button type="button" wire:click="removePendingAttachment({{ $index }})" class="text-zinc-500 hover:text-red-500">
                                <flux:icon name="x-mark" variant="micro" />
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endif

<flux:error name="devicePhotos.*" />
<flux:error name="attachments.*" />
