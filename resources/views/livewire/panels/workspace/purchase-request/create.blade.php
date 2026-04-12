<flux:modal name="panels.workspace.purchase-request.create.modal" class="md:w-96" flyout position="right">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.create_purchase_request') }}</flux:heading>
            <flux:text class="mt-2">{{ __('app.create_purchase_request_description') }}</flux:text>
        </div>
        <!-- Modal body -->
        <form wire:submit="create" method="post" class="space-y-4">
            <flux:field>
                <flux:label>{{ __('app.item_name') }}</flux:label>
                <flux:input wire:model="name" type="text" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('app.quantity') }}</flux:label>
                <flux:input wire:model="quantity" type="number" min="1" />
                <flux:error name="quantity" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('app.approximate_price') }}</flux:label>
                <flux:input wire:model="price" type="number" step="0.01" />
                <flux:error name="price" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('app.supplier') }} ({{ __('app.optional') }})</flux:label>
                <flux:input wire:model="supplier" type="text" />
                <flux:error name="supplier" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('app.description_reason') }}</flux:label>
                <flux:textarea wire:model="description" />
                <flux:error name="description" />
            </flux:field>

            <flux:button type="submit" class="w-full" variant="primary">
                {{ __('app.create') }}
            </flux:button>
        </form>
    </div>
</flux:modal>
