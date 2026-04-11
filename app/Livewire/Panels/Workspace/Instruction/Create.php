<?php

namespace App\Livewire\Panels\Workspace\Instruction;

use App\Models\Workspace\Instruction;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

class Create extends Component
{
    public $title;
    public $body;
    public $status = 'draft';

    #[On('instruction-create-modal')]
    public function showModal()
    {
        $this->reset(['title', 'body', 'status']);
        $this->status = 'draft';
        Flux::modal('instruction-create-modal')->show();
    }

    protected $rules = [
        'title' => 'required|min:3',
        'body' => 'required',
        'status' => 'required|in:draft,finalized',
    ];

    public function save()
    {
        $this->validate();

        $instruction = Instruction::create([
            'title' => $this->title,
            'body' => $this->body,
            'status' => $this->status,
            'user_id' => auth()->id(),
        ]);

        $this->dispatch('instruction-saved');
        Flux::modal('instruction-create-modal')->close();
        Flux::toast(__('app.instruction_created'));
    }

    public function render()
    {
        return view('livewire.panels.workspace.instruction.create');
    }
}
