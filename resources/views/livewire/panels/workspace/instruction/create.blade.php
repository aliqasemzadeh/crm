<flux:modal name="instruction-create-modal" class="md:w-[40rem]" flyout position="right">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.create_instruction') }}</flux:heading>
        </div>

        <form wire:submit="save" class="space-y-6">
            <flux:input wire:model="title" label="{{ __('app.title') }}" />

            <flux:textarea wire:model="body" label="{{ __('app.body') }}" rows="10" />

            <flux:select wire:model="status" label="{{ __('app.status') }}">
                <flux:select.option value="draft">{{ __('app.instruction_status.draft') }}</flux:select.option>
                <flux:select.option value="finalized">{{ __('app.instruction_status.finalized') }}</flux:select.option>
            </flux:select>

            <div class="flex gap-2">
                <flux:button type="submit" variant="primary">{{ __('app.save') }}</flux:button>
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('app.cancel') }}</flux:button>
                </flux:modal.close>
            </div>
        </form>
    </div>
</flux:modal>
