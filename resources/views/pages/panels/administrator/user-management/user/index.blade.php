<?php

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;

new #[Layout('layouts.panels.administrator')] #[On('panels.administrator.user-management.user.index.render')] class extends Component
{
    use WithPagination;

    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public string $search = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'page' => ['except' => 1],
    ];

    public function sort($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    #[Computed]
    public function users()
    {
        return \App\Models\User::query()
            ->when($this->search, function ($query) {
                $search = '%'.$this->search.'%';
                $query->where(function ($q) use ($search) {
                    $q->where('id', 'like', $search)
                        ->orWhere('first_name', 'like', $search)
                        ->orWhere('last_name', 'like', $search)
                        ->orWhere('mobile', 'like', $search)
                        ->orWhere('email', 'like', $search);
                });
            })
            ->tap(fn ($query) => $this->sortBy ? $query->orderBy($this->sortBy, $this->sortDirection) : $query)
            ->paginate(20);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function impersonate(int $userId)
    {
        $this->authorize('administrator_user_management_edit');

        $user = \App\Models\User::query()->findOrFail($userId);

        if ((int) Auth::id() === (int) $user->id) {
            Flux::toast(__('app.cannot_login_as_self'), variant: 'danger');
            return;
        }

        Auth::login($user);
        request()->session()->regenerate();

        Flux::toast(__('app.login_as_user_success'));

        return $this->redirectRoute('home', navigate: true);
    }

    public function mount()
    {
        $this->authorize('administrator_user_management_index');
    }
};

?>

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
                <flux:modal.trigger name="panels.administrator.user-management.user.create.modal">
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
            <flux:table.column>{{ __('app.id') }}</flux:table.column>
            <flux:table.column>{{ __('app.mobile') }}</flux:table.column>
            <flux:table.column>{{ __('app.name') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">{{ __('app.date') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach ($this->users as $user)
                @php
                    $avatarUrl = $user->getAvatarUrl();
                @endphp
                <flux:table.row :key="$user->id">
                    <flux:table.cell>
                        {{ $user->id }}
                    </flux:table.cell>
                    <flux:table.cell class="flex items-center gap-3">
                        @if ($avatarUrl)
                            <flux:avatar src="{{ $avatarUrl }}" size="xs" />
                        @else
                            <flux:avatar name="{{ $user->name }}" color="auto" size="xs" />
                        @endif
                        {{ $user->mobile }}
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $user->name }}
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">
                        <div class="flex gap-2">
                            @if (auth()->id() !== $user->id)
                                @can('administrator_user_management_edit')
                                    <flux:tooltip content="{{ __('actions.impersonate') }}">
                                        <flux:button size="xs" variant="primary" color="violet" icon="user-check" icon:variant="outline" wire:click="impersonate({{ $user->id }})" />
                                    </flux:tooltip>
                                @endcan
                            @endif
                            @can('administrator_user_management_edit')
                                <flux:tooltip content="{{ __('app.edit') }}">
                                    <flux:button size="xs" variant="primary" icon="pencil" icon:variant="outline" wire:click="$dispatch('panels.administrator.user-management.user.edit.assign-data', { id: '{{ $user->id }}' })" />
                                </flux:tooltip>
                            @endcan
                            @can('administrator_user_management_roles')
                                <flux:tooltip content="{{ __('app.roles') }}">
                                    <flux:button size="xs" variant="primary" color="orange" icon="shield-check" icon:variant="outline" wire:click="$dispatch('panels.administrator.user-management.user.roles.assign-data', { id: '{{ $user->id }}' })" />
                                </flux:tooltip>
                            @endcan
                            @can('administrator_user_management_permissions')
                                <flux:tooltip content="{{ __('app.permissions') }}">
                                    <flux:button size="xs" variant="primary" color="lime" icon="key" icon:variant="outline" wire:click="$dispatch('panels.administrator.user-management.user.permissions.assign-data', { id: '{{ $user->id }}' })" />
                                </flux:tooltip>
                            @endcan
                            @can('administrator_user_management_delete')
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
</div>
