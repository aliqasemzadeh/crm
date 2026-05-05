<div>
    <flux:main>
        <div class="mb-6">
            <flux:heading size="xl">{{ __('app.task.create') }}</flux:heading>
        </div>

        <x-workspace.task.create submit="save" :checklist-items="$checklistItems" />
    </flux:main>
</div>
