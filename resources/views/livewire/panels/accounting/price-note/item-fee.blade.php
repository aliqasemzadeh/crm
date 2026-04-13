<div class="space-y-4">
    <form wire:submit="save">
        <flux:input.group>
            <flux:input required wire:model="fee" mask:dynamic="$money($input, '.', ',', 0)" />
            <flux:button type="submit" variant="primary">{{ __('app.save') }}</flux:button>
        </flux:input.group>
    </form>

    @if($productPriceId)
        <hr class="border-gray-200 dark:border-white/10" />

        <form wire:submit="saveSite">
            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <flux:input wire:model="siteFee" label="{{ __('app.site_price') }}" mask:dynamic="$money($input, '.', ',', 0)" />
                </div>
                <div class="w-24">
                    <flux:input wire:model="siteStock" label="{{ __('app.site_stock') }}" type="number" />
                </div>
                <flux:button type="submit" variant="primary">{{ __('app.save') }}</flux:button>
            </div>
        </form>

        <flux:modal.trigger name="sync-title-confirm">
            <flux:button variant="subtle" size="sm" class="w-full">{{ __('app.sync_titles') }}</flux:button>
        </flux:modal.trigger>

        <flux:modal name="sync-title-confirm" variant="destructive" class="min-w-[22rem]">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('app.sync_titles') }}</flux:heading>
                    <flux:subheading>{{ __('app.sync_titles_confirm') }}</flux:subheading>
                </div>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('app.cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button wire:click="syncTitle" type="submit" variant="danger">{{ __('app.confirm') }}</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
