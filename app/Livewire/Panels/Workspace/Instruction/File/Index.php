<?php

namespace App\Livewire\Panels\Workspace\Instruction\File;

use App\Models\Workspace\Instruction;
use App\Models\Workspace\InstructionFile;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class Index extends Component
{
    use WithFileUploads;

    public ?Instruction $instruction = null;
    public $files = [];
    public $file_description = '';

    #[On('instruction-files-modal')]
    public function showModal($id)
    {
        $this->instruction = Instruction::with('files')->findOrFail($id);
        $this->files = [];
        $this->file_description = '';

        Flux::modal('instruction-files-modal')->show();
    }

    public function uploadFiles()
    {
        if (!$this->instruction) return;

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

        Flux::toast(__('app.files_uploaded'));
    }

    public function deleteFile(InstructionFile $file)
    {
        // Only owner or manager can delete
        if ($file->user_id !== auth()->id() && !auth()->user()->can('administrator_workspace_instruction_manage')) {
            return;
        }

        Storage::disk('public')->delete($file->file_path);
        $file->delete();
        $this->instruction->refresh();

        Flux::toast(__('app.file_deleted'));
    }

    public function render()
    {
        return view('livewire.panels.workspace.instruction.file.index');
    }
}
