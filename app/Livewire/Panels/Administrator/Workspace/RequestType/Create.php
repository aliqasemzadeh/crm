<?php

namespace App\Livewire\Panels\Administrator\Workspace\RequestType;

use Livewire\Component;

use App\Models\Workspace\RequestType;
use Flux\Flux;

class Create extends Component
{
    public $name = '';
    public $title = '';
    public $description = '';
    public $is_active = true;

    protected $rules = [
        'name' => 'required|string|unique:workspace_request_types,name',
        'title' => 'required|string',
        'description' => 'nullable|string',
        'is_active' => 'boolean',
    ];

    public function create()
    {
        $this->validate();

        RequestType::create([
            'name' => $this->name,
            'title' => $this->title,
            'description' => $this->description,
            'is_active' => $this->is_active,
        ]);

        $this->reset(['name', 'title', 'description', 'is_active']);

        $this->dispatch('refresh-request-types');
        $this->dispatch('modal-close', name: 'panels.administrator.workspace.request-type.create.modal');
        $this->dispatch('toast', heading: __('app.create'), text: __('app.created_successfully'), variant: 'success');
    }

    public function render()
    {
        return view('livewire.panels.administrator.workspace.request-type.create');
    }
}
