<div>
    <livewire:panels.workspace.instruction.create />
    <livewire:panels.workspace.instruction.edit />
    <livewire:panels.workspace.instruction.file.index />

    <flux:main>
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
            <flux:heading size="xl">{{ __('app.instructions') }}</flux:heading>
            <div class="flex flex-wrap items-center gap-4">
                <flux:select wire:model.live="filter_status" placeholder="{{ __('app.status') }}" class="w-40">
                    <flux:select.option value="">{{ __('app.all') }}</flux:select.option>
                    <flux:select.option value="draft">{{ __('app.instruction_status.draft') }}</flux:select.option>
                    <flux:select.option value="finalized">{{ __('app.instruction_status.finalized') }}</flux:select.option>
                </flux:select>

                <flux:button wire:click="$dispatch('instruction-create-modal')" icon="plus" variant="primary">
                    {{ __('app.create_instruction') }}
                </flux:button>
            </div>
        </div>

        <div class="space-y-4">
            @forelse ($instructions as $instruction)
                <div class="p-4 bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 flex justify-between items-center">
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center gap-2">
                            <flux:heading size="lg">{{ $instruction->title }}</flux:heading>
                            <flux:badge size="sm" :color="match($instruction->status) {
                                'draft' => 'zinc',
                                'finalized' => 'green',
                                default => 'zinc',
                            }">{{ __('app.instruction_status.' . $instruction->status) }}</flux:badge>
                        </div>
                        <flux:subheading>{!! $instruction->body !!}</flux:subheading>
                        <div class="text-xs text-zinc-500 flex items-center gap-2">
                            @if($instruction->user?->avatar)
                                <flux:avatar src="{{ $instruction->user->getAvatarUrl() }}" size="xs" />
                            @else
                                <flux:avatar name="{{ $instruction->user?->name }}" color="auto" size="xs" />
                            @endif
                            {{ $instruction->user->name }}
                            <span class="mx-1">•</span>
                            <flux:icon.calendar variant="micro" />
                            {{ \Morilog\Jalali\Jalalian::fromDateTime($instruction->created_at)->format('Y/m/d H:i') }}
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <flux:button wire:click="$dispatch('instruction-files-modal', { id: {{ $instruction->id }} })" icon="paper-clip" variant="ghost" size="sm">
                            {{ __('app.files') }}
                        </flux:button>

                        @if($instruction->user_id === auth()->id() || auth()->user()->can('administrator_workspace_instruction_manage'))
                            <flux:button wire:click="$dispatch('instruction-edit-modal', { id: {{ $instruction->id }} })" icon="pencil-square" variant="ghost" size="sm" />

                            <flux:modal.trigger name="delete-instruction-{{ $instruction->id }}">
                                <flux:button icon="trash" variant="ghost" size="sm" color="red" />
                            </flux:modal.trigger>

                            <flux:modal name="delete-instruction-{{ $instruction->id }}" class="min-w-[22rem]">
                                <form class="space-y-6" wire:submit="deleteInstruction({{ $instruction->id }})">
                                    <div>
                                        <flux:heading size="lg">{{ __('app.delete') }}</flux:heading>
                                        <flux:subheading>{{ __('app.are_you_sure') }}</flux:subheading>
                                    </div>

                                    <div class="flex gap-2">
                                        <flux:spacer />
                                        <flux:modal.close>
                                            <flux:button variant="ghost">{{ __('app.cancel') }}</flux:button>
                                        </flux:modal.close>
                                        <flux:button type="submit" variant="danger">{{ __('app.delete') }}</flux:button>
                                    </div>
                                </form>
                            </flux:modal>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-12 text-center border-2 border-dashed border-zinc-200 dark:border-zinc-700 rounded-xl">
                    <flux:icon.document-text class="mx-auto h-12 w-12 text-zinc-400" />
                    <flux:heading class="mt-4">{{ __('app.no_instructions_found') }}</flux:heading>
                    <div class="mt-6">
                        <flux:button wire:click="$dispatch('instruction-create-modal')" icon="plus" variant="primary">
                            {{ __('app.create_instruction') }}
                        </flux:button>
                    </div>
                </div>
            @endforelse

            <div class="mt-4">
                {{ $instructions->links() }}
            </div>
        </div>
    </flux:main>
</div>
