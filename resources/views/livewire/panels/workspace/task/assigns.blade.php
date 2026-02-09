<div>
    <flux:main>
        <div class="mb-6">
            <flux:heading size="xl">{{ __('app.task.assign_users') }}: {{ $task->title }}</flux:heading>
            <flux:subheading>{{ __('app.task.assign_users_description') }}</flux:subheading>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="space-y-4">
                <flux:field>
                    <flux:label>{{ __('app.search') }}</flux:label>
                    <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('app.search_placeholder') }}" type="text" icon="magnifying-glass" />
                </flux:field>

                @if(count($users) > 0)
                    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($users as $user)
                            <div class="p-4 flex items-center justify-between">
                                <div>
                                    <div class="font-medium">{{ $user->name }}</div>
                                    <div class="text-xs text-zinc-500">{{ $user->mobile }}</div>
                                </div>
                                <div class="flex gap-1">
                                    <flux:button size="xs" wire:click="assign({{ $user->id }}, 'assignee')">{{ __('app.task.assignee') }}</flux:button>
                                    <flux:button size="xs" wire:click="assign({{ $user->id }}, 'reviewer')">{{ __('app.task.reviewer') }}</flux:button>
                                    <flux:button size="xs" wire:click="assign({{ $user->id }}, 'watcher')">{{ __('app.task.watcher') }}</flux:button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @elseif(strlen($search) >= 2)
                    <div class="p-4 text-center text-zinc-500 italic">
                        {{ __('app.no_results_found') }}
                    </div>
                @endif
            </div>

            <div class="space-y-4">
                <flux:heading size="lg">{{ __('app.task.review.task_users') }}</flux:heading>

                <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($assignedUsers as $user)
                        <div class="p-4 flex items-center justify-between">
                            <div>
                                <div class="font-medium">{{ $user->name }}</div>
                                <div class="text-xs text-zinc-500">
                                    <flux:badge size="sm" variant="outline" color="zinc">
                                        {{ __('app.task.' . $user->pivot->role) }}
                                    </flux:badge>
                                    {{ $user->mobile }}
                                </div>
                            </div>
                            <flux:button icon="trash" size="sm" variant="ghost" color="red" wire:click="delete({{ $user->id }}, '{{ $user->pivot->role }}')" wire:confirm="{{ __('app.are_you_sure') }}" />
                        </div>
                    @empty
                        <div class="p-8 text-center text-zinc-500">
                            {{ __('app.task.no_tasks') }}
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="mt-8">
            <flux:button href="{{ route('panels.workspace.task.index') }}" variant="ghost" wire:navigate>{{ __('app.task.cancel') }}</flux:button>
        </div>
    </flux:main>
</div>
