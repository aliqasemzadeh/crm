<?php

use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
use Livewire\Attributes\Computed;

new #[Layout('layouts.panels.administrator')] class extends Component
{
    use WithPagination;

    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    #[Computed]
    public function roles()
    {
        return Role::query()
            ->tap(fn ($query) => $this->sortBy ? $query->orderBy($this->sortBy, $this->sortDirection) : $query)
            ->paginate(10);
    }

    public function mount()
    {
        $this->authorize('administrator_user_management_role_index');
    }
};

?>

<x-slot name="title">
    {{ __('app.roles') }}
</x-slot>
<flux:main>
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.roles') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.roles_description') }}</flux:subheading>
            </div>
            @can('administrator_user_management_role_create')
                <flux:modal.trigger name="panels.administrator.user-management.role.create.modal">
                    <flux:button variant="primary">{{ __('app.create_role') }}</flux:button>
                </flux:modal.trigger>
            @endcan
        </div>

        <flux:separator variant="subtle" />
    </div>

    <livewire:panels.administrator.user-management.role.create />
    <livewire:panels.administrator.user-management.role.edit />
    <livewire:panels.administrator.user-management.role.permissions />
    <livewire:panels.administrator.user-management.role.users />

    <flux:table :paginate="$this->roles">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">{{ __('app.name') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'guard_name'" :direction="$sortDirection" wire:click="sort('guard_name')">{{ __('app.guard_name') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">{{ __('app.date') }}</flux:table.column>
            <flux:table.column />
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->roles as $role)
                <flux:table.row :key="$role->id">
                    <flux:table.cell class="whitespace-nowrap">{{ $role->name }}</flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $role->guard_name }}</flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $role->created_at?->format('Y-m-d H:i') }}</flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">
                        <div class="flex gap-2">
                            @can('administrator_user_management_role_edit')
                                <flux:tooltip content="{{ __('app.edit') }}">
                                    <flux:button size="xs" variant="primary" icon="pencil" icon:variant="outline" wire:click="$dispatch('panels.administrator.user-management.role.edit.assign-data', { id: '{{ $role->id }}' })" />
                                </flux:tooltip>
                            @endcan
                            @can('administrator_user_management_role_users')
                                <flux:tooltip content="{{ __('app.users') }}">
                                    <flux:button size="xs" variant="primary" color="orange" icon="users" icon:variant="outline" wire:click="$dispatch('panels.administrator.user-management.role.users.assign-data', { id: '{{ $role->id }}' })" />
                                </flux:tooltip>
                            @endcan
                            @can('administrator_user_management_role_permissions')
                                <flux:tooltip content="{{ __('app.permissions') }}">
                                    <flux:button size="xs" variant="primary" color="lime" icon="key" icon:variant="outline" wire:click="$dispatch('panels.administrator.user-management.role.permissions.assign-data', { id: '{{ $role->id }}' })" />
                                </flux:tooltip>
                            @endcan
                            @can('administrator_user_management_role_delete')
                                <flux:tooltip content="{{ __('app.delete') }}">
                                    <flux:button size="xs" variant="primary" color="red" icon="trash" icon:variant="outline" wire:confirm="{{ __('common.are_you_sure') }}" />
                                </flux:tooltip>
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</flux:main>
