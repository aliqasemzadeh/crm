<?php

namespace App\Livewire\Panels\ServiceCenter\Assembly;

use App\Ai\Agents\AssemblySerialAssistant;
use App\Enums\AssemblyLogTypeEnum;
use App\Enums\AssemblyStatusEnum;
use App\Models\ServiceCenter\Assembly;
use App\Models\ServiceCenter\AssemblyLog;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class Work extends Component
{
    public ?Assembly $assembly = null;

    public ?string $system_serial = null;

    public ?int $assembled_by_user_id = null;

    public string $status = '';

    public ?string $notes = null;

    public string $aiPrompt = '';

    public bool $isGeneratingAi = false;

    /** @var array<int, array{id: int, title: string, serial_number: string, notes: string}> */
    public array $parts = [];

    #[On('panels.service-center.assembly.work.assign-data')]
    public function assignData(int $id): void
    {
        $this->authorize('service_center_assembly_work');

        $this->assembly = Assembly::query()
            ->with(['order', 'parts', 'assembledBy'])
            ->findOrFail($id);

        $this->system_serial = $this->assembly->system_serial;
        $this->assembled_by_user_id = $this->assembly->assembled_by_user_id;
        $this->status = $this->assembly->status instanceof AssemblyStatusEnum
            ? $this->assembly->status->value
            : (string) $this->assembly->status;
        $this->notes = $this->assembly->notes;
        $this->aiPrompt = '';
        $this->parts = $this->assembly->parts->map(fn ($part) => [
            'id' => $part->id,
            'title' => $part->title,
            'serial_number' => (string) ($part->serial_number ?? ''),
            'notes' => (string) ($part->notes ?? ''),
        ])->values()->all();

        Flux::modal('panels.service-center.assembly.work.modal')->show();
    }

    #[Computed]
    public function statusOptions(): array
    {
        return AssemblyStatusEnum::options();
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'mobile']);
    }

    public function startAssembly(): void
    {
        $this->authorize('service_center_assembly_work');

        if (! $this->assembly) {
            return;
        }

        $this->status = AssemblyStatusEnum::InProgress->value;
        if (! $this->assembled_by_user_id) {
            $this->assembled_by_user_id = Auth::id();
        }

        $this->save(applyStatusImmediately: true);
    }

    public function completeAssembly(): void
    {
        $this->authorize('service_center_assembly_work');

        $this->status = AssemblyStatusEnum::Ready->value;
        $this->save(applyStatusImmediately: true);
    }

    public function generateWithAi(): void
    {
        $this->authorize('service_center_assembly_work');

        $prompt = trim($this->aiPrompt);
        if ($prompt === '' || ! $this->assembly) {
            Flux::toast(__('app.assembly_ai_prompt_required'), variant: 'warning');

            return;
        }

        $this->isGeneratingAi = true;

        try {
            $context = "سیستم #{$this->assembly->unit_number}\nسریال فعلی: {$this->system_serial}\nقطعات:\n";
            foreach ($this->parts as $part) {
                $context .= "- {$part['title']} | سریال: ".($part['serial_number'] ?: 'ندارد')."\n";
            }
            $context .= "\nدرخواست:\n{$prompt}";

            $response = (new AssemblySerialAssistant)->prompt(
                $context,
                provider: 'gap',
                model: config('ai.default_model'),
                timeout: 120,
            );

            $systemSerial = trim((string) ($response['system_serial'] ?? ''));
            if ($systemSerial !== '') {
                $this->system_serial = $systemSerial;
            }

            $notes = trim((string) ($response['notes'] ?? ''));
            if ($notes !== '') {
                $this->notes = $notes;
            }

            $suggestions = $response['suggestions'] ?? [];
            if (is_array($suggestions)) {
                foreach ($suggestions as $suggestion) {
                    $title = trim((string) ($suggestion['part_title'] ?? ''));
                    $serial = trim((string) ($suggestion['suggested_serial'] ?? ''));
                    if ($title === '' || $serial === '') {
                        continue;
                    }
                    foreach ($this->parts as $index => $part) {
                        if ($part['title'] === $title && ($part['serial_number'] ?? '') === '') {
                            $this->parts[$index]['serial_number'] = $serial;
                        }
                    }
                }
            }

            $summary = trim((string) ($response['missing_parts_summary'] ?? ''));
            Flux::toast($summary !== '' ? $summary : __('app.assembly_ai_filled'));
        } catch (Throwable $e) {
            Log::warning('Assembly serial AI failed', ['message' => $e->getMessage()]);
            Flux::toast(__('app.assembly_ai_failed'), variant: 'danger');
        } finally {
            $this->isGeneratingAi = false;
        }
    }

    public function save(bool $applyStatusImmediately = false): void
    {
        $this->authorize('service_center_assembly_work');

        if (! $this->assembly) {
            return;
        }

        $this->validate([
            'system_serial' => ['nullable', 'string', 'max:255'],
            'assembled_by_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'parts.*.serial_number' => ['nullable', 'string', 'max:255'],
            'parts.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $oldAssembler = $this->assembly->assembled_by_user_id;
        $oldStatus = $this->assembly->status instanceof AssemblyStatusEnum
            ? $this->assembly->status
            : AssemblyStatusEnum::tryFromSafe((string) $this->assembly->status);

        $this->assembly->fill([
            'system_serial' => $this->system_serial ?: null,
            'assembled_by_user_id' => $this->assembled_by_user_id,
            'notes' => $this->notes,
        ]);

        $newStatus = AssemblyStatusEnum::tryFromSafe($this->status);

        if ($applyStatusImmediately || $oldStatus !== $newStatus) {
            $this->assembly->save();
            $this->assembly->applyStatus($newStatus);
        } else {
            $this->assembly->save();
        }

        foreach ($this->parts as $partData) {
            $part = $this->assembly->parts()->whereKey($partData['id'])->first();
            if (! $part) {
                continue;
            }
            $part->update([
                'serial_number' => $partData['serial_number'] !== '' ? $partData['serial_number'] : null,
                'notes' => $partData['notes'] !== '' ? $partData['notes'] : null,
            ]);
        }

        if ($oldAssembler != $this->assembled_by_user_id) {
            $this->assembly->load('assembledBy');

            AssemblyLog::create([
                'assembly_order_id' => $this->assembly->assembly_order_id,
                'assembly_id' => $this->assembly->id,
                'user_id' => Auth::id(),
                'type' => AssemblyLogTypeEnum::Assign,
                'status' => $newStatus->value,
                'description' => __('app.assembled_by').': '.($this->assembly->assembledBy?->name ?? '-'),
            ]);
        }

        AssemblyLog::create([
            'assembly_order_id' => $this->assembly->assembly_order_id,
            'assembly_id' => $this->assembly->id,
            'user_id' => Auth::id(),
            'type' => AssemblyLogTypeEnum::PartSerial,
            'status' => $newStatus->value,
            'description' => __('app.assembly_saved'),
        ]);

        unset($this->users);
        $this->assembly->refresh()->load(['order', 'parts', 'assembledBy']);

        Flux::toast(__('app.assembly_saved'));
        $this->dispatch('panels.service-center.assembly.index.render');
    }

    public function render()
    {
        return view('livewire.panels.service-center.assembly.work');
    }
}
