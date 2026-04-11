<div>
    <flux:main>
        <div class="mb-6">
            <flux:heading size="xl">{{ __('app.create_instruction') }}</flux:heading>
        </div>

        <form wire:submit="save" class="space-y-6 max-w-4xl">
            <flux:input wire:model="title" label="{{ __('app.title') }}" />

            <flux:textarea wire:model="body" label="{{ __('app.body') }}" rows="10" />

            <flux:select wire:model="status" label="{{ __('app.status') }}">
                <flux:select.option value="draft">{{ __('app.instruction_status.draft') }}</flux:select.option>
                <flux:select.option value="finalized">{{ __('app.instruction_status.finalized') }}</flux:select.option>
            </flux:select>

            <div class="flex gap-2">
                <flux:button type="submit" variant="primary">{{ __('app.save') }}</flux:button>
                <flux:button href="{{ route('panels.workspace.instruction.index') }}" variant="ghost" wire:navigate>{{ __('app.cancel') }}</flux:button>
            </div>
        </form>
    </flux:main>
</div>
