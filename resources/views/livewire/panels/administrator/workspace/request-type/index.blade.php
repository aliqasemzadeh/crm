<x-slot name="title">
    {{ __('app.request_types') }}
</x-slot>
<div>
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.request_types') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.request_types_description') }}</flux:subheading>
            </div>
            <flux:modal.trigger name="panels.administrator.workspace.request-type.create.modal">
                <flux:button variant="primary">{{ __('app.create_request_type') }}</flux:button>
            </flux:modal.trigger>
        </div>

        <flux:separator variant="subtle" />
    </div>

    <livewire:panels.administrator.workspace.request-type.create />
    <livewire:panels.administrator.workspace.request-type.edit />

    <flux:table :paginate="$requestTypes">
        <flux:table.columns sticky class="bg-white dark:bg-zinc-900">
            <flux:table.column colspan="5" class="bg-white dark:bg-zinc-900">
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
            <flux:table.column>{{ __('app.request_type_name') }}</flux:table.column>
            <flux:table.column>{{ __('app.request_type_title') }}</flux:table.column>
            <flux:table.column>{{ __('app.request_type_is_active') }}</flux:table.column>
            <flux:table.column>{{ __('app.request_type_created_at') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach ($requestTypes as $type)
                <flux:table.row :key="$type->id">
                    <flux:table.cell>
                        {{ $type->id }}
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $type->name }}
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $type->title }}
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$type->is_active ? 'green' : 'red'" size="sm">
                            {{ $type->is_active ? __('app.active') : __('app.inactive') }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">
                        {{ $type->created_at->format('Y/m/d H:i') }}
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap flex gap-2 justify-end">
                        <flux:button size="xs" variant="primary" wire:click="$dispatch('panels.administrator.workspace.request-type.edit.assign-data', { id: {{ $type->id }} })">
                            {{ __('app.edit') }}
                        </flux:button>
                        <flux:modal.trigger name="delete-request-type-{{ $type->id }}">
                            <flux:button size="xs" variant="danger">
                                {{ __('app.delete') }}
                            </flux:button>
                        </flux:modal.trigger>

                        <flux:modal name="delete-request-type-{{ $type->id }}" class="md:w-96">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">{{ __('app.delete') }}</flux:heading>
                                    <flux:text class="mt-2">{{ __('app.are_you_sure') }}</flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer />
                                    <flux:modal.close>
                                        <flux:button variant="ghost">{{ __('app.cancel') }}</flux:button>
                                    </flux:modal.close>
                                    <flux:button variant="danger" wire:click="delete({{ $type->id }})">{{ __('app.delete') }}</flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>
