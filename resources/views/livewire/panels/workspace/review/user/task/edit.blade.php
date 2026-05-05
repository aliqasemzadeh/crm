<flux:modal name="review-user-task-edit-modal" flyout position="right" class="md:w-[600px] space-y-6">
    <div>
        <flux:heading size="lg">{{ __('app.edit_task') }}</flux:heading>
        <flux:subheading>{{ __('app.workspace_review.edit_task_for_user') }} {{ $user->name }}</flux:subheading>
    </div>

    @if($task)
        <x-workspace.task.edit
            submit="update"
            :show-cancel="false"
            form-class="space-y-6"
        />
    @endif
</flux:modal>
