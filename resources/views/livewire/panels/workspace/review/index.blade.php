<div class="space-y-6">
    <livewire:panels.workspace.review.task.activity />
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('app.task.review.list_title') }}</flux:heading>

        <flux:radio.group wire:model.live="filter" variant="segmented">
            <flux:radio value="pending" :label="__('app.task.review.filter_pending')" />
            <flux:radio value="all" :label="__('app.task.review.filter_all')" />
        </flux:radio.group>
    </div>

    <flux:table :paginate="$this->tasks">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortBy === 'title'" :direction="$sortDirection" wire:click="sort('title')">{{ __('app.task.review.title') }}</flux:table.column>
            <flux:table.column>{{ __('app.task.review.task_users') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">{{ __('app.task.review.created_at') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'done_at'" :direction="$sortDirection" wire:click="sort('done_at')">{{ __('app.task.review.done_at') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'approval_status'" :direction="$sortDirection" wire:click="sort('approval_status')">{{ __('app.task.review.approval_status') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->tasks as $task)
                <flux:table.row :key="$task->id">
                    <flux:table.cell class="font-medium">
                        <span class="cursor-pointer hover:underline" wire:click="$dispatch('panels.workspace.review.task.activity.assign-data', { id: {{ $task->id }} })">
                            {{ $task->title }}
                        </span>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:avatar.group>
                            @foreach($task->users as $user)
                                <flux:avatar circle size="xs" :name="$user->name" :tooltip="$user->name" />
                            @endforeach
                        </flux:avatar.group>
                    </flux:table.cell>
                    <flux:table.cell>{{ \Morilog\Jalali\Jalalian::fromCarbon($task->created_at)->format('%Y-%m-%d %H:%M') }}</flux:table.cell>
                    <flux:table.cell>{{ $task->done_at ? \Morilog\Jalali\Jalalian::fromCarbon($task->done_at)->format('%Y-%m-%d %H:%M') : '-' }}</flux:table.cell>
                    <flux:table.cell>
                        @if($task->approval_status === 'approved')
                            <flux:badge variant="pill" color="green" size="sm">{{ __('app.task.review.status_approved') }}</flux:badge>
                        @elseif($task->approval_status === 'rejected')
                            <flux:badge variant="pill" color="red" size="sm">{{ __('app.task.review.status_rejected') }}</flux:badge>
                        @elseif($task->approval_status === 'pending')
                            <flux:badge variant="pill" color="yellow" size="sm">{{ __('app.task.review.status_pending') }}</flux:badge>
                        @else
                            <flux:badge variant="pill" size="sm">{{ __('app.task.review.status_unknown') }}</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex gap-2">
                            <flux:button wire:click="$dispatch('panels.workspace.review.task.activity.assign-data', { id: {{ $task->id }} })" variant="subtle" size="sm" icon="chat-bubble-left-right">{{ __('app.task.activity.modal_title') }}</flux:button>
                            <flux:button wire:click="openReviewModal({{ $task->id }})" variant="primary" color="lime" size="sm" icon="magnifying-glass">{{ __('app.task.review.review_button') }}</flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal name="review-task" variant="floating" class="md:w-lg">
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
