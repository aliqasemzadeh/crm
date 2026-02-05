<div>
        <flux:table>
            <flux:table.columns sticky class="bg-white dark:bg-zinc-900">
                <flux:table.column colspan="5" class="bg-white dark:bg-zinc-900">
                    <div class="flex flex-col gap-1 pe-2 items-end">
                        <flux:input
                            size="sm"
                            placeholder="{{ __('app.search_placeholder') }}"
                            wire:model.live="search"
                        />
                    </div>
                </flux:table.column>
            </flux:table.columns>
            <flux:table.columns>
                <flux:table.column>{{ __('app.name') }}</flux:table.column>
                <flux:table.column>{{ __('app.balance') }}</flux:table.column>
                <flux:table.column>{{ __('app.quantity') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach($this->groupingss as $group)
                    <flux:table.row>
                        <flux:table.cell class="flex items-center gap-3">
                            {{ $group->Title }}
                        </flux:table.cell>
                        <flux:table.cell>

                        </flux:table.cell>
                        <flux:table.cell>

                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
</div>
