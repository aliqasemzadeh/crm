@include('livewire.panels.service-center.repair.partials.voice-recorder-script')

<flux:modal name="panels.service-center.repair.admission.modal" class="w-full md:w-2/3" flyout position="right">
    <div class="space-y-6 pb-20">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <flux:heading size="lg">
                    {{ $mode === 'ai' ? __('app.repair_ai_admission') : __('app.admission') }}
                </flux:heading>
                <flux:text class="mt-2">
                    {{ $mode === 'ai' ? __('app.repair_ai_admission_help') : __('app.admission_description') }}
                </flux:text>
            </div>

            <div class="flex gap-2">
                @if ($mode === 'manual')
                    <flux:tooltip content="{{ __('app.repair_ai_admission') }}">
                        <flux:button type="button" size="sm" variant="primary" color="violet" icon="sparkles" wire:click="switchToAiMode" />
                    </flux:tooltip>
                @endif
            </div>
        </div>

        @if ($mode === 'ai')
            @include('livewire.panels.service-center.repair.partials.ai-composer')
        @else
            <form wire:submit="admission" method="post" class="space-y-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="space-y-4">
                        <flux:heading size="sm">{{ __('app.owner_information') }}</flux:heading>

                        <flux:field>
                            <flux:label>{{ __('app.owner_mobile') }}</flux:label>
                            <flux:autocomplete wire:model="owner_mobile" size="sm">
                                @foreach($this->owners as $ownerMobile)
                                    <flux:autocomplete.item @click="$wire.fillOwnerByMobile('{{ $ownerMobile }}')">{{ $ownerMobile }}</flux:autocomplete.item>
                                @endforeach
                            </flux:autocomplete>
                            <flux:error name="owner_mobile" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('app.owner_name') }}</flux:label>
                            <flux:autocomplete wire:model="owner_name" size="sm">
                                @foreach($this->ownerNames as $ownerName)
                                    <flux:autocomplete.item @click="$wire.fillOwnerByName('{{ $ownerName }}')">{{ $ownerName }}</flux:autocomplete.item>
                                @endforeach
                            </flux:autocomplete>
                            <flux:error name="owner_name" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('app.owner_organization') }}</flux:label>
                            <flux:input wire:model="owner_organization" type="text" size="sm" />
                            <flux:error name="owner_organization" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('app.owner_email') }}</flux:label>
                            <flux:input wire:model="owner_email" type="email" size="sm" />
                            <flux:error name="owner_email" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('app.owner_national_code') }}</flux:label>
                            <flux:input wire:model="owner_national_code" type="text" size="sm" />
                            <flux:error name="owner_national_code" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('app.owner_address') }}</flux:label>
                            <flux:textarea wire:model="owner_address" rows="3" size="sm" />
                            <flux:error name="owner_address" />
                        </flux:field>
                    </div>

                    <div class="space-y-4">
                        <flux:heading size="sm">{{ __('app.device_information') }}</flux:heading>

                        <flux:field>
                            <flux:label>{{ __('app.device_type') }}</flux:label>
                            <flux:autocomplete wire:model="device_type" size="sm" type="text">
                                @foreach($this->types as $type)
                                    <flux:autocomplete.item @click="$wire.fillDeviceType('{{ $type }}')">{{ $type }}</flux:autocomplete.item>
                                @endforeach
                            </flux:autocomplete>
                            <flux:error name="device_type" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('app.device_brand') }}</flux:label>
                            <flux:autocomplete wire:model="device_brand" size="sm" type="text">
                                @foreach($this->brands as $brand)
                                    <flux:autocomplete.item @click="$wire.fillDeviceBrand('{{ $brand }}')">{{ $brand }}</flux:autocomplete.item>
                                @endforeach
                            </flux:autocomplete>
                            <flux:error name="device_brand" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('app.device_model') }}</flux:label>
                            <flux:autocomplete wire:model="device_model" size="sm" type="text">
                                @foreach($this->models as $model)
                                    <flux:autocomplete.item>{{ $model }}</flux:autocomplete.item>
                                @endforeach
                            </flux:autocomplete>
                            <flux:error name="device_model" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('app.device_serial_number') }}</flux:label>
                            <flux:input wire:model="device_serial_number" type="text" size="sm" />
                            <flux:error name="device_serial_number" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('app.warranty_type') }}</flux:label>
                            <flux:select wire:model="warranty_type" placeholder="{{ __('app.select_warranty_status') }}" size="sm" searchable>
                                <flux:select.option value="no">{{ __('app.warranty_no') }}</flux:select.option>
                                <flux:select.option value="yes">{{ __('app.warranty_yes') }}</flux:select.option>
                            </flux:select>
                            <flux:error name="warranty_type" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('app.warranty_date') }}</flux:label>
                            <flux:input wire:model="warranty_date" type="date" size="sm" />
                            <flux:error name="warranty_date" />
                        </flux:field>
                    </div>
                </div>

                @include('livewire.panels.service-center.repair.partials.device-photos')

                <div class="space-y-4">
                    <flux:heading size="sm">{{ __('app.repair_more_details') }}</flux:heading>

                    <div class="space-y-2">
                        <flux:composer
                            wire:model="device_problem"
                            :label="__('app.device_problem')"
                            rows="3"
                            max-rows="8"
                            variant="input"
                            :placeholder="__('app.device_problem')"
                        >
                            <x-slot name="actionsLeading">
                                <flux:tooltip content="{{ __('app.repair_attach_files') }}">
                                    <flux:button
                                        type="button"
                                        size="sm"
                                        variant="subtle"
                                        icon="paper-clip"
                                        wire:click="$upload('attachments', { accept: 'image/*,audio/*,.pdf,.doc,.docx,.zip', multiple: true })"
                                    />
                                </flux:tooltip>
                                @include('livewire.panels.service-center.repair.partials.whiteboard')
                            </x-slot>
                            <x-slot name="actionsTrailing">
                                <div x-data="repairVoiceRecorder('device_problem')" class="inline-flex">
                                    <flux:tooltip content="{{ __('app.repair_voice_to_text') }}">
                                        <flux:button
                                            type="button"
                                            size="sm"
                                            variant="filled"
                                            icon="microphone"
                                            ::class="recording ? 'ring-2 ring-red-500 text-red-600' : ''"
                                            x-bind:disabled="$wire.isTranscribing"
                                            @click="toggle()"
                                        />
                                    </flux:tooltip>
                                </div>
                            </x-slot>
                        </flux:composer>
                        <flux:error name="device_problem" />
                    </div>

                    @include('livewire.panels.service-center.repair.partials.description-composer', [
                        'field' => 'device_accessories',
                        'label' => __('app.device_accessories'),
                    ])

                    @include('livewire.panels.service-center.repair.partials.description-composer', [
                        'field' => 'device_description',
                        'label' => __('app.device_description'),
                    ])

                    @include('livewire.panels.service-center.repair.partials.description-composer', [
                        'field' => 'admission_description',
                        'label' => __('app.admission_description_label'),
                    ])
                </div>

                @include('livewire.panels.service-center.repair.partials.pending-files')

                <div
                    wire:loading
                    wire:target="transcribeAudio,devicePhotos,attachments"
                    class="text-sm text-zinc-500"
                >
                    {{ __('app.repair_uploading') }}
                </div>

                <div class="sticky bottom-0 z-10 -mx-2 border-t border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900 md:static md:border-0 md:bg-transparent md:p-0 dark:md:bg-transparent">
                    <flux:button type="submit" class="w-full" variant="primary" color="orange" wire:loading.attr="disabled">
                        {{ __('app.admission') }}
                    </flux:button>
                </div>
            </form>
        @endif
    </div>
</flux:modal>
