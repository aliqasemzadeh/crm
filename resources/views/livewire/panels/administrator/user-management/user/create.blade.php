<flux:modal name="panel.administrator.user-management.user.create.modal" class="md:w-96" flyout position="right">
    <div class="space-y-6">
    <div>
        <flux:heading size="lg">{{ __('app.create_user') }}</flux:heading>
        <flux:text class="mt-2">{{ __('app.create_user_description') }}</flux:text>
    </div>
            <!-- Modal body -->
            <form wire:submit="create" method="post">
                <div class="pb-2">
                    <flux:field>
                        <flux:label>{{ __('app.mobile') }}</flux:label>

                        <flux:input wire:model="mobile" type="text" />

                        <flux:error name="mobile" />
                    </flux:field>
                </div>
                <flux:button type="submit" class="w-full" variant="primary">
                    {{ __('app.create') }}
                </flux:button>
            </form>
    </div>
</flux:modal>
