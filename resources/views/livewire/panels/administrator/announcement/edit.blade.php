<flux:modal name="panels.administrator.announcement.edit.modal" class="md:min-w-[600px]">
    <form wire:submit="edit" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.edit_announcement') }}</flux:heading>
            <flux:subheading>{{ __('app.edit_announcement_description') }}</flux:subheading>
        </div>

        <flux:input wire:model="title" label="{{ __('app.title') }}" />

        <flux:field>
            <flux:label>{{ __('app.content') }}</flux:label>
            <flux:editor wire:model="content" />
            <flux:error name="content" />
        </flux:field>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:input wire:model="icon" label="{{ __('app.icon') }}" />
            <flux:input wire:model="link" label="{{ __('app.link') }}" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
             <flux:select wire:model="color" label="{{ __('app.color') }}">
                <flux:select.option value="zinc">Zinc</flux:select.option>
                <flux:select.option value="red">Red</flux:select.option>
                <flux:select.option value="orange">Orange</flux:select.option>
                <flux:select.option value="amber">Amber</flux:select.option>
                <flux:select.option value="yellow">Yellow</flux:select.option>
                <flux:select.option value="lime">Lime</flux:select.option>
                <flux:select.option value="green">Green</flux:select.option>
                <flux:select.option value="emerald">Emerald</flux:select.option>
                <flux:select.option value="teal">Teal</flux:select.option>
                <flux:select.option value="cyan">Cyan</flux:select.option>
                <flux:select.option value="sky">Sky</flux:select.option>
                <flux:select.option value="blue">Blue</flux:select.option>
                <flux:select.option value="indigo">Indigo</flux:select.option>
                <flux:select.option value="violet">Violet</flux:select.option>
                <flux:select.option value="purple">Purple</flux:select.option>
                <flux:select.option value="fuchsia">Fuchsia</flux:select.option>
                <flux:select.option value="pink">Pink</flux:select.option>
                <flux:select.option value="rose">Rose</flux:select.option>
            </flux:select>

            <flux:field variant="inline">
                <flux:label>{{ __('app.is_active') }}</flux:label>
                <flux:switch wire:model="is_active" />
            </flux:field>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:date-picker wire:model="starts_at" label="{{ __('app.starts_at') }}" />
            <flux:date-picker wire:model="ends_at" label="{{ __('app.ends_at') }}" />
        </div>

        <div class="flex">
            <flux:spacer />
            <flux:modal.close>
                <flux:button variant="ghost">{{ __('app.logout.cancel') }}</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary" class="ms-3">{{ __('app.update') }}</flux:button>
        </div>
    </form>
</flux:modal>
