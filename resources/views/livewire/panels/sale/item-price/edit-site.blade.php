<flux:modal name="panels.sale.item-price.edit-site.modal" class="md:w-96" flyout position="right">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.edit_irancode') }} : {{ $item_title ?? '' }}</flux:heading>
        </div>
        <form wire:submit="edit" method="post">
            <div class="pb-2">
                <flux:field>
                    <flux:label>{{ __('app.irancode') }}</flux:label>
                    <flux:input wire:model="iran_code" type="text" />
                    <flux:error name="iran_code" />
                </flux:field>
            </div>
            <flux:button type="submit" class="w-full" variant="primary">
                {{ __('app.update') }}
            </flux:button>
        </form>
    </div>
</flux:modal>
