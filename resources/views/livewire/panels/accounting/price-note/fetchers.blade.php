<flux:modal name="panels.accounting.price-note.fetchers.modal" class="min-w-[80%]" flyout position="right">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.fetchers') }}</flux:heading>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:field>
                <flux:label>{{ __('app.fetcher') }}</flux:label>
                <flux:select wire:model="fetcher" placeholder="{{ __('app.select_fetcher') }}">
                    <flux:select.option></flux:select.option>
                    @foreach($supportedFetchers as $name => $class)
                        <flux:select.option :value="$name">{{ $name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="fetcher" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('app.link') }}</flux:label>
                <flux:input wire:model="link" type="text" />
                <flux:error name="link" />
            </flux:field>

            <div class="flex items-end">
                <flux:button wire:click="add" variant="primary">{{ __('app.add_fetcher') }}</flux:button>
            </div>
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('app.fetcher') }}</flux:table.column>
                <flux:table.column>{{ __('app.link') }}</flux:table.column>
                <flux:table.column>{{ __('app.last_fetched_price') }}</flux:table.column>
                <flux:table.column>{{ __('app.message') }}</flux:table.column>
                <flux:table.column>{{ __('app.action') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach($this->fetchers as $itemFetcher)
                    <flux:table.row wire:key="fetcher-row-{{ $itemFetcher->id }}">
                        <flux:table.cell>{{ $itemFetcher->fetcher }}</flux:table.cell>
                        <flux:table.cell class="max-w-xs truncate" title="{{ $itemFetcher->link }}">{{ $itemFetcher->link }}</flux:table.cell>
                        <flux:table.cell>{{ $itemFetcher->price ? number_format($itemFetcher->price) : '-' }}</flux:table.cell>
                        <flux:table.cell>
                            @if($itemFetcher->message)
                                <span class="text-red-500 text-xs">{{ $itemFetcher->message }}</span>
                            @endif
                                @if($itemFetcher->updated_at)
                                    <span
                                        class="text-xs">{{ \Morilog\Jalali\Jalalian::fromDateTime($itemFetcher->updated_at)->format('Y/m/d H:i:s') }}</span>
                                @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-2">
                                <flux:button size="xs" href="{{ $itemFetcher->link }}" target="_blank" rel="nofollow noopener noreferrer">
                                    <flux:icon.link class="w-4 h-4" />
                                </flux:button>
                                <flux:button size="xs" wire:click="run({{ $itemFetcher->id }})" variant="primary" color="green" wire:loading.attr="disabled">
                                    <flux:icon.play class="w-4 h-4" />
                                </flux:button>
                                <flux:button size="xs" wire:click="delete({{ $itemFetcher->id }})" variant="danger" wire:confirm="{{ __('app.are_you_sure') }}">
                                    <flux:icon.trash class="w-4 h-4" />
                                </flux:button>

                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>
</flux:modal>
