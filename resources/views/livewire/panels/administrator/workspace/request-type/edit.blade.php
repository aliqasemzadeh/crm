<flux:modal name="panels.administrator.workspace.request-type.edit.modal" class="md:w-[40rem]" flyout position="right">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.edit_request_type') }}</flux:heading>
            <flux:text class="mt-2">{{ __('app.edit_request_type_description') }}</flux:text>
        </div>

        <form wire:submit="edit" class="space-y-4">
            <flux:field>
                <flux:label>{{ __('app.request_type_name') }}</flux:label>
                <flux:input wire:model="name" type="text" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('app.request_type_title') }}</flux:label>
                <flux:input wire:model="title" type="text" />
                <flux:error name="title" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('app.request_type_description') }}</flux:label>
                <flux:textarea wire:model="description" />
                <flux:error name="description" />
            </flux:field>

            <flux:checkbox wire:model="is_active" label="{{ __('app.request_type_is_active') }}" />

            <flux:field>
                <flux:label>{{ __('app.request_type_schema') }}</flux:label>
                <flux:textarea wire:model="schema_text" rows="15" dir="ltr" class="font-mono" />
                <flux:error name="schema_text" />
            </flux:field>

            <flux:button type="submit" class="w-full" variant="primary">
                {{ __('app.update') }}
            </flux:button>
        </form>
    </div>
</flux:modal>
