<flux:modal name="panels.workspace.dashboard.task.edit.modal" class="md:w-96" flyout position="right">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.edit_task') }}</flux:heading>
            <flux:text class="mt-2">{{ __('app.edit_task_description') }}</flux:text>
        </div>
        <x-workspace.task.edit submit="edit" />
    </div>
</flux:modal>
