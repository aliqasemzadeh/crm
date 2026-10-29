<div class="space-y-4 rounded-xl border border-violet-200 bg-violet-50/50 p-4 dark:border-violet-800 dark:bg-violet-950/30">
    <div>
        <flux:heading size="sm">{{ __('app.repair_ai_admission') }}</flux:heading>
        <flux:text class="mt-1 text-sm">{{ __('app.repair_ai_admission_help') }}</flux:text>
    </div>

    <form wire:submit="generateWithAi" class="space-y-3">
        <flux:composer
            wire:model="aiPrompt"
            submit="enter"
            rows="4"
            max-rows="10"
            label="{{ __('app.repair_ai_prompt') }}"
            label:sr-only
            placeholder="{{ __('app.repair_ai_prompt_placeholder') }}"
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
            </x-slot>

            <x-slot name="actionsTrailing">
                <div x-data="repairVoiceRecorder('aiPrompt')" class="inline-flex">
                    <flux:tooltip content="{{ __('app.repair_voice_to_text') }}">
                        <flux:button
                            type="button"
                            size="sm"
                            variant="filled"
                            icon="microphone"
                            x-bind:class="recording ? 'ring-2 ring-red-500 text-red-600' : ''"
                            wire:loading.attr="disabled"
                            wire:target="transcribeAudio"
                            @click="toggle()"
                        />
                    </flux:tooltip>
                </div>

                <flux:tooltip content="{{ __('app.repair_ai_generate') }}">
                    <flux:button
                        type="submit"
                        size="sm"
                        variant="primary"
                        color="violet"
                        icon="sparkles"
                        wire:loading.attr="disabled"
                        wire:target="generateWithAi"
                    />
                </flux:tooltip>
            </x-slot>
        </flux:composer>

        <flux:error name="aiPrompt" />
    </form>

    <div wire:loading wire:target="generateWithAi,transcribeAudio" class="text-sm text-violet-700 dark:text-violet-300">
        {{ __('app.repair_ai_working') }}
    </div>

    @include('livewire.panels.service-center.repair.partials.pending-files')

    <flux:button
        type="button"
        variant="ghost"
        size="sm"
        class="w-full"
        wire:click="$dispatch('panels.service-center.repair.admission.switch-to-manual')"
    >
        {{ __('app.repair_switch_to_manual') }}
    </flux:button>
</div>
