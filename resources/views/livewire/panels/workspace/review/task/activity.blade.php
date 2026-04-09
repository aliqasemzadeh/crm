<flux:modal name="panels.workspace.review.task.activity.modal" class="md:w-1/3">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.task.activity.modal_title') }}</flux:heading>
            @if($task)
                <flux:subheading>{{ $task->title }}</flux:subheading>
            @endif
        </div>
        <div>
            {{ $task->description }}
        </div>

        <div class="space-y-4 max-h-[60vh] overflow-y-auto px-1">
            @if($task && $task->reports->count() > 0)
                @foreach($task->reports as $report)
                    <div class="p-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <div class="flex justify-between items-start mb-2">
                            <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $report->user?->name }}</span>
                            <span class="text-xs text-zinc-500">{{ $report->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-sm text-zinc-700 dark:text-zinc-300 whitespace-pre-line">{{ $report->body }}</p>

                        @if($report->files->count() > 0)
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach($report->files as $file)
                                    <a href="{{ asset('storage/' . $file->path) }}" target="_blank" class="flex items-center gap-1 text-xs text-blue-600 hover:underline">
                                        <flux:icon name="paper-clip" variant="micro" />
                                        {{ $file->original_name }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            @else
                <div class="text-center py-4 text-zinc-500 text-sm">
                    {{ __('app.task.activity.no_activities') }}
                </div>
            @endif
        </div>

        @if($task && !($task->status === 'done' && $task->approval_status === 'approved'))
            <form wire:submit="send" class="space-y-4">
                <flux:composer wire:model="body" placeholder="{{ __('app.task.activity.write_report') }}">
                    <x-slot name="actionsLeading">
                        <input type="file" wire:model="files" multiple class="hidden" id="review-activity-files">
                        <flux:button size="sm" variant="subtle" icon="paper-clip" onclick="document.getElementById('review-activity-files').click()" />
                    </x-slot>

                    <x-slot name="actionsTrailing">
                        <flux:button type="submit" size="sm" variant="primary" icon="paper-airplane" />
                    </x-slot>
                </flux:composer>

                @if($files)
                    <div class="flex flex-wrap gap-2">
                        @foreach($files as $file)
                            <div class="text-xs bg-zinc-100 dark:bg-zinc-800 px-2 py-1 rounded flex items-center gap-1">
                                <flux:icon name="document" variant="micro" />
                                {{ $file->getClientOriginalName() }}
                            </div>
                        @endforeach
                    </div>
                @endif

                <flux:error name="body" />
                <flux:error name="files.*" />
            </form>
        @elseif($task)
            <div class="p-4 bg-zinc-100 dark:bg-zinc-800/50 rounded-lg border border-zinc-200 dark:border-zinc-700 text-center text-sm text-zinc-500">
                <flux:icon name="lock-closed" variant="micro" class="inline-block mr-1" />
                {{ __('app.task.activity.task_is_locked') }}
            </div>
        @endif
        @if($task && $task->approval_status !== 'approved')
            <div class="space-y-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:textarea wire:model="review_note" :label="__('app.task.review.review_note')" :placeholder="__('app.task.review.review_note_placeholder')" />
                <div class="flex gap-2">
                    <flux:button wire:click="approve" variant="primary" color="green" class="flex-1" icon="check">{{ __('app.task.review.approve') }}</flux:button>
                    <flux:button wire:click="reject" variant="primary" color="red" class="flex-1" icon="x-mark">{{ __('app.task.review.reject') }}</flux:button>
                </div>
                <flux:error name="review_note" />
            </div>
        @endif
    </div>
</flux:modal>
