<x-slot name="title">
    {{ __('app.permissions') }}
</x-slot>
<div>
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.permissions') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.permissions_description') }}</flux:subheading>
            </div>
            @can('administrator_user_management_permission_create')
                <flux:modal.trigger name="panels.administrator.user-management.permission.create.modal">
                    <flux:button variant="primary">{{ __('app.create_permission') }}</flux:button>
                </flux:modal.trigger>
            @endcan
        </div>

        <flux:separator variant="subtle" />
    </div>

    <livewire:panels.administrator.user-management.permission.create />
    <livewire:panels.administrator.user-management.permission.edit />

    <flux:table :paginate="$this->permissions">
        <flux:table.columns sticky class="bg-white dark:bg-zinc-900">
            <flux:table.column colspan="4" class="bg-white dark:bg-zinc-900">
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
            <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">{{ __('app.name') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'guard_name'" :direction="$sortDirection" wire:click="sort('guard_name')">{{ __('app.guard_name') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">{{ __('app.date') }}</flux:table.column>
            <flux:table.column />
        </flux:table.columns>

        @foreach ($this->permissions as $permission)
            <flux:table.row :key="$permission->id">
                <flux:table.cell class="whitespace-nowrap">{{ $permission->name }}</flux:table.cell>
                <flux:table.cell class="whitespace-nowrap">{{ $permission->guard_name }}</flux:table.cell>
                <flux:table.cell class="whitespace-nowrap">{{ $permission->created_at?->format('Y-m-d H:i') }}</flux:table.cell>
                <flux:table.cell class="whitespace-nowrap">
                    @can('administrator_user_management_permission_edit')
                        <flux:button size="xs" variant="primary" wire:click="$dispatch('panels.administrator.user-management.permission.edit.assign-data', { id: '{{ $permission->id }}' })">{{ __('app.edit') }}</flux:button>
                    @endcan
                    @can('administrator_user_management_permission_delete')
                        <flux:button size="xs" variant="danger">{{ __('app.delete') }}</flux:button>
                    @endcan
                </flux:table.cell>
            </flux:table.row>
        @endforeach
    </flux:table>
</div>
