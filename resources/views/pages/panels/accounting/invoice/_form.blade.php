@php
    $issuer = $this->issuerName;
    $invoiceUiPrefix = $invoiceUiPrefix ?? 'panels.accounting.invoice';
    $invoiceRoutePrefix = $invoiceRoutePrefix ?? 'panels.accounting.invoice';
    $partyCreatePermissions = $partyCreatePermissions ?? ['accounting_party_create', 'accounting_invoice_create'];

    $alpineRows = collect($items)->map(function (array $row) {
        return [
            'quantity' => (float) str_replace(',', '', (string) ($row['quantity'] ?? 0)),
            'fee' => (float) str_replace(',', '', (string) ($row['fee'] ?? 0)),
            'discount' => (float) str_replace(',', '', (string) ($row['discount'] ?? 0)),
            'tax' => (float) str_replace(',', '', (string) ($row['tax'] ?? 0)),
            'discountPercent' => '',
            'taxPercent' => '',
        ];
    })->values()->all();
@endphp

<div class="w-full">
    <div class="rounded-xl border border-zinc-300 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900 overflow-hidden">
        <div class="border-b border-zinc-200 bg-zinc-50 px-4 py-4 sm:px-6 dark:border-zinc-700 dark:bg-zinc-800/60">
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

        <div class="p-4 sm:p-6 space-y-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                <div class="space-y-2 lg:col-span-1">
                    <flux:label>{{ __('app.customer') }}</flux:label>

                    @if ($this->selectedParty)
                        <div class="flex items-center justify-between gap-2 rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                            <div class="min-w-0">
                                <flux:text>{{ $this->partyName($this->selectedParty) }}</flux:text>
                            </div>
                            <div class="flex items-center gap-1 shrink-0" wire:sort:ignore>
                                <flux:tooltip content="{{ __('app.party_financial_status') }}">
                                    <flux:button
                                        type="button"
                                        size="xs"
                                        variant="ghost"
                                        color="sky"
                                        icon="wallet"
                                        wire:click="showPartyBalance({{ $this->selectedParty->PartyId }})"
                                    />
                                </flux:tooltip>
                                <flux:button type="button" size="xs" variant="ghost" color="red" icon="x-mark" wire:click="clearParty" />
                            </div>
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

                            @canany($partyCreatePermissions)
                                <flux:select.option.create modal="{{ $invoiceUiPrefix }}.party-create.modal" min-length="0">
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
                </div>

                <x-date-select wire:model="form.date" :label="__('app.date')" :required="true" />

                <flux:input wire:model="form.description" label="{{ __('app.description') }}" />
            </div>

            <flux:accordion transition>
                <flux:accordion.item :heading="__('app.invoice_settings')">
                    <div class="grid grid-cols-1 gap-4 pt-2 md:grid-cols-2 lg:grid-cols-3">
                        <div class="space-y-3">
                            <flux:radio.group wire:model.live="price_mode" label="{{ __('app.price_mode') }}" variant="segmented">
                                <flux:radio value="last_sale" label="{{ __('app.last_sale_price') }}" />
                                <flux:radio value="site" label="{{ __('app.site_price') }}" />
                            </flux:radio.group>
                            <flux:button
                                type="button"
                                variant="primary"
                                color="rose"
                                icon="arrow-path"
                                class="w-full"
                                wire:click="applyPriceMode"
                            >
                                {{ __('app.apply_price_mode') }}
                            </flux:button>
                        </div>

                        <div class="space-y-2">
                            <flux:input
                                wire:model.live.debounce.400ms="tax_percent"
                                type="number"
                                step="any"
                                min="0"
                                label="{{ __('app.tax_percent') }}"
                                class="text-center"
                            />
                        </div>

                        <div class="space-y-2">
                            <flux:select
                                wire:model="form.delivery_location_ref"
                                searchable
                                label="{{ __('app.delivery_location') }}"
                                placeholder="{{ __('app.delivery_location') }}"
                            >
                                @foreach ($this->deliveryLocations as $location)
                                    <flux:select.option value="{{ $location->DeliveryLocationID }}" wire:key="delivery-{{ $location->DeliveryLocationID }}">
                                        {{ $location->Title }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                            @error('form.delivery_location_ref')
                                <flux:text class="text-red-500">{{ $message }}</flux:text>
                            @enderror
                        </div>
                    </div>
                </flux:accordion.item>
            </flux:accordion>

            <div
                wire:ignore
                wire:key="invoice-form-calc-{{ $this->form_revision }}-{{ $this->tax_percent }}-{{ $this->price_mode }}"
                x-data="invoiceFormCalculator({
                    taxPercent: {{ (float) $this->tax_percent }},
                    rows: @js($alpineRows),
                    labels: {
                        rial: @js(__('app.rial')),
                        toman: @js(__('app.toman')),
                        equivalent: @js(__('app.amount_equivalent_prefix')),
                    },
                })"
            >
                <form x-on:submit.prevent="prepareSave($event)" class="space-y-6">
                <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                <table class="w-full min-w-[960px] text-sm">
                    <thead class="bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                        <tr>
                            <th class="px-3 py-2 text-right font-medium w-10">#</th>
                            <th class="px-3 py-2 text-right font-medium">{{ __('app.item') }}</th>
                            <th class="px-3 py-2 text-center font-medium w-24">{{ __('app.quantity') }}</th>
                            <th class="px-3 py-2 text-center font-medium w-36">{{ __('app.fee') }}</th>
                            <th class="px-3 py-2 text-center font-medium w-36">{{ __('app.discount') }}</th>
                            <th class="px-3 py-2 text-center font-medium w-36">{{ __('app.tax') }}</th>
                            <th class="px-3 py-2 text-center font-medium w-32">{{ __('app.line_total') }}</th>
                            <th class="px-3 py-2 text-center font-medium w-32">{{ __('app.options') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700" wire:sort="sortItems">
                        @foreach ($items as $index => $row)
                            @php
                                $rowId = $row['row_id'] ?? ('row-'.$index);
                                $itemRef = $row['item_ref'] ? (int) $row['item_ref'] : null;
                                $selectedItem = $itemRef ? ($this->selectedItems[$itemRef] ?? null) : null;
                            @endphp
                            <tr
                                class="align-top"
                                wire:key="invoice-row-{{ $rowId }}"
                                wire:sort:item="{{ $rowId }}"
                            >
                                <td class="px-3 py-3 text-zinc-500">{{ $index + 1 }}</td>
                                <td class="px-3 py-3 min-w-[240px]" wire:sort:ignore>
                                    @if ($selectedItem)
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
                                    @else
                                        <button
                                            type="button"
                                            wire:click="openItemSearch({{ $index }})"
                                            class="flex w-full items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-start text-sm text-zinc-500 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800"
                                        >
                                            <flux:icon name="magnifying-glass" variant="micro" class="text-zinc-400" />
                                            <span>{{ __('app.search_item') }}</span>
                                        </button>
                                    @endif

                                    <div
                                        class="mt-1 text-[11px] leading-tight text-zinc-500 dark:text-zinc-400"
                                        x-show="parse(rows[{{ $index }}].fee) > 0"
                                        x-cloak
                                    >
                                        <div x-text="rialWords(rows[{{ $index }}].fee)"></div>
                                        <div x-text="tomanEquivalentWords(rows[{{ $index }}].fee)"></div>
                                    </div>

                                    @error("items.$index.item_ref")
                                        <flux:text class="text-red-500">{{ $message }}</flux:text>
                                    @enderror
                                </td>
                                <td class="px-3 py-3" wire:sort:ignore>
                                    <flux:input
                                        type="text"
                                        inputmode="decimal"
                                        size="sm"
                                        class="text-center [&_input]:text-center"
                                        mask:dynamic="$money($input, '.', ',', 4)"
                                        x-model="rows[{{ $index }}].quantity"
                                        x-on:input="onBaseChange({{ $index }})"
                                        x-on:blur="formatMoneyField({{ $index }}, 'quantity', 4); syncBaseRow({{ $index }})"
                                    />
                                </td>
                                <td class="px-3 py-3" wire:sort:ignore>
                                    <flux:input
                                        size="sm"
                                        class="text-center [&_input]:text-center"
                                        mask:dynamic="$money($input, '.', ',', 0)"
                                        x-model="rows[{{ $index }}].fee"
                                        x-on:input="onBaseChange({{ $index }})"
                                        x-on:blur="formatMoneyField({{ $index }}, 'fee', 0); syncBaseRow({{ $index }})"
                                    />
                                </td>
                                <td class="px-3 py-3" wire:sort:ignore>
                                    <div class="flex items-start gap-1">
                                        <flux:input
                                            size="sm"
                                            class="text-center [&_input]:text-center"
                                            mask:dynamic="$money($input, '.', ',', 0)"
                                            x-model="rows[{{ $index }}].discount"
                                            x-on:input="onBaseChange({{ $index }})"
                                            x-on:blur="formatMoneyField({{ $index }}, 'discount', 0); syncBaseRow({{ $index }})"
                                        />
                                        <flux:dropdown>
                                            <flux:tooltip content="{{ __('app.discount_percent') }}">
                                                <flux:button type="button" size="xs" variant="ghost" color="amber" icon="calculator" icon:variant="outline" />
                                            </flux:tooltip>
                                            <flux:popover class="w-48 space-y-2 p-3">
                                                <flux:input
                                                    type="number"
                                                    step="any"
                                                    min="0"
                                                    size="sm"
                                                    class="text-center [&_input]:text-center"
                                                    placeholder="%"
                                                    x-model="rows[{{ $index }}].discountPercent"
                                                />
                                                <flux:button
                                                    type="button"
                                                    variant="primary"
                                                    color="amber"
                                                    class="w-full"
                                                    size="sm"
                                                    x-on:click="applyDiscountPercent({{ $index }})"
                                                >
                                                    {{ __('app.apply_percent') }}
                                                </flux:button>
                                            </flux:popover>
                                        </flux:dropdown>
                                    </div>
                                </td>
                                <td class="px-3 py-3" wire:sort:ignore>
                                    <div class="flex items-start gap-1">
                                        <flux:input
                                            size="sm"
                                            class="text-center [&_input]:text-center"
                                            mask:dynamic="$money($input, '.', ',', 0)"
                                            x-model="rows[{{ $index }}].tax"
                                            x-on:input="recalcTotals()"
                                            x-on:blur="formatMoneyField({{ $index }}, 'tax', 0); syncRow({{ $index }}, 'tax')"
                                        />
                                        <flux:dropdown>
                                            <flux:tooltip content="{{ __('app.tax_percent') }}">
                                                <flux:button type="button" size="xs" variant="ghost" color="sky" icon="calculator" icon:variant="outline" />
                                            </flux:tooltip>
                                            <flux:popover class="w-48 space-y-2 p-3">
                                                <flux:input
                                                    type="number"
                                                    step="any"
                                                    min="0"
                                                    size="sm"
                                                    class="text-center [&_input]:text-center"
                                                    placeholder="%"
                                                    x-model="rows[{{ $index }}].taxPercent"
                                                    x-bind:placeholder="String(taxPercent)"
                                                />
                                                <flux:button
                                                    type="button"
                                                    variant="primary"
                                                    color="sky"
                                                    class="w-full"
                                                    size="sm"
                                                    x-on:click="applyTaxPercent({{ $index }})"
                                                >
                                                    {{ __('app.apply_percent') }}
                                                </flux:button>
                                            </flux:popover>
                                        </flux:dropdown>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <div class="pt-2 font-semibold tabular-nums" x-text="formatNumber(lineTotal({{ $index }}))"></div>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex items-center justify-center gap-1 pt-1">
                                        <button type="button" wire:sort:handle class="cursor-grab p-1 text-zinc-400 hover:text-zinc-600">
                                            <flux:icon name="grip-vertical" variant="micro" />
                                        </button>
                                        <div class="flex items-center gap-1" wire:sort:ignore>
                                            <flux:tooltip content="{{ __('app.refresh_row') }}">
                                                <flux:button
                                                    type="button"
                                                    size="xs"
                                                    variant="primary"
                                                    color="sky"
                                                    icon="arrow-path"
                                                    icon:variant="outline"
                                                    wire:click="refreshRow({{ $index }})"
                                                    :disabled="! $itemRef"
                                                />
                                            </flux:tooltip>
                                            <flux:tooltip content="{{ __('app.remove_row') }}">
                                                <flux:button
                                                    type="button"
                                                    size="xs"
                                                    variant="primary"
                                                    color="red"
                                                    icon="minus"
                                                    icon:variant="outline"
                                                    wire:click="removeRow({{ $index }})"
                                                    :disabled="count($items) <= 1"
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
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>

                <div class="flex flex-col gap-4 border-t border-zinc-200 pt-4 dark:border-zinc-700 md:flex-row md:items-end md:justify-between">
                    <div class="ms-auto w-full max-w-sm space-y-2 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-zinc-500">{{ __('app.price') }}</span>
                            <span class="font-medium tabular-nums" x-text="formatNumber(totals.price)"></span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-zinc-500">{{ __('app.discount') }}</span>
                            <span class="font-medium tabular-nums" x-text="formatNumber(totals.discount)"></span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-zinc-500">{{ __('app.tax') }}</span>
                            <span class="font-medium tabular-nums" x-text="formatNumber(totals.tax)"></span>
                        </div>
                        <flux:separator variant="subtle" />
                        <div class="flex items-center justify-between">
                            <flux:heading size="sm">{{ __('app.net_amount') }}</flux:heading>
                            <flux:heading size="lg" class="tabular-nums" x-text="formatNumber(totals.net)"></flux:heading>
                        </div>
                        <div
                            class="space-y-0.5 text-xs leading-tight text-zinc-500 dark:text-zinc-400"
                            x-show="parse(totals.net) > 0"
                            x-cloak
                        >
                            <div x-text="rialWords(totals.net)"></div>
                            <div x-text="tomanEquivalentWords(totals.net)"></div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <flux:button type="button" variant="ghost" href="{{ route($invoiceRoutePrefix.'.index') }}" wire:navigate icon="arrow-right">
                        {{ __('app.back') }}
                    </flux:button>
                    <flux:button type="button" variant="primary" color="sky" icon="eye" x-on:click="preparePreview()">
                        {{ __('app.preview') }}
                    </flux:button>
                    <flux:button type="submit" variant="primary" color="orange" class="w-full sm:w-auto" icon="save" wire:loading.attr="disabled" wire:target="save,commitClientRows">
                        {{ __('app.save') }}
                    </flux:button>
                </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    window.invoiceFormCalculator = function (config) {
        const labels = config.labels || { rial: 'ریال', toman: 'تومان', equivalent: 'معادل' };

        const ones = ['', 'یک', 'دو', 'سه', 'چهار', 'پنج', 'شش', 'هفت', 'هشت', 'نه'];
        const teens = ['ده', 'یازده', 'دوازده', 'سیزده', 'چهارده', 'پانزده', 'شانزده', 'هفده', 'هجده', 'نوزده'];
        const tens = ['', '', 'بیست', 'سی', 'چهل', 'پنجاه', 'شصت', 'هفتاد', 'هشتاد', 'نود'];
        const hundreds = ['', 'صد', 'دویست', 'سیصد', 'چهارصد', 'پانصد', 'ششصد', 'هفتصد', 'هشتصد', 'نهصد'];
        const scales = ['', 'هزار', 'میلیون', 'میلیارد', 'تریلیون'];

        function threeDigitsToWords(n) {
            n = Math.floor(n);

            if (n === 0) {
                return '';
            }

            const parts = [];
            const h = Math.floor(n / 100);
            const rem = n % 100;

            if (h > 0) {
                parts.push(hundreds[h]);
            }

            if (rem >= 10 && rem <= 19) {
                parts.push(teens[rem - 10]);
            } else {
                const t = Math.floor(rem / 10);
                const o = rem % 10;

                if (t > 0) {
                    parts.push(tens[t]);
                }

                if (o > 0) {
                    parts.push(ones[o]);
                }
            }

            return parts.join(' و ');
        }

        function toPersianWords(value) {
            let number = Math.floor(Math.abs(Number(value) || 0));

            if (number === 0) {
                return 'صفر';
            }

            const parts = [];
            let scaleIndex = 0;

            while (number > 0 && scaleIndex < scales.length) {
                const chunk = number % 1000;

                if (chunk > 0) {
                    const chunkWords = threeDigitsToWords(chunk);
                    const scale = scales[scaleIndex];
                    parts.unshift(scale ? `${chunkWords} ${scale}` : chunkWords);
                }

                number = Math.floor(number / 1000);
                scaleIndex++;
            }

            return parts.join(' و ');
        }

        return {
            taxPercent: Number(config.taxPercent) || 0,
            labels,
            rows: (config.rows || []).map((row) => ({
                quantity: row.quantity ?? 0,
                fee: row.fee ?? 0,
                discount: row.discount ?? 0,
                tax: row.tax ?? 0,
                discountPercent: row.discountPercent ?? '',
                taxPercent: row.taxPercent ?? '',
            })),
            totals: { price: 0, discount: 0, tax: 0, net: 0 },

            init() {
                this.rows.forEach((row, index) => {
                    this.formatMoneyField(index, 'quantity', 4);
                    this.formatMoneyField(index, 'fee', 0);
                    this.formatMoneyField(index, 'discount', 0);
                    this.formatMoneyField(index, 'tax', 0);
                });
                this.recalcTotals();
            },

            parse(value) {
                if (typeof value === 'number') {
                    return Number.isFinite(value) ? value : 0;
                }

                return parseFloat(String(value ?? 0).replace(/,/g, '')) || 0;
            },

            formatNumber(value, decimals = null) {
                const number = this.parse(value);

                if (decimals === 0 || (decimals === null && Math.abs(number - Math.round(number)) < 0.0000001)) {
                    return Math.round(number).toLocaleString('en-US');
                }

                const maxDecimals = decimals === null ? 4 : decimals;

                return number
                    .toLocaleString('en-US', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: maxDecimals,
                    })
                    .replace(/(\.\d*?[1-9])0+$/, '$1')
                    .replace(/\.0+$/, '');
            },

            formatMoneyField(index, field, decimals = 0) {
                const row = this.rows[index];

                if (! row) {
                    return;
                }

                row[field] = this.formatNumber(row[field], decimals);
            },

            rialWords(value) {
                const amount = Math.round(this.parse(value));

                if (amount <= 0) {
                    return '';
                }

                return `${toPersianWords(amount)} ${this.labels.rial}`;
            },

            tomanEquivalentWords(value) {
                const toman = Math.floor(this.parse(value) / 10);

                if (toman <= 0) {
                    return '';
                }

                return `${this.labels.equivalent} ${toPersianWords(toman)} ${this.labels.toman}`;
            },

            lineTotal(index) {
                const row = this.rows[index];

                if (! row) {
                    return 0;
                }

                return (this.parse(row.quantity) * this.parse(row.fee)) - this.parse(row.discount) + this.parse(row.tax);
            },

            onBaseChange(index) {
                this.recalculateTax(index);
                this.recalcTotals();
            },

            recalculateTax(index) {
                const row = this.rows[index];

                if (! row) {
                    return;
                }

                const base = Math.max((this.parse(row.quantity) * this.parse(row.fee)) - this.parse(row.discount), 0);
                row.tax = this.formatNumber(Math.round(base * (this.parse(this.taxPercent) / 100)), 0);
            },

            applyDiscountPercent(index) {
                const row = this.rows[index];

                if (! row) {
                    return;
                }

                const percent = this.parse(row.discountPercent);
                row.discount = this.formatNumber(
                    Math.round(this.parse(row.quantity) * this.parse(row.fee) * percent / 100),
                    0
                );
                this.onBaseChange(index);
                this.syncRow(index, 'discount');
                this.syncRow(index, 'tax');
            },

            applyTaxPercent(index) {
                const row = this.rows[index];

                if (! row) {
                    return;
                }

                const percent = row.taxPercent === '' || row.taxPercent === null
                    ? this.parse(this.taxPercent)
                    : this.parse(row.taxPercent);
                const base = Math.max((this.parse(row.quantity) * this.parse(row.fee)) - this.parse(row.discount), 0);
                row.tax = this.formatNumber(Math.round(base * percent / 100), 0);
                this.recalcTotals();
                this.syncRow(index, 'tax');
            },

            syncAllRows() {
                if (! this.$wire) {
                    return Promise.resolve();
                }

                const payload = this.rows.map((row) => ({
                    quantity: this.parse(row.quantity),
                    fee: this.parse(row.fee),
                    discount: this.parse(row.discount),
                    tax: this.parse(row.tax),
                }));

                return this.$wire.commitClientRows(payload);
            },

            async preparePreview() {
                await this.syncAllRows();
                await this.$wire.openPreview();
            },

            async prepareSave(event) {
                event.preventDefault();
                await this.syncAllRows();
                await this.$wire.save();
            },

            syncRow(index, field) {
                const row = this.rows[index];

                if (! row || ! this.$wire) {
                    return;
                }

                this.$wire.set(`items.${index}.${field}`, this.parse(row[field]));
            },

            syncBaseRow(index) {
                this.syncRow(index, 'quantity');
                this.syncRow(index, 'fee');
                this.syncRow(index, 'discount');
                this.syncRow(index, 'tax');
            },

            recalcTotals() {
                let price = 0;
                let discount = 0;
                let tax = 0;

                this.rows.forEach((row) => {
                    price += this.parse(row.quantity) * this.parse(row.fee);
                    discount += this.parse(row.discount);
                    tax += this.parse(row.tax);
                });

                this.totals = {
                    price,
                    discount,
                    tax,
                    net: price - discount + tax,
                };
            },
        };
    };
</script>
