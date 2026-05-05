<flux:modal name="review-user-task-create-modal" flyout position="right" class="md:w-[600px] space-y-6">
    <div>
        <flux:heading size="lg">{{ __('app.create_task') }}</flux:heading>
        <flux:subheading>{{ __('app.workspace_review.create_task_for_user') }} {{ $user->name }}</flux:subheading>
    </div>

    <x-workspace.task.create
        submit="create"
        :show-cancel="false"
        form-class="space-y-6"
    />
</flux:modal>
