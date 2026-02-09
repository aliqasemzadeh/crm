<div class="space-y-6">
    <div class="flex justify-between items-center">
        <flux:heading size="xl">{{ __('app.workspace_review.users_list') }}</flux:heading>
        <div class="w-1/3">
            <flux:input wire:model.live="search" icon="magnifying-glass" placeholder="{{ __('app.search') }}..." />
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @foreach($users as $user)
            <flux:card class="flex flex-col justify-between">
                <div class="flex items-center gap-4 mb-4">
                    <flux:avatar src="{{ $user->avatar_url }}" name="{{ $user->name }}" size="lg" />
                    <div>
                        <flux:heading size="md">{{ $user->name }}</flux:heading>
                        <flux:subheading size="xs">{{ $user->email }}</flux:subheading>
                    </div>
                </div>

                <div class="flex justify-between items-center mt-auto pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <div class="flex flex-col">
                        <span class="text-xs text-zinc-500">{{ __('app.workspace_review.tasks_count') }}</span>
                        <flux:badge color="zinc" size="sm">{{ $user->tasks_count }}</flux:badge>
                    </div>
                    <flux:button
                        variant="primary"
                        icon-trailing="chevron-left"
                        href="{{ route('panels.workspace.review.user.board', $user) }}"
                    >
                        {{ __('app.workspace_review.view_board') }}
                    </flux:button>
                </div>
            </flux:card>
        @endforeach
    </div>

    <div class="mt-6">
        {{ $users->links() }}
    </div>
</div>
