<?php

namespace App\Livewire\Panels\Workspace\Instruction;

use App\Models\Workspace\Instruction;
use App\Models\Workspace\InstructionFile;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class Edit extends Component
{
    use WithFileUploads;

    public Instruction $instruction;

    public $title;
    public $body;
    public $status;

    public $files = [];
    public $file_description;

    protected $rules = [
        'title' => 'required|min:3',
        'body' => 'required',
        'status' => 'required|in:draft,finalized',
    ];

    public function mount(Instruction $instruction)
    {
        $this->instruction = $instruction;
        $this->title = $instruction->title;
        $this->body = $instruction->body;
        $this->status = $instruction->status;
    }

    public function save()
    {
        if ($this->instruction->user_id !== auth()->id()) {
            return;
        }

        $this->validate();

        $this->instruction->update([
            'title' => $this->title,
            'body' => $this->body,
            'status' => $this->status,
        ]);

        session()->flash('status', __('app.instruction_updated'));
    }

    public function uploadFiles()
    {
        $this->validate([
            'files.*' => 'required|max:10240', // 10MB
        ]);

        foreach ($this->files as $file) {
            $path = $file->store('workspace/instructions/' . $this->instruction->id, 'public');

            InstructionFile::create([
                'instruction_id' => $this->instruction->id,
                'user_id' => auth()->id(),
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_description' => $this->file_description,
            ]);
        }

        $this->files = [];
        $this->file_description = '';
        $this->instruction->refresh();

        session()->flash('status', __('app.files_uploaded'));
    }

    public function deleteFile(InstructionFile $file)
    {
        if ($file->user_id !== auth()->id()) {
            return;
        }

        Storage::disk('public')->delete($file->file_path);
        $file->delete();
        $this->instruction->refresh();
    }

    #[Layout('layouts.panels.workspace')]
    public function render()
    {
        return view('livewire.panels.workspace.instruction.edit');
    }
}
