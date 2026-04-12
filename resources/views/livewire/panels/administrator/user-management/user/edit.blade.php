<flux:modal name="panels.administrator.user-management.user.edit.modal" class="md:w-96" flyout position="right">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.edit_user') }} : {{ isset($mobile) ? $mobile : '' }}</flux:heading>
            <flux:text class="mt-2">{{ __('app.edit_user_description') }}</flux:text>
        </div>
        <form wire:submit="edit" method="post">
        <div class="pb-2">
            <div class="flex items-center justify-center mb-6">
                <flux:file-upload wire:model="photo">
                    <!-- Custom avatar uploader -->
                    <div class="
                        relative flex items-center justify-center size-20 rounded-full transition-colors cursor-pointer
                        border border-zinc-200 dark:border-white/10 hover:border-zinc-300 dark:hover:border-white/10
                        bg-zinc-100 hover:bg-zinc-200 dark:bg-white/10 hover:dark:bg-white/15 in-data-dragging:dark:bg-white/15
                    ">
                        <!-- Show the uploaded file if it exists -->
                        @if ($photo)
                            <img src="{{ $photo?->temporaryUrl() }}" class="size-full object-cover rounded-full" />
                        @elseif ($user?->avatar)
                            <img src="{{ $user->getAvatarUrl() }}" class="size-full object-cover rounded-full" />
                        @else
                            <!-- Show the default icon if no file is uploaded -->
                            <flux:icon name="user" variant="solid" class="text-zinc-500 dark:text-zinc-400" />
                        @endif

                        <!-- Corner upload icon -->
                        <div class="absolute bottom-0 right-0 bg-white dark:bg-zinc-800 rounded-full">
                            <flux:icon name="arrow-up-circle" variant="solid" class="text-zinc-500 dark:text-zinc-400" />
                        </div>

                        @if ($photo || $user?->avatar)
                            <div class="absolute -top-2 -right-2">
                                <flux:button icon="x-mark" size="xs" variant="danger" class="rounded-full !size-6" wire:click.prevent="removeAvatar" />
                            </div>
                        @endif
                    </div>
                </flux:file-upload>
            </div>
            <flux:field>
                    <flux:label>{{ __('app.first_name') }}</flux:label>

                    <flux:input wire:model="first_name" type="text" />

                    <flux:error name="first_name" />
            </flux:field>
            <flux:field>
                <flux:label>{{ __('app.last_name') }}</flux:label>

                <flux:input wire:model="last_name" type="text" />

                <flux:error name="last_name" />
            </flux:field>
            <flux:field>
                <flux:label>{{ __('app.mobile') }}</flux:label>

                <flux:input wire:model="mobile" type="text" />

                <flux:error name="mobile" />
            </flux:field>
            <flux:field>
                <flux:label>{{ __('app.email') }}</flux:label>

                <flux:input wire:model="email" type="email" />

                <flux:error name="email" />
            </flux:field>
            <flux:field>
                <flux:label>{{ __('app.password') }}</flux:label>

                <flux:input wire:model="password" type="password" />

                <flux:error name="password" />
            </flux:field>
            <flux:field>
                <flux:label>{{ __('app.password_confirmation') }}</flux:label>

                <flux:input wire:model="password_confirmation" type="password" />

                <flux:error name="password_confirmation" />
            </flux:field>

            <flux:field class="mt-4">
                <flux:label>{{ __('app.signature') }}</flux:label>
                <flux:file-upload wire:model="photos" multiple label="{{ __('app.upload_files') }}">
                    <flux:file-upload.dropzone
                        heading="{{ __('app.drop_files_or_click_to_browse') }}"
                        text="{{ __('app.file_upload_limit_text') }}"
                        inline
                    />
                </flux:file-upload>

                <div class="mt-3 flex flex-col gap-2">
                    @foreach ($photos as $index => $uploadedFile)
                        <flux:file-item :heading="$uploadedFile->getClientOriginalName()">
                            <x-slot name="actions">
                                <flux:file-item.remove wire:click="removeUpload('photos', '{{ $uploadedFile->getFilename() }}')" />
                            </x-slot>
                        </flux:file-item>
                    @endforeach

                    @if ($user?->signature)
                        @foreach (json_decode($user->signature, true) ?? [] as $sigPath)
                            <div class="relative group">
                                <img src="{{ asset('storage/' . $sigPath) }}" class="w-full h-auto rounded border border-zinc-200 dark:border-white/10" />
                                <flux:file-item :heading="basename($sigPath)">
                                    <x-slot name="actions">
                                        <flux:file-item.remove wire:click="removeSignature('{{ $sigPath }}')" />
                                    </x-slot>
                                </flux:file-item>
                            </div>
                        @endforeach
                    @endif
                </div>
            </flux:field>
        </div>
        <flux:button type="submit" class="w-full" variant="primary">
            {{ __('app.update') }}
        </flux:button>
    </form>
    </div>
</flux:modal>
