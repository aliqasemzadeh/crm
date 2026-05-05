@props([
    'modalName',
    'task' => null,
    'submit' => 'send',
    'composerPlaceholder' => __('app.task.activity.write_report'),
    'fileInputId' => 'activity-files',
    'bodyModel' => 'body',
    'filesModel' => 'files',
    'files' => [],
])

<flux:modal :name="$modalName" class="md:w-1/3">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.task.activity.modal_title') }}</flux:heading>
            @if($task)
                <flux:subheading>{{ $task->title }}</flux:subheading>
            @endif
        </div>

        <div>
            @if($task)
                {{ $task->description ?? '' }}
            @endif
        </div>

        <div class="max-h-[60vh] space-y-4 overflow-y-auto px-1">
            @if($task && $task->reports->count() > 0)
                @foreach($task->reports as $report)
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-800/50">
                        <div class="mb-2 flex items-start justify-between">
                            <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $report->user?->name }}</span>
                            <span class="text-xs text-zinc-500">{{ $report->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-300">{{ $report->body }}</p>

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
                <div class="py-4 text-center text-sm text-zinc-500">
                    {{ __('app.task.activity.no_activities') }}
                </div>
            @endif
        </div>

        @if($task && !($task->status === 'done' && $task->approval_status === 'approved'))
            <form wire:submit="{{ $submit }}" class="space-y-4">
                <flux:composer wire:model="{{ $bodyModel }}" :placeholder="$composerPlaceholder">
                    <x-slot name="actionsLeading">
                        <input type="file" wire:model="{{ $filesModel }}" multiple class="hidden" id="{{ $fileInputId }}">
                        <flux:button size="sm" variant="subtle" icon="paper-clip" onclick="document.getElementById('{{ $fileInputId }}').click()" />
                    </x-slot>

                    <x-slot name="actionsTrailing">
                        <flux:button type="submit" size="sm" variant="primary" icon="paper-airplane" />
                    </x-slot>
                </flux:composer>

                @if($files)
                    <div class="flex flex-wrap gap-2">
                        @foreach($files as $file)
                            <div class="flex items-center gap-1 rounded bg-zinc-100 px-2 py-1 text-xs dark:bg-zinc-800">
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
            <div class="rounded-lg border border-zinc-200 bg-zinc-100 p-4 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800/50">
                <flux:icon name="lock-closed" variant="micro" class="mr-1 inline-block" />
                {{ __('app.task.activity.task_is_locked') }}
            </div>
        @endif

        {{ $slot }}
    </div>
</flux:modal>