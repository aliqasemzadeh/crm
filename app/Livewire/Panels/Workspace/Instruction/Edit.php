<?php

namespace App\Livewire\Panels\Workspace\Instruction;

use App\Models\Workspace\Instruction;
use App\Models\Workspace\InstructionFile;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class Edit extends Component
{
    use WithFileUploads;

    public ?Instruction $instruction = null;

    public $title;
    public $body;
    public $status;

    public $files = [];
    public $file_description;

    #[On('instruction-edit-modal')]
    public function showModal($id)
    {
        $this->instruction = Instruction::findOrFail($id);

        if ($this->instruction->user_id !== auth()->id() && !auth()->user()->can('administrator_workspace_instruction_manage')) {
            Flux::toast(__('app.unauthorized'), variant: 'danger');
            return;
        }

        $this->title = $this->instruction->title;
        $this->body = $this->instruction->body;
        $this->status = $this->instruction->status;

        Flux::modal('instruction-edit-modal')->show();
    }

    protected $rules = [
        'title' => 'required|min:3',
        'body' => 'required',
        'status' => 'required|in:draft,finalized',
    ];

    public function save()
    {
        if (!$this->instruction || ($this->instruction->user_id !== auth()->id() && !auth()->user()->can('administrator_workspace_instruction_manage'))) {
            return;
        }

        $this->validate();

        $this->instruction->update([
            'title' => $this->title,
            'body' => $this->body,
            'status' => $this->status,
        ]);

        $this->dispatch('instruction-saved');
        Flux::toast(__('app.instruction_updated'));
    }

    public function render()
    {
        return view('livewire.panels.workspace.instruction.edit');
    }
}
