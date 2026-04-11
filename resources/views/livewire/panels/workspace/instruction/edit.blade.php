<div>
    <flux:main>
        <div class="mb-6 flex justify-between items-center">
            <flux:heading size="xl">{{ __('app.edit_instruction') }}</flux:heading>
            <flux:button href="{{ route('panels.workspace.instruction.index') }}" variant="ghost" icon="chevron-left" wire:navigate>{{ __('app.back') }}</flux:button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <form wire:submit="save" class="p-6 bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 space-y-6">
                    <flux:input wire:model="title" label="{{ __('app.title') }}" />

                    <flux:textarea wire:model="body" label="{{ __('app.body') }}" rows="15" />

                    <flux:select wire:model="status" label="{{ __('app.status') }}">
                        <flux:select.option value="draft">{{ __('app.instruction_status.draft') }}</flux:select.option>
                        <flux:select.option value="finalized">{{ __('app.instruction_status.finalized') }}</flux:select.option>
                    </flux:select>

                    <div class="flex gap-2">
                        <flux:button type="submit" variant="primary">{{ __('app.save_changes') }}</flux:button>
                    </div>
                </form>
            </div>

            <div class="space-y-6">
                <div class="p-6 bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 space-y-4">
                    <flux:heading size="lg">{{ __('app.files') }}</flux:heading>

                    <form wire:submit="uploadFiles" class="space-y-4">
                        <flux:input type="file" wire:model="files" multiple label="{{ __('app.select_files') }}" />
                        <flux:input wire:model="file_description" label="{{ __('app.file_description') }}" />
                        <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled">
                            <span wire:loading.remove>{{ __('app.upload') }}</span>
                            <span wire:loading>{{ __('app.uploading') }}</span>
                        </flux:button>
                    </form>

                    <hr class="border-zinc-200 dark:border-zinc-700" />

                    <div class="space-y-2">
                        @foreach($instruction->files as $file)
                            <div class="flex items-center justify-between p-2 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-100 dark:border-zinc-800">
                                <div class="flex flex-col overflow-hidden">
                                    @php
                                        $canAccess = $instruction->status === 'finalized' || $file->user_id === auth()->id();
                                    @endphp
                                    @if($canAccess)
                                        <a href="{{ Storage::url($file->file_path) }}" target="_blank" class="text-sm font-medium text-blue-600 dark:text-blue-400 truncate hover:underline">
                                            {{ $file->file_name }}
                                        </a>
                                    @else
                                        <span class="text-sm font-medium text-zinc-400 truncate cursor-not-allowed" title="{{ __('app.only_finalized_accessible') }}">
                                            {{ $file->file_name }}
                                        </span>
                                    @endif
                                    @if($file->file_description)
                                        <span class="text-xs text-zinc-500 truncate">{{ $file->file_description }}</span>
                                    @endif
                                </div>
                                <div class="flex gap-1">
                                    @if($file->user_id === auth()->id())
                                        <flux:button wire:click="deleteFile({{ $file->id }})" wire:confirm="{{ __('app.are_you_sure') }}" icon="trash" variant="ghost" size="xs" color="red" />
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </flux:main>
</div>
