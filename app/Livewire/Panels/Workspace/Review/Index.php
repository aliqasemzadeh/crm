<?php

namespace App\Livewire\Panels\Workspace\Review;

use App\Models\Workspace\Task;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $selectedTask;
    public $review_note;
    public $approval_status;

    public $sortBy = 'done_at';
    public $sortDirection = 'asc';

    public function sort($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    #[\Livewire\Attributes\Computed]
    public function tasks()
    {
        return Task::where('status', 'done')
            ->tap(fn ($query) => $this->sortBy ? $query->orderBy($this->sortBy, $this->sortDirection) : $query)
            ->paginate(10);
    }

    public function openReviewModal(Task $task)
    {
        $this->selectedTask = $task;
        $this->review_note = $task->review_note;
        $this->approval_status = $task->approval_status === 'none' ? 'pending' : $task->approval_status;

        $this->js('$flux.modal("review-task").show()');
    }

    public function submitReview()
    {
        $this->validate([
            'approval_status' => 'required|in:approved,rejected',
            'review_note' => 'nullable|string',
        ]);

        $updateData = [
            'approval_status' => $this->approval_status,
            'review_note' => $this->review_note,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ];

        // اگر رد شد، وضعیت را به doing برگردان تا دوباره اصلاح شود
        if ($this->approval_status === 'rejected') {
            $updateData['status'] = 'doing';
        }

        $this->selectedTask->update($updateData);

        $this->js('$flux.modal("review-task").close()');

        Flux::toast(__('app.task.review.success_message'));
    }

    #[On('panels.workspace.review.index.render')]
    #[Layout('layouts.panels.workspace')]
    public function render()
    {
        return view('livewire.panels.workspace.review.index');
    }
}
