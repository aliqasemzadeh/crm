<?php

namespace App\Livewire\Panels\Workspace\Dashboard\Task;

use App\Models\Workspace\Task;
use App\Models\Workspace\TaskChecklist;
use Livewire\Component;

class Checklist extends Component
{
    public Task $task;

    public function toggleChecklist(int $checklistId): void
    {
        $checklist = TaskChecklist::query()
            ->where('task_id', $this->task->id)
            ->findOrFail($checklistId);

        $isDone = ! $checklist->is_done;
        $checklist->update([
            'is_done' => $isDone,
            'done_at' => $isDone ? now() : null,
            'done_by' => $isDone ? auth()->id() : null,
        ]);

        $this->task->load('checklists');
    }

    public function render()
    {
        return view('livewire.panels.workspace.dashboard.task.checklist');
    }
}
