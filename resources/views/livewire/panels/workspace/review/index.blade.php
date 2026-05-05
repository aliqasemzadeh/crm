<div class="space-y-6">
    <livewire:panels.workspace.review.task.activity />
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('app.task.review.list_title') }}</flux:heading>

        <flux:radio.group wire:model.live="filter" variant="segmented">
            <flux:radio value="pending" :label="__('app.task.review.filter_pending')" />
            <flux:radio value="all" :label="__('app.task.review.filter_all')" />
        </flux:radio.group>
    </div>

    <div class="space-y-4">
        @foreach ($this->tasks as $task)
            <flux:card>
                <x-workspace.task.card
                    :task="$task"
                    event-prefix="panels.workspace.review.task"
                    :show-drag-handle="false"
                    :show-edit-action="false"
                    :show-assign-action="false"
                    :show-delete-action="false"
                    :show-checklist-action="false"
                >
                    <flux:tooltip content="{{ __('app.task.review.review_button') }}">
                        <flux:button
                            size="xs"
                            variant="primary"
                            color="lime"
                            icon="magnifying-glass"
                            wire:click="openReviewModal({{ $task->id }})"
                        />
                    </flux:tooltip>
                </x-workspace.task.card>
            </flux:card>
        @endforeach
    </div>

    <div>
        {{ $this->tasks->links() }}
    </div>

    <flux:modal name="review-task" flyout position="right" class="md:w-lg">
        <form wire:submit="submitReview" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('app.task.review.modal_title') }}</flux:heading>
                <flux:subheading>{{ __('app.task.review.modal_subheading') }}</flux:subheading>
            </div>

            @if($selectedTask)
                <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-zinc-500">{{ __('app.task.review.title') }}:</span>
                        <span class="font-medium">{{ $selectedTask->title }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-zinc-500">{{ __('app.task.review.done_time') }}:</span>
                        <span>{{ $selectedTask->done_at ? \Morilog\Jalali\Jalalian::fromCarbon($selectedTask->done_at)->format('%Y-%m-%d %H:%M') : '-' }}</span>
                    </div>
                </div>
            @endif

            <flux:radio.group wire:model="approval_status" :label="__('app.task.review.approval_status')" variant="cards" class="flex-col">
                <flux:radio value="approved" :label="__('app.task.review.approve')" :description="__('app.task.review.approve_description')" />
                <flux:radio value="rejected" :label="__('app.task.review.reject')" :description="__('app.task.review.reject_description')" />
            </flux:radio.group>

            <flux:textarea wire:model="review_note" :label="__('app.task.review.review_note')" :placeholder="__('app.task.review.review_note_placeholder')" />

            <div class="flex items-center justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled" size="sm">{{ __('app.cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary" size="sm">{{ __('app.task.review.submit_review') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
