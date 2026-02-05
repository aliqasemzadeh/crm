<x-slot name="title">
    {{ __('app.users') }}
</x-slot>
<div>
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.users') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.users_description') }}</flux:subheading>
            </div>
            @can('administrator_user_management_create')
                <flux:modal.trigger name="administrator.user-management.user.create.modal">
                    <flux:button variant="primary">{{ __('app.create_user') }}</flux:button>
                </flux:modal.trigger>
            @endcan
        </div>

        <flux:separator variant="subtle" />
    </div>
    <livewire:panels.administrator.user-management.user.create />
    <livewire:panels.administrator.user-management.user.edit />
    <livewire:panels.administrator.user-management.user.roles />
    <livewire:panels.administrator.user-management.user.permissions />

    <flux:table :paginate="$this->users">
        <flux:table.columns sticky class="bg-white dark:bg-zinc-900">
            <flux:table.column colspan="3" class="bg-white dark:bg-zinc-900">
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
            <flux:table.column>{{ __('app.mobile') }}</flux:table.column>
            <flux:table.column>{{ __('app.name') }}</flux:table.column>
            <flux:table.column sortable sorted direction="desc">{{ __('app.date') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach ($this->users as $user)
                <flux:table.row :key="$user->id">
                    <flux:table.cell class="flex items-center gap-3">
                        {{ $user->mobile }}
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $user->name }}
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">
                        @can('administrator_user_management_edit')
                            <flux:button size="xs" variant="primary" wire:click="$dispatch('panel.administrator.user-management.user.edit.assign-data', { id: '{{ $user->id }}' })">{{ __('app.edit') }}</flux:button>
                        @endcan
                        @can('administrator_user_management_roles')
                            <flux:button size="xs" variant="primary" color="orange" wire:click="$dispatch('panel.administrator.user-management.user.roles.assign-data', { id: '{{ $user->id }}' })">{{ __('app.roles') }}</flux:button>
                        @endcan
                        @can('administrator_user_management_permissions')
                            <flux:button size="xs" variant="primary" color="lime" wire:click="$dispatch('panel.administrator.user-management.user.permissions.assign-data', { id: '{{ $user->id }}' })">{{ __('app.permissions') }}</flux:button>
                        @endcan
                        @can('administrator_user_management_delete')
                            <flux:button size="xs" variant="danger">{{ __('app.delete') }}</flux:button>
                        @endcan
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>
