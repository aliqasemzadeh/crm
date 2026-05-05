<flux:modal name="panels.workspace.review.task.activity.modal" flyout position="right" class="md:w-1/3">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.task.activity.modal_title') }}</flux:heading>
            @if($task)
                <flux:subheading>{{ $task->title }}</flux:subheading>
            @endif
        </div>

        <div>
            @if($task)
                {{ $task->description ?? "" }}
            @endif
        </div>

        @if($task)
            <x-workspace.task.checklist
                :checklist-items="$checklistItems"
                add-modal-name="panels.workspace.review.task.activity.checklist.create.modal"
            />

            <flux:modal name="panels.workspace.review.task.activity.checklist.create.modal" flyout position="right" class="md:w-[420px]">
                <div class="space-y-5">
                    <div>
                        <flux:heading size="lg">{{ __('app.task.checklist_create_title') }}</flux:heading>
                        <flux:subheading>{{ __('app.task.checklist_create_description') }}</flux:subheading>
                    </div>

                    <flux:input wire:model="newChecklistTitle" :label="__('app.task.checklist_item_title')" />

                    <div class="w-full">
                        <flux:button type="button" variant="primary" color="teal" class="w-full" wire:click="addChecklistItem">
                            {{ __('app.task.save') }}
                        </flux:button>
                    </div>
                </div>
            </flux:modal>
        @endif

        @if($task && $task->checklists->isNotEmpty())
            <div class="space-y-3">
                <flux:checkbox.group :label="__('app.task.checklist')">
                    @foreach($task->checklists as $checklist)
                        <label wire:key="review-activity-checklist-{{ $checklist->id }}" class="flex items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 transition dark:border-zinc-700 {{ $checklist->is_done ? 'bg-emerald-50 dark:bg-emerald-900/20' : 'bg-white dark:bg-zinc-900/30' }}">
                            <flux:checkbox :checked="$checklist->is_done" wire:change="toggleChecklist({{ $checklist->id }})" />
                            <span class="text-sm {{ $checklist->is_done ? 'text-emerald-700 line-through dark:text-emerald-300' : 'text-zinc-700 dark:text-zinc-200' }}">
                                {{ $checklist->title }}
                            </span>
                        </label>
                    @endforeach
                </flux:checkbox.group>
            </div>
        @endif

        <div class="space-y-4 max-h-[60vh] overflow-y-auto px-1">
            @if($task && $task->reports->count() > 0)
                @foreach($task->reports as $report)
                    <div class="p-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex items-center gap-2">
                                @if($report->user?->avatar)
                                    <flux:avatar src="{{ $report->user?->getAvatarUrl() }}" size="xs" />
                                @else
                                    <flux:avatar name="{{ $report->user?->name }}" color="auto" size="xs" />
                                @endif
                                <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $report->user?->name }}</span>
                            </div>
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
