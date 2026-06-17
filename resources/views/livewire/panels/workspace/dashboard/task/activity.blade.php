<div>
    <x-workspace.task.activity
        modal-name="panels.workspace.dashboard.task.activity.modal"
        :task="$task"
        submit="send"
        composer-placeholder="{{ __('app.task.activity.write_report') }}"
        file-input-id="activity-files"
        body-model="body"
        files-model="files"
        :files="$files"
        toggle-checklist-method="toggleChecklist"
    />

    <flux:modal name="panels.workspace.dashboard.task.activity.checklists.modal" flyout position="right" class="md:w-[520px]">
        @if($task)
            <div class="space-y-5">
                <x-workspace.task.checklist
                    :checklist-items="$checklistItems"
                    add-modal-name="panels.workspace.dashboard.task.activity.checklist.create.modal"
                />
            </div>
        @endif
    </flux:modal>

    <flux:modal name="panels.workspace.dashboard.task.activity.checklist.create.modal" flyout position="right" class="md:w-[420px]">
        <form wire:submit="addChecklistItem" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('app.task.checklist_create_title') }}</flux:heading>
                <flux:subheading>{{ __('app.task.checklist_create_description') }}</flux:subheading>
            </div>

            <flux:input wire:model="newChecklistTitle" :label="__('app.task.checklist_item_title')" />

            <div class="w-full">
                <flux:button type="submit" variant="primary" color="teal" class="w-full">
                    {{ __('app.task.save') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="panels.workspace.dashboard.task.activity.checklist.edit.modal" flyout position="right" class="md:w-[420px]">
        <form wire:submit="updateChecklistItem" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('app.task.checklist_edit_title') }}</flux:heading>
            </div>

            <flux:input wire:model="editingChecklistTitle" :label="__('app.task.checklist_item_title')" />

            <div class="w-full">
                <flux:button type="submit" variant="primary" color="teal" class="w-full">
                    {{ __('app.task.save') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

</div>
