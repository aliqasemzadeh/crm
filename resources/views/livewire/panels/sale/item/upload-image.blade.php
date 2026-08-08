<flux:modal name="panels.sale.item.upload-image.modal" class="md:max-w-lg w-full" flyout position="right">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.warehouse_item_upload_image_modal_title') }}</flux:heading>
            <flux:text class="mt-2">{{ $item_title }}</flux:text>
            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('app.warehouse_item_upload_image_modal_description') }}
            </flux:text>
        </div>

        <form wire:submit="store" class="space-y-4">
            <flux:field>
                <flux:file-upload wire:model="photos" multiple label="{{ __('app.upload_files') }}">
                    <flux:file-upload.dropzone
                        heading="{{ __('app.drop_files_or_click_to_browse') }}"
                        text="{{ __('app.file_upload_limit_text') }}"
                        inline
                    />
                </flux:file-upload>
                <flux:error name="photos" />
                <flux:error name="photos.*" />

                <div class="mt-3 flex flex-col gap-2">
                    @foreach ($photos as $uploadedFile)
                        @if ($uploadedFile->isPreviewable())
                            <flux:file-item
                                :heading="$uploadedFile->getClientOriginalName()"
                                :image="$uploadedFile->temporaryUrl()"
                                :size="$uploadedFile->getSize()"
                                wire:key="wh-item-photo-{{ $uploadedFile->getFilename() }}"
                            >
                                <x-slot name="actions">
                                    <flux:file-item.remove wire:click="removeUpload('photos', '{{ $uploadedFile->getFilename() }}')" />
                                </x-slot>
                            </flux:file-item>
                        @else
                            <flux:file-item
                                :heading="$uploadedFile->getClientOriginalName()"
                                :size="$uploadedFile->getSize()"
                                wire:key="wh-item-photo-{{ $uploadedFile->getFilename() }}"
                            >
                                <x-slot name="actions">
                                    <flux:file-item.remove wire:click="removeUpload('photos', '{{ $uploadedFile->getFilename() }}')" />
                                </x-slot>
                            </flux:file-item>
                        @endif
                    @endforeach
                </div>
            </flux:field>

            <flux:button
                type="submit"
                class="w-full"
                variant="primary"
                color="orange"
                wire:loading.attr="disabled"
            >
                {{ __('app.save') }}
            </flux:button>
        </form>
    </div>
</flux:modal>
