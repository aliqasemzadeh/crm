@props([
    'modalName',
    'task' => null,
    'users' => [],
    'assignedUsers' => [],
    'search' => '',
])

<flux:modal :name="$modalName" flyout position="right" class="md:w-1/2">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.task.assign_users') }}</flux:heading>
            @if($task)
                <flux:subheading>{{ $task->title }}</flux:subheading>
            @endif
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div class="space-y-4">
                <flux:field>
                    <flux:label>{{ __('app.search') }}</flux:label>
                    <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('app.search_placeholder')" type="text" icon="magnifying-glass" />
                </flux:field>

                @if(count($users) > 0)
                    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white divide-y divide-zinc-200 dark:border-zinc-700 dark:bg-zinc-800 dark:divide-zinc-700">
                        @foreach($users as $user)
                            <div class="flex items-center justify-between p-3">
                                <div class="flex items-center gap-2">
                                    <flux:avatar src="{{ $user->getAvatarUrl() }}" size="xs" :name="$user->name" />
                                    <div>
                                        <div class="text-xs font-medium text-zinc-800 dark:text-white">{{ $user->name }}</div>
                                        <div class="text-[10px] text-zinc-500">{{ $user->mobile }}</div>
                                    </div>
                                </div>

                                <flux:dropdown>
                                    <flux:button size="xs" variant="subtle" icon-trailing="chevron-down">{{ __('app.task.assignee') }}</flux:button>
                                    <flux:menu>
                                        <flux:menu.item wire:click="assign({{ $user->id }}, 'assignee')">{{ __('app.task.assignee') }}</flux:menu.item>
                                        <flux:menu.item wire:click="assign({{ $user->id }}, 'reviewer')">{{ __('app.task.reviewer') }}</flux:menu.item>
                                        <flux:menu.item wire:click="assign({{ $user->id }}, 'watcher')">{{ __('app.task.watcher') }}</flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </div>
                        @endforeach
                    </div>
                @elseif(strlen($search) >= 2)
                    <div class="p-4 text-center text-sm italic text-zinc-500">
                        {{ __('app.no_results_found') }}
                    </div>
                @endif
            </div>

            <div class="space-y-4">
                <flux:heading size="sm">{{ __('app.task.review.task_users') }}</flux:heading>

                <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white divide-y divide-zinc-200 dark:border-zinc-700 dark:bg-zinc-800 dark:divide-zinc-700">
                    @forelse($assignedUsers as $user)
                        <div class="flex items-center justify-between p-3">
                            <div class="flex items-center gap-2">
                                <flux:avatar src="{{ $user->getAvatarUrl() }}" size="xs" :name="$user->name" />
                                <div>
                                    <div class="text-xs font-medium text-zinc-800 dark:text-white">{{ $user->name }}</div>
                                    <div class="text-[10px] text-zinc-500">
                                        <flux:badge size="sm" variant="outline" class="origin-right scale-75">
                                            {{ __('app.task.' . $user->pivot->role) }}
                                        </flux:badge>
                                    </div>
                                </div>
                            </div>
                            <flux:button icon="trash" size="xs" variant="ghost" color="red" wire:click="delete({{ $user->id }}, '{{ $user->pivot->role }}')" />
                        </div>
                    @empty
                        <div class="p-8 text-center text-sm text-zinc-500">
                            {{ __('app.task.no_tasks') }}
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <flux:modal.close>
                <flux:button variant="ghost">{{ __('app.close') }}</flux:button>
            </flux:modal.close>
        </div>
    </div>
</flux:modal>