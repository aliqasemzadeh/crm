<?php

namespace App\Livewire\Panels\Administrator\Workspace\RequestType;

use Livewire\Component;
use App\Models\Workspace\RequestType;

class Schema extends Component
{
    public ?RequestType $requestType = null;
    public $fields = [];
    public $show_raw = false;
    public $schema_text = '';

    protected $listeners = [
        'panels.administrator.workspace.request-type.schema.assign-data' => 'assignData',
    ];

    public function assignData($id)
    {
        $this->requestType = RequestType::findOrFail($id);
        $schema = $this->requestType->schema ?? ['fields' => []];
        $fields = $schema['fields'] ?? [];

        // Prepare fields for the visual editor
        foreach ($fields as &$field) {
            if (isset($field['options']) && is_array($field['options'])) {
                $field['options'] = implode(',', $field['options']);
            }
        }

        $this->fields = $fields;
        $this->schema_text = json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $this->dispatch('modal-show', name: 'panels.administrator.workspace.request-type.schema.modal');
    }

    public function addField()
    {
        $this->fields[] = [
            'name' => '',
            'type' => 'text',
            'title' => '',
            'required' => false,
            'options' => '',
        ];
    }

    public function removeField($index)
    {
        unset($this->fields[$index]);
        $this->fields = array_values($this->fields);
    }

    public function moveUp($index)
    {
        if ($index > 0) {
            $temp = $this->fields[$index - 1];
            $this->fields[$index - 1] = $this->fields[$index];
            $this->fields[$index] = $temp;
        }
    }

    public function moveDown($index)
    {
        if ($index < count($this->fields) - 1) {
            $temp = $this->fields[$index + 1];
            $this->fields[$index + 1] = $this->fields[$index];
            $this->fields[$index] = $temp;
        }
    }

    public function toggleRaw()
    {
        if ($this->show_raw) {
            // Sync from raw to fields
            try {
                $decoded = json_decode($this->schema_text, true);
                if (isset($decoded['fields'])) {
                    $fields = $decoded['fields'];
                    foreach ($fields as &$field) {
                        if (isset($field['options']) && is_array($field['options'])) {
                            $field['options'] = implode(',', $field['options']);
                        }
                    }
                    $this->fields = $fields;
                }
            } catch (\Exception $e) {
                // Ignore invalid JSON when toggling back
            }
        } else {
            // Sync from fields to raw
            $processedFields = $this->fields;
            foreach ($processedFields as &$field) {
                if (isset($field['type']) && $field['type'] === 'select' && isset($field['options']) && is_string($field['options'])) {
                    $field['options'] = array_map('trim', explode(',', $field['options']));
                }
            }
            $this->schema_text = json_encode(['fields' => $processedFields], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        $this->show_raw = !$this->show_raw;
    }

    public function save()
    {
        if ($this->show_raw) {
            $this->validate([
                'schema_text' => 'required|json',
            ]);
            $schema = json_decode($this->schema_text, true);
        } else {
            $processedFields = $this->fields;
            foreach ($processedFields as &$field) {
                if (isset($field['type']) && $field['type'] === 'select' && isset($field['options']) && is_string($field['options'])) {
                    $field['options'] = array_map('trim', explode(',', $field['options']));
                } elseif (isset($field['options'])) {
                    unset($field['options']);
                }
            }
            $schema = ['fields' => $processedFields];
        }

        $this->requestType->update([
            'schema' => $schema,
        ]);

        $this->dispatch('toast', heading: __('app.update'), text: __('app.updated_successfully'), variant: 'success');
        $this->dispatch('modal-close', name: 'panels.administrator.workspace.request-type.schema.modal');
    }

    public function render()
    {
        return view('livewire.panels.administrator.workspace.request-type.schema');
    }
}
