<flux:modal name="panels.administrator.workspace.request-type.create.modal" class="md:w-96" flyout position="right">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.create_request_type') }}</flux:heading>
            <flux:text class="mt-2">{{ __('app.create_request_type_description') }}</flux:text>
        </div>

        <form wire:submit="create" class="space-y-4">
            <flux:field>
                <flux:label>{{ __('app.request_type_name') }}</flux:label>
                <flux:input wire:model="name" type="text" placeholder="e.g. purchase" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('app.request_type_title') }}</flux:label>
                <flux:input wire:model="title" type="text" placeholder="مثلا درخواست خرید" />
                <flux:error name="title" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('app.request_type_description') }}</flux:label>
                <flux:textarea wire:model="description" />
                <flux:error name="description" />
            </flux:field>

            <flux:checkbox wire:model="is_active" label="{{ __('app.request_type_is_active') }}" />

            <flux:button type="submit" class="w-full" variant="primary">
                {{ __('app.create') }}
            </flux:button>
        </form>
    </div>
</flux:modal>
