<form wire:submit="save">
    <flux:input.group>
        <flux:input wire:model="fee" mask:dynamic="$money($input, '.', ',', 0)" invalid />
        <flux:button type="submit" variant="primary">{{ __('app.save') }}</flux:button>
    </flux:input.group>
</form>
