<x-slot name="title">
    {{ __('app.announcements') }}
</x-slot>
<div>
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.announcements') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.announcements_description') }}</flux:subheading>
            </div>
            @can('administrator_announcement_create')
                <flux:modal.trigger name="panels.administrator.announcement.create.modal">
                    <flux:button variant="primary">{{ __('app.create_announcement') }}</flux:button>
                </flux:modal.trigger>
            @endcan
        </div>

        <flux:separator variant="subtle" />
    </div>
    <livewire:panels.administrator.announcement.create />
    <livewire:panels.administrator.announcement.edit />
    <livewire:panels.administrator.announcement.users />

    <flux:table :paginate="$this->announcements">
        <flux:table.columns sticky class="bg-white dark:bg-zinc-900">
            <flux:table.column colspan="6" class="bg-white dark:bg-zinc-900">
                <div class="flex flex-col gap-1 pe-2 items-end">
                    <flux:input
                        size="sm"
                        placeholder="{{ __('app.search_placeholder') }}"
                        wire:model.live="search"
                    />
                </div>
            </flux:table.column>
        </flux:table.columns>
        <flux:table.columns>
            <flux:table.column>{{ __('app.id') }}</flux:table.column>
            <flux:table.column>{{ __('app.title') }}</flux:table.column>
            <flux:table.column>{{ __('app.is_active') }}</flux:table.column>
            <flux:table.column>{{ __('app.link') }}</flux:table.column>
            <flux:table.column>{{ __('app.date') }}</flux:table.column>
            <flux:table.column>{{ __('app.options') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->announcements as $announcement)
                <flux:table.row :key="$announcement->id">
                    <flux:table.cell>
                        {{ $announcement->id }}
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center gap-2">
                             @if($announcement->icon)
                                <flux:icon :icon="$announcement->icon" variant="mini" />
                             @endif
                             {{ $announcement->title }}
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($announcement->is_active)
                            <flux:badge color="green" inset="top">{{ __('app.active') }}</flux:badge>
                        @else
                            <flux:badge color="red" inset="top">{{ __('app.inactive') }}</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($announcement->link)
                            <flux:button icon="link" variant="ghost" size="xs" href="{{ $announcement->link }}" target="_blank" />
                        @else
                            -
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex flex-col text-xs text-zinc-500">
                            <div class="flex items-center gap-1">
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ __('app.starts_at') }}:</span>
                                <span>{{ $announcement->starts_at?->format('Y/m/d H:i') ?: '-' }}</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ __('app.ends_at') }}:</span>
                                <span>{{ $announcement->ends_at?->format('Y/m/d H:i') ?: '-' }}</span>
                            </div>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">
                        @can('administrator_announcement_edit')
                            <flux:button size="xs" variant="primary" wire:click="$dispatch('panels.administrator.announcement.edit.assign-data', { id: '{{ $announcement->id }}' })">{{ __('app.edit') }}</flux:button>
                        @endcan
                        @can('administrator_announcement_users')
                            <flux:button size="xs" variant="primary" color="orange" wire:click="$dispatch('panels.administrator.announcement.users.assign-data', { id: '{{ $announcement->id }}' })">{{ __('app.viewed_users') }}</flux:button>
                        @endcan
                        @can('administrator_announcement_delete')
                             <flux:modal.trigger name="delete-announcement-{{ $announcement->id }}">
                                <flux:button size="xs" variant="danger">{{ __('app.delete') }}</flux:button>
                             </flux:modal.trigger>
                             <flux:modal name="delete-announcement-{{ $announcement->id }}" class="min-w-[22rem]">
                                <form wire:submit="delete({{ $announcement->id }})" class="space-y-6">
                                    <div>
                                        <flux:heading size="lg">{{ __('app.are_you_sure') }}</flux:heading>
                                        <flux:subheading>
                                            {{ __('app.delete') }} {{ $announcement->title }}
                                        </flux:subheading>
                                    </div>

                                    <div class="flex">
                                        <flux:spacer />
                                        <flux:modal.close>
                                            <flux:button variant="ghost">{{ __('app.logout.cancel') }}</flux:button>
                                        </flux:modal.close>
                                        <flux:button type="submit" variant="danger" class="ms-3">{{ __('app.delete') }}</flux:button>
                                    </div>
                                </form>
                            </flux:modal>
                        @endcan
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center py-4">
                        {{ __('app.no_announcements_found') }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
