<flux:modal name="instruction-files-modal" class="md:w-[40rem]" flyout position="right">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.instruction_files') }}</flux:heading>
        </div>

        @if($instruction)
            @php
                $canManage = $instruction->user_id === auth()->id() || auth()->user()->can('administrator_workspace_instruction_manage');
            @endphp

            @if($canManage)
                <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 space-y-4">
                    <form wire:submit="uploadFiles" class="space-y-4">
                        <flux:input type="file" wire:model="files" multiple label="{{ __('app.select_files') }}" />
                        <flux:input wire:model="file_description" label="{{ __('app.file_description') }}" />
                        <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled">
                            <span wire:loading.remove>{{ __('app.upload') }}</span>
                            <span wire:loading>{{ __('app.uploading') }}</span>
                        </flux:button>
                    </form>
                </div>
                <hr class="border-zinc-200 dark:border-zinc-700" />
            @endif

            <div class="space-y-2 max-h-[30rem] overflow-y-auto">
                @forelse($instruction->files as $file)
                    <div class="flex items-center justify-between p-3 bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <div class="flex flex-col overflow-hidden">
                            @php
                                $canAccess = $instruction->status === 'finalized' || $file->user_id === auth()->id() || auth()->user()->can('administrator_workspace_instruction_manage');
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
                            <div class="text-[10px] text-zinc-400 flex gap-2">
                                <span>{{ $file->user->name }}</span>
                                <span>{{ \Morilog\Jalali\Jalalian::fromDateTime($file->created_at)->format('Y/m/d H:i') }}</span>
                            </div>
                        </div>
                        <div class="flex gap-1">
                            @if($file->user_id === auth()->id() || auth()->user()->can('administrator_workspace_instruction_manage'))
                                <flux:button wire:click="deleteFile({{ $file->id }})" wire:confirm="{{ __('app.are_you_sure') }}" icon="trash" variant="ghost" size="xs" color="red" />
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-zinc-500">
                        {{ __('app.no_files_found') }}
                    </div>
                @endforelse
            </div>
        @endif
    </div>
</flux:modal>
