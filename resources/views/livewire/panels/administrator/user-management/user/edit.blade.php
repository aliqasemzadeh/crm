<flux:modal name="panel.administrator.user-management.user.edit.modal" class="md:w-96" flyout position="right">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.edit_user') }} : {{ isset($mobile) ? $mobile : '' }}</flux:heading>
            <flux:text class="mt-2">{{ __('app.edit_user_description') }}</flux:text>
        </div>
        <form wire:submit="edit" method="post">
        <div class="pb-2">
            <flux:field>
                    <flux:label>{{ __('app.first_name') }}</flux:label>

                    <flux:input wire:model="first_name" type="text" />

                    <flux:error name="first_name" />
            </flux:field>
            <flux:field>
                <flux:label>{{ __('app.last_name') }}</flux:label>

                <flux:input wire:model="last_name" type="text" />

                <flux:error name="last_name" />
            </flux:field>
            <flux:field>
                <flux:label>{{ __('app.mobile') }}</flux:label>

                <flux:input wire:model="mobile" type="text" />

                <flux:error name="mobile" />
            </flux:field>
            <flux:field>
                <flux:label>{{ __('app.email') }}</flux:label>

                <flux:input wire:model="email" type="email" />

                <flux:error name="email" />
            </flux:field>
            <flux:field>
                <flux:label>{{ __('app.password') }}</flux:label>

                <flux:input wire:model="password" type="password" />

                <flux:error name="password" />
            </flux:field>
            <flux:field>
                <flux:label>{{ __('app.password_confirmation') }}</flux:label>

                <flux:input wire:model="password_confirmation" type="password" />

                <flux:error name="password_confirmation" />
            </flux:field>
        </div>
        <flux:button type="submit" class="w-full" variant="primary">
            {{ __('app.update') }}
        </flux:button>
    </form>
    </div>
</flux:modal>
