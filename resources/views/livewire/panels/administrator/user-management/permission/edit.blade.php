<flux:modal name="administrator.user-management.permission.edit.modal" class="md:w-96">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.edit_permission') }} : {{ $permission->name }}</flux:heading>
            <flux:text class="mt-2">{{ __('app.edit_permission_description') }}</flux:text>
        </div>
        <form wire:submit="edit" method="post">
            <div class="pb-2">
                <flux:field>
                    <flux:label>{{ __('app.name') }}</flux:label>

                    <flux:input wire:model="name" type="text" />

                    <flux:error name="name" />
                </flux:field>
                <flux:field>
                    <flux:label>{{ __('app.guard_name') }}</flux:label>

                    <flux:input wire:model="guard_name" type="text" />

                    <flux:error name="guard_name" />
                </flux:field>
            </div>
            <button type="submit" class="btn-default btn-indigo w-full">
                {{ __('app.update') }}
            </button>
        </form>
    </div>
</flux:modal>
