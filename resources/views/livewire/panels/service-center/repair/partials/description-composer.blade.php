@props([
    'field',
    'label',
    'placeholder' => null,
    'showWhiteboard' => false,
])

<div class="space-y-2">
    <flux:composer
        wire:model="{{ $field }}"
        :label="$label"
        rows="3"
        max-rows="8"
        variant="input"
        :placeholder="$placeholder ?? $label"
    >
        <x-slot name="header">
            @if ($attachments ?? false)
                {{-- pending attachments rendered globally below --}}
            @endif
        </x-slot>

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

            @if ($showWhiteboard)
                {{ $slot }}
            @endif
        </x-slot>

        <x-slot name="actionsTrailing">
            <div
                x-data="repairVoiceRecorder('{{ $field }}')"
                class="inline-flex"
            >
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

    <flux:error name="{{ $field }}" />
</div>
