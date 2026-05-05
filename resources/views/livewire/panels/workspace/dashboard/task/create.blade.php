<flux:modal name="panels.workspace.dashboard.task.create.modal" class="md:w-96" flyout position="right">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.create_task') }}</flux:heading>
            <flux:text class="mt-2">{{ __('app.create_task_description') }}</flux:text>
        </div>
        <x-workspace.task.create submit="create" />
    </div>
</flux:modal>
