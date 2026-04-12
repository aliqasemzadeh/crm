<flux:modal name="panels.administrator.workspace.request-type.schema.modal" class="md:w-[50rem]" flyout position="right">
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <flux:heading size="lg">{{ __('app.request_type_schema') }} : {{ $requestType?->title }}</flux:heading>
                <flux:text class="mt-2">{{ __('app.edit_schema_description') }}</flux:text>
            </div>
            <flux:button size="sm" variant="ghost" wire:click="toggleRaw">
                {{ $show_raw ? __('app.visual_editor') : __('app.raw_json') }}
            </flux:button>
        </div>

        @if($show_raw)
            <flux:field>
                <flux:textarea wire:model="schema_text" rows="25" dir="ltr" class="font-mono text-sm" />
                <flux:error name="schema_text" />
            </flux:field>
        @else
            <div class="space-y-4">
                @foreach($fields as $index => $field)
                    <div class="p-4 border border-zinc-200 dark:border-white/10 rounded-lg space-y-4 relative bg-zinc-50/50 dark:bg-white/5">
                        <div class="flex justify-between items-start">
                            <div class="flex gap-2">
                                <flux:button icon="chevron-up" size="xs" variant="ghost" wire:click="moveUp({{ $index }})" :disabled="$index === 0" />
                                <flux:button icon="chevron-down" size="xs" variant="ghost" wire:click="moveDown({{ $index }})" :disabled="$index === count($fields) - 1" />
                            </div>
                            <flux:button icon="trash" size="xs" variant="danger" wire:click="removeField({{ $index }})" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <flux:field>
                                <flux:label>{{ __('app.field_name_en') }}</flux:label>
                                <flux:input wire:model="fields.{{ $index }}.name" placeholder="e.g. amount" />
                            </flux:field>

                            <flux:field>
                                <flux:label>{{ __('app.field_title_fa') }}</flux:label>
                                <flux:input wire:model="fields.{{ $index }}.title" placeholder="مثلا مبلغ" />
                            </flux:field>

                            <flux:field>
                                <flux:label>{{ __('app.field_type') }}</flux:label>
                                <flux:select wire:model="fields.{{ $index }}.type">
                                    <flux:select.option value="text">{{ __('app.field_types.text') }}</flux:select.option>
                                    <flux:select.option value="number">{{ __('app.field_types.number') }}</flux:select.option>
                                    <flux:select.option value="textarea">{{ __('app.field_types.textarea') }}</flux:select.option>
                                    <flux:select.option value="select">{{ __('app.field_types.select') }}</flux:select.option>
                                    <flux:select.option value="date">{{ __('app.field_types.date') }}</flux:select.option>
                                    <flux:select.option value="checkbox">{{ __('app.field_types.checkbox') }}</flux:select.option>
                                </flux:select>
                            </flux:field>

                            <div class="flex items-center pt-6">
                                <flux:checkbox wire:model="fields.{{ $index }}.required" label="{{ __('app.required') }}" />
                            </div>
                        </div>

                        @if(($fields[$index]['type'] ?? '') === 'select')
                            <flux:field>
                                <flux:label>{{ __('app.field_options') }}</flux:label>
                                <flux:input wire:model="fields.{{ $index }}.options" placeholder="pcs,kg,box ({{ __('app.comma_separated') }})" />
                            </flux:field>
                        @endif
                    </div>
                @endforeach

                <flux:button variant="ghost" icon="plus" class="w-full" wire:click="addField">
                    {{ __('app.add_field') }}
                </flux:button>
            </div>
        @endif

        <flux:button variant="primary" class="w-full" wire:click="save">
            {{ __('app.save') }}
        </flux:button>
    </div>
</flux:modal>
