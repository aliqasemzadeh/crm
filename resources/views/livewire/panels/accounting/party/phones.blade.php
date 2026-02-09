<flux:modal name="panel.shop.sepidar.party.phones.modal" class="md:w-1/3" flyout position="right">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.phones') }}:  {{ $part->Name ?? "" }} {{ $part->LastName ?? "" }}</flux:heading>
            <flux:text class="mt-2">{{ __('app.phones_description') }}</flux:text>
        </div>
        <flux:table>
            <flux:table.columns class="bg-white dark:bg-zinc-900">
                <flux:table.column>{{ __('app.phone') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach($this->phones as $phone)
                    <flux:table.row>
                        <flux:table.cell>
                            {{ $phone->Phone }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>
</flux:modal>

