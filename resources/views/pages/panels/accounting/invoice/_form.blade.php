@php
    $issuer = $this->issuerName;
@endphp

<div class="mx-auto max-w-5xl">
    <div class="rounded-xl border border-zinc-300 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900 overflow-hidden">
        {{-- Invoice header --}}
        <div class="border-b border-zinc-200 bg-zinc-50 px-6 py-5 dark:border-zinc-700 dark:bg-zinc-800/60">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <flux:heading size="xl">{{ $heading }}</flux:heading>
                    <flux:text class="mt-1">{{ $subheading }}</flux:text>
                </div>
                <div class="text-sm space-y-1 md:text-left">
                    <div class="flex gap-2 justify-between md:justify-end">
                        <span class="text-zinc-500">{{ __('app.issuer') }}:</span>
                        <span class="font-medium">{{ $issuer }}</span>
                    </div>
                    @isset($invoiceNumber)
                        <div class="flex gap-2 justify-between md:justify-end">
                            <span class="text-zinc-500">{{ __('app.invoice_number') }}:</span>
                            <span class="font-medium">{{ $invoiceNumber }}</span>
                        </div>
                    @endisset
                </div>
            </div>
        </div>

        <div class="p-6 space-y-6">
            {{-- Meta --}}
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="space-y-2">
                    <flux:label>{{ __('app.customer') }}</flux:label>

                    @if ($this->selectedParty)
                        <div class="flex items-center justify-between gap-2 rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                            <flux:text>{{ $this->partyName($this->selectedParty) }}</flux:text>
                            <flux:button type="button" size="xs" variant="ghost" color="red" icon="x-mark" wire:click="clearParty" />
                        </div>
                    @else
                        <flux:select variant="combobox" :filter="false" wire:model.live="form.customer_party_ref" placeholder="{{ __('app.search_customer') }}">
                            <x-slot name="input">
                                <flux:select.input wire:model.live.debounce.400ms="partySearch" placeholder="{{ __('app.search_customer') }}" />
                            </x-slot>

                            @foreach ($this->partyResults as $party)
                                <flux:select.option value="{{ $party->PartyId }}" wire:key="party-{{ $party->PartyId }}">
                                    {{ $this->partyName($party) }}
                                </flux:select.option>
                            @endforeach

                            @canany(['accounting_party_create', 'accounting_invoice_create'])
                                <flux:select.option.create modal="panels.accounting.invoice.party-create.modal" min-length="0">
                                    {{ __('app.create_customer') }}
                                </flux:select.option.create>
                            @endcanany
                        </flux:select>
                    @endif

                    @error('form.customer_party_ref')
                        <flux:text class="text-red-500">{{ $message }}</flux:text>
                    @enderror
                </div>

                <div class="space-y-2">
                    <flux:radio.group wire:model.live="form.sale_type_ref" label="{{ __('app.sale_type') }}" variant="segmented">
                        <flux:radio value="1" label="{{ __('app.official') }}" />
                        <flux:radio value="2" label="{{ __('app.unofficial') }}" />
                    </flux:radio.group>
                    @error('form.sale_type_ref')
                        <flux:text class="text-red-500">{{ $message }}</flux:text>
                    @enderror
                </div>

                <x-date-select wire:model="form.date" :label="__('app.date')" :required="true" />

                <flux:input wire:model="form.description" label="{{ __('app.description') }}" />
            </div>

            {{-- Line items table --}}
            <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                <table class="w-full min-w-[720px] text-sm">
                    <thead class="bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                        <tr>
                            <th class="px-3 py-2 text-right font-medium w-10">#</th>
                            <th class="px-3 py-2 text-right font-medium">{{ __('app.item') }}</th>
                            <th class="px-3 py-2 text-right font-medium w-24">{{ __('app.quantity') }}</th>
                            <th class="px-3 py-2 text-right font-medium w-36">{{ __('app.fee') }}</th>
                            <th class="px-3 py-2 text-right font-medium w-32">{{ __('app.discount') }}</th>
                            <th class="px-3 py-2 text-right font-medium w-32">{{ __('app.line_total') }}</th>
                            <th class="px-3 py-2 text-center font-medium w-24">{{ __('app.options') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($form->items as $index => $row)
                            @php
                                $itemRef = $row['item_ref'] ? (int) $row['item_ref'] : null;
                                $selectedItem = $itemRef ? ($this->selectedItems[$itemRef] ?? null) : null;
                                $showSearch = ! $selectedItem || $itemSearchRow === $index;
                                $qty = (float) str_replace(',', '', (string) ($row['quantity'] ?? 0));
                                $fee = (float) str_replace(',', '', (string) ($row['fee'] ?? 0));
                                $discount = (float) str_replace(',', '', (string) ($row['discount'] ?? 0));
                                $lineTotal = ($qty * $fee) - $discount;
                            @endphp
                            <tr class="align-top" wire:key="invoice-row-{{ $index }}">
                                <td class="px-3 py-3 text-zinc-500">{{ $index + 1 }}</td>
                                <td class="px-3 py-3 space-y-2 min-w-[260px]">
                                    @if ($selectedItem && ! $showSearch)
                                        <div class="flex items-center gap-2 rounded-lg border border-zinc-200 px-2 py-1.5 dark:border-zinc-700">
                                            @if ($selectedItem->image?->Thumbnail)
                                                <img
                                                    src="data:image/jpeg;base64,{{ base64_encode($selectedItem->image->Thumbnail) }}"
                                                    alt=""
                                                    class="size-9 rounded object-cover shadow-sm"
                                                >
                                            @else
                                                <div class="flex size-9 items-center justify-center rounded bg-zinc-100 dark:bg-zinc-800">
                                                    <flux:icon name="package" variant="micro" class="text-zinc-400" />
                                                </div>
                                            @endif
                                            <div class="min-w-0 flex-1">
                                                <div class="truncate font-medium">{{ $selectedItem->Title }}</div>
                                                <div class="truncate text-xs text-zinc-500">{{ $selectedItem->Code }}</div>
                                            </div>
                                            <flux:button type="button" size="xs" variant="ghost" wire:click="clearItem({{ $index }})">
                                                {{ __('app.change') }}
                                            </flux:button>
                                        </div>
                                    @endif

                                    @if ($showSearch)
                                        <flux:select
                                            variant="combobox"
                                            :filter="false"
                                            wire:model.live="form.items.{{ $index }}.item_ref"
                                            placeholder="{{ __('app.search_item') }}"
                                        >
                                            <x-slot name="input">
                                                <flux:select.input
                                                    wire:model.live.debounce.400ms="itemSearch"
                                                    wire:focus="openItemSearch({{ $index }})"
                                                    placeholder="{{ __('app.search_item') }}"
                                                />
                                            </x-slot>

                                            @foreach ($this->itemResults as $item)
                                                <flux:select.option value="{{ $item['id'] }}" wire:key="item-{{ $index }}-{{ $item['id'] }}">
                                                    <div class="flex items-start gap-2 py-0.5">
                                                        @if ($item['thumbnail'])
                                                            <img
                                                                src="data:image/jpeg;base64,{{ base64_encode($item['thumbnail']) }}"
                                                                alt=""
                                                                class="size-10 shrink-0 rounded object-cover"
                                                            >
                                                        @else
                                                            <div class="flex size-10 shrink-0 items-center justify-center rounded bg-zinc-100 dark:bg-zinc-800">
                                                                <flux:icon name="photo" variant="micro" class="text-zinc-400" />
                                                            </div>
                                                        @endif
                                                        <div class="min-w-0 flex-1 space-y-1">
                                                            <div class="truncate font-medium">{{ $item['title'] }}</div>
                                                            <div class="text-xs text-zinc-500">{{ $item['code'] }}</div>
                                                            <div class="grid grid-cols-3 gap-x-2 text-[11px]">
                                                                <div>
                                                                    <span class="text-zinc-500">{{ __('app.stock') }}:</span>
                                                                    <span class="font-semibold text-teal-600 dark:text-teal-400">{{ number_format($item['stock']) }}</span>
                                                                </div>
                                                                <div>
                                                                    <span class="text-zinc-500">{{ __('app.last_sale_price') }}:</span>
                                                                    <span class="font-semibold text-sky-600 dark:text-sky-400">{{ number_format($item['last_sale_price']) }}</span>
                                                                </div>
                                                                <div>
                                                                    <span class="text-zinc-500">{{ __('app.last_purchase_price') }}:</span>
                                                                    <span class="font-semibold text-amber-600 dark:text-amber-400">{{ number_format($item['last_purchase_price']) }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    @endif

                                    <flux:input
                                        wire:model.blur="form.items.{{ $index }}.description"
                                        placeholder="{{ __('app.description') }}"
                                        size="sm"
                                    />

                                    @error("form.items.$index.item_ref")
                                        <flux:text class="text-red-500">{{ $message }}</flux:text>
                                    @enderror
                                </td>
                                <td class="px-3 py-3">
                                    <flux:input wire:model.live.blur="form.items.{{ $index }}.quantity" type="number" step="any" min="0" size="sm" />
                                </td>
                                <td class="px-3 py-3">
                                    <flux:input
                                        wire:model.live.blur="form.items.{{ $index }}.fee"
                                        size="sm"
                                        mask:dynamic="$money($input, '.', ',', 0)"
                                    />
                                </td>
                                <td class="px-3 py-3">
                                    <flux:input
                                        wire:model.live.blur="form.items.{{ $index }}.discount"
                                        size="sm"
                                        mask:dynamic="$money($input, '.', ',', 0)"
                                    />
                                </td>
                                <td class="px-3 py-3">
                                    <div class="pt-2 font-semibold tabular-nums">{{ number_format($lineTotal) }}</div>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex items-center justify-center gap-1 pt-1">
                                        <flux:tooltip content="{{ __('app.remove_row') }}">
                                            <flux:button
                                                type="button"
                                                size="xs"
                                                variant="primary"
                                                color="red"
                                                icon="minus"
                                                icon:variant="outline"
                                                wire:click="removeRow({{ $index }})"
                                                :disabled="count($form->items) <= 1"
                                            />
                                        </flux:tooltip>
                                        <flux:tooltip content="{{ __('app.insert_row') }}">
                                            <flux:button
                                                type="button"
                                                size="xs"
                                                variant="primary"
                                                color="teal"
                                                icon="plus"
                                                icon:variant="outline"
                                                wire:click="insertRowAfter({{ $index }})"
                                            />
                                        </flux:tooltip>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Totals --}}
            <div class="flex flex-col gap-4 border-t border-zinc-200 pt-4 dark:border-zinc-700 md:flex-row md:items-end md:justify-between">
                <div class="ms-auto w-full max-w-sm space-y-2 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-zinc-500">{{ __('app.price') }}</span>
                        <span class="font-medium tabular-nums">{{ number_format($this->totals['price']) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-zinc-500">{{ __('app.discount') }}</span>
                        <span class="font-medium tabular-nums">{{ number_format($this->totals['discount']) }}</span>
                    </div>
                    <flux:separator variant="subtle" />
                    <div class="flex items-center justify-between">
                        <flux:heading size="sm">{{ __('app.net_amount') }}</flux:heading>
                        <flux:heading size="lg" class="tabular-nums">{{ number_format($this->totals['net']) }}</flux:heading>
                    </div>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <flux:button type="button" variant="ghost" href="{{ route('panels.accounting.invoice.index') }}" wire:navigate icon="arrow-right">
                    {{ __('app.back') }}
                </flux:button>
                <flux:button type="button" variant="primary" color="sky" icon="eye" wire:click="openPreview">
                    {{ __('app.preview') }}
                </flux:button>
                <flux:button type="submit" variant="primary" color="orange" class="w-full sm:w-auto" icon="save" wire:loading.attr="disabled">
                    {{ __('app.save') }}
                </flux:button>
            </div>
        </div>
    </div>
</div>

{{-- Create party flyout --}}
<flux:modal name="panels.accounting.invoice.party-create.modal" class="md:w-96" flyout position="right">
    <form wire:submit="createParty" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.create_customer') }}</flux:heading>
            <flux:text class="mt-2">{{ __('app.create_customer_description') }}</flux:text>
        </div>

        <flux:input wire:model="newPartyName" label="{{ __('app.first_name') }}" required />
        <flux:input wire:model="newPartyLastName" label="{{ __('app.last_name') }}" />
        <flux:input wire:model="newPartyMobile" label="{{ __('app.mobile') }}" required />

        <flux:button type="submit" variant="primary" color="orange" class="w-full" icon="save">
            {{ __('app.save') }}
        </flux:button>
    </form>
</flux:modal>

{{-- Preview flyout --}}
<flux:modal name="panels.accounting.invoice.preview.modal" class="md:w-2/3 lg:w-1/2" flyout position="right">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.invoice_preview') }}</flux:heading>
            <flux:text class="mt-2">{{ __('app.invoice_preview_description') }}</flux:text>
        </div>

        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700 space-y-4">
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <flux:text size="sm">{{ __('app.customer') }}</flux:text>
                    <flux:heading size="sm">{{ $this->partyName($this->selectedParty) }}</flux:heading>
                </div>
                <div>
                    <flux:text size="sm">{{ __('app.sale_type') }}</flux:text>
                    <flux:heading size="sm">
                        {{ (int) $form->sale_type_ref === 1 ? __('app.official') : __('app.unofficial') }}
                    </flux:heading>
                </div>
                <div>
                    <flux:text size="sm">{{ __('app.date') }}</flux:text>
                    <flux:heading size="sm">{{ $form->date }}</flux:heading>
                </div>
                <div>
                    <flux:text size="sm">{{ __('app.issuer') }}</flux:text>
                    <flux:heading size="sm">{{ $issuer }}</flux:heading>
                </div>
                @if (filled($form->description))
                    <div class="col-span-2">
                        <flux:text size="sm">{{ __('app.description') }}</flux:text>
                        <flux:heading size="sm">{{ $form->description }}</flux:heading>
                    </div>
                @endif
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('app.item') }}</flux:table.column>
                    <flux:table.column>{{ __('app.quantity') }}</flux:table.column>
                    <flux:table.column>{{ __('app.fee') }}</flux:table.column>
                    <flux:table.column>{{ __('app.discount') }}</flux:table.column>
                    <flux:table.column>{{ __('app.line_total') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($form->items as $index => $row)
                        @php
                            $selectedItem = $row['item_ref'] ? ($this->selectedItems[(int) $row['item_ref']] ?? null) : null;
                            $qty = (float) str_replace(',', '', (string) ($row['quantity'] ?? 0));
                            $fee = (float) str_replace(',', '', (string) ($row['fee'] ?? 0));
                            $discount = (float) str_replace(',', '', (string) ($row['discount'] ?? 0));
                            $lineTotal = ($qty * $fee) - $discount;
                        @endphp
                        <flux:table.row wire:key="preview-row-{{ $index }}">
                            <flux:table.cell>
                                <div class="flex items-center gap-2">
                                    @if ($selectedItem?->image?->Thumbnail)
                                        <img
                                            src="data:image/jpeg;base64,{{ base64_encode($selectedItem->image->Thumbnail) }}"
                                            class="size-8 rounded object-cover"
                                            alt=""
                                        >
                                    @endif
                                    <span>{{ $selectedItem?->Title ?? '-' }}</span>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>{{ number_format($qty) }}</flux:table.cell>
                            <flux:table.cell>{{ number_format($fee) }}</flux:table.cell>
                            <flux:table.cell>{{ number_format($discount) }}</flux:table.cell>
                            <flux:table.cell>{{ number_format($lineTotal) }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>

            <div class="space-y-1 text-sm">
                <div class="flex justify-between"><span>{{ __('app.price') }}</span><span>{{ number_format($this->totals['price']) }}</span></div>
                <div class="flex justify-between"><span>{{ __('app.discount') }}</span><span>{{ number_format($this->totals['discount']) }}</span></div>
                <div class="flex justify-between font-bold"><span>{{ __('app.net_amount') }}</span><span>{{ number_format($this->totals['net']) }}</span></div>
            </div>
        </div>
    </div>
</flux:modal>
