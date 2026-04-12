<?php

namespace App\Livewire\Panels\Administrator\Workspace\RequestType;

use Livewire\Component;

use App\Models\Workspace\RequestType;

class Edit extends Component
{
    public ?RequestType $requestType = null;
    public $name = '';
    public $title = '';
    public $description = '';
    public $is_active = true;
    public $schema_text = '';

    protected $listeners = [
        'panels.administrator.workspace.request-type.edit.assign-data' => 'assignData',
    ];

    public function assignData($id)
    {
        $this->requestType = RequestType::findOrFail($id);
        $this->name = $this->requestType->name;
        $this->title = $this->requestType->title;
        $this->description = $this->requestType->description;
        $this->is_active = $this->requestType->is_active;
        $this->schema_text = json_encode($this->requestType->schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $this->dispatch('modal-show', name: 'panels.administrator.workspace.request-type.edit.modal');
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|unique:workspace_request_types,name,' . $this->requestType?->id,
            'title' => 'required|string',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'schema_text' => 'nullable|json',
        ];
    }

    public function edit()
    {
        $this->validate();

        $this->requestType->update([
            'name' => $this->name,
            'title' => $this->title,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'schema' => json_decode($this->schema_text, true),
        ]);

        $this->dispatch('refresh-request-types');
        $this->dispatch('modal-close', name: 'panels.administrator.workspace.request-type.edit.modal');
        $this->dispatch('toast', heading: __('app.update'), text: __('app.updated_successfully'), variant: 'success');
    }

    public function render()
    {
        return view('livewire.panels.administrator.workspace.request-type.edit');
    }
}
