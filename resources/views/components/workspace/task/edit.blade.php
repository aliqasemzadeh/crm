@props([
    'submit' => 'save',
    'saveLabel' => __('app.task.save'),
    'showCancel' => true,
    'cancelLabel' => __('app.task.cancel'),
    'cancelRoute' => route('panels.workspace.task.index'),
    'formClass' => 'space-y-6 max-w-2xl',
])

<x-workspace.task.create
    :submit="$submit"
    :save-label="$saveLabel"
    :show-cancel="$showCancel"
    :cancel-label="$cancelLabel"
    :cancel-route="$cancelRoute"
    :form-class="$formClass"
/>