<?php

namespace App\Livewire\Panels\ServiceCenter\Repair\Concerns;

use App\Ai\Agents\RepairAdmissionAssistant;
use App\Models\ServiceCenter\Repair;
use Flux\Flux;
use Illuminate\Support\Facades\Log;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Laravel\Ai\Transcription;
use Throwable;

trait HandlesRepairAdmissionAssist
{
    use WithFileUploads;

    /** @var array<int, TemporaryUploadedFile> */
    public array $devicePhotos = [];

    /** @var array<int, TemporaryUploadedFile> */
    public array $attachments = [];

    public mixed $voiceAudio = null;

    public string $voiceTargetField = 'device_problem';

    public string $aiPrompt = '';

    public string $mode = 'manual';

    /** @var array<int, int> */
    public array $removedMediaIds = [];

    public bool $isTranscribing = false;

    public bool $isGeneratingAi = false;

    /**
     * @return array<string, mixed>
     */
    protected function mediaValidationRules(): array
    {
        return [
            'devicePhotos' => ['nullable', 'array'],
            'devicePhotos.*' => ['image', 'max:10240'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:20480'],
            'voiceAudio' => ['nullable', 'file', 'max:20480'],
            'aiPrompt' => ['nullable', 'string', 'max:10000'],
            'voiceTargetField' => ['in:device_problem,device_accessories,device_description,admission_description,aiPrompt'],
        ];
    }

    public function removePendingDevicePhoto(int $index): void
    {
        if (! isset($this->devicePhotos[$index])) {
            return;
        }

        $photos = $this->devicePhotos;
        unset($photos[$index]);
        $this->devicePhotos = array_values($photos);
    }

    public function removePendingAttachment(int $index): void
    {
        if (! isset($this->attachments[$index])) {
            return;
        }

        $files = $this->attachments;
        unset($files[$index]);
        $this->attachments = array_values($files);
    }

    public function removeExistingMedia(int $mediaId): void
    {
        if (! in_array($mediaId, $this->removedMediaIds, true)) {
            $this->removedMediaIds[] = $mediaId;
        }

        if (method_exists($this, 'existingDevicePhotos')) {
            unset($this->existingDevicePhotos);
        }

        if (method_exists($this, 'existingAttachments')) {
            unset($this->existingAttachments);
        }
    }

    public function undoRemoveExistingMedia(int $mediaId): void
    {
        $this->removedMediaIds = array_values(array_filter(
            $this->removedMediaIds,
            fn (int $id): bool => $id !== $mediaId
        ));

        if (method_exists($this, 'existingDevicePhotos')) {
            unset($this->existingDevicePhotos);
        }

        if (method_exists($this, 'existingAttachments')) {
            unset($this->existingAttachments);
        }
    }

    public function transcribeAudio(?string $field = null): void
    {
        $target = $field ?: $this->voiceTargetField;

        $allowed = ['device_problem', 'device_accessories', 'device_description', 'admission_description', 'aiPrompt'];

        if (! in_array($target, $allowed, true)) {
            $target = 'device_problem';
        }

        if (! $this->voiceAudio instanceof TemporaryUploadedFile) {
            Flux::toast(__('app.repair_voice_missing'), variant: 'danger');

            return;
        }

        $this->isTranscribing = true;

        try {
            $this->validate([
                'voiceAudio' => ['required', 'file', 'max:20480'],
            ]);

            // Keep a copy in attachments so the original audio is stored with the repair.
            $this->attachments[] = $this->voiceAudio;

            $transcript = Transcription::fromUpload($this->voiceAudio)
                ->language('fa')
                ->timeout(180)
                ->generate(
                    'gap',
                    (string) config('ai.providers.gap.models.transcription.default', 'whisper-1')
                );

            $text = trim((string) $transcript);

            if ($text === '') {
                Flux::toast(__('app.repair_transcription_empty'), variant: 'warning');

                return;
            }

            $current = trim((string) ($this->{$target} ?? ''));
            $this->{$target} = $current === '' ? $text : $current."\n".$text;

            Flux::toast(__('app.repair_transcription_done'));
        } catch (Throwable $e) {
            Log::warning('Repair admission transcription failed', [
                'message' => $e->getMessage(),
            ]);
            Flux::toast(__('app.repair_transcription_failed'), variant: 'danger');
        } finally {
            $this->voiceAudio = null;
            $this->isTranscribing = false;
        }
    }

    public function generateWithAi(): void
    {
        $prompt = trim($this->aiPrompt);

        if ($prompt === '') {
            Flux::toast(__('app.repair_ai_prompt_required'), variant: 'warning');

            return;
        }

        $this->isGeneratingAi = true;

        try {
            $response = (new RepairAdmissionAssistant)->prompt(
                $prompt,
                provider: 'gap',
                model: config('ai.default_model'),
                timeout: 120,
            );

            $this->applyAiAdmissionFields([
                'owner_name' => (string) ($response['owner_name'] ?? ''),
                'owner_organization' => (string) ($response['owner_organization'] ?? ''),
                'owner_mobile' => (string) ($response['owner_mobile'] ?? ''),
                'owner_email' => (string) ($response['owner_email'] ?? ''),
                'owner_national_code' => (string) ($response['owner_national_code'] ?? ''),
                'owner_address' => (string) ($response['owner_address'] ?? ''),
                'warranty_type' => (string) ($response['warranty_type'] ?? ''),
                'warranty_date' => (string) ($response['warranty_date'] ?? ''),
                'device_type' => (string) ($response['device_type'] ?? ''),
                'device_brand' => (string) ($response['device_brand'] ?? ''),
                'device_model' => (string) ($response['device_model'] ?? ''),
                'device_serial_number' => (string) ($response['device_serial_number'] ?? ''),
                'device_problem' => (string) ($response['device_problem'] ?? ''),
                'device_accessories' => (string) ($response['device_accessories'] ?? ''),
                'device_description' => (string) ($response['device_description'] ?? ''),
                'admission_description' => (string) ($response['admission_description'] ?? ''),
            ]);
            $this->mode = 'manual';

            Flux::toast(__('app.repair_ai_filled'));
        } catch (Throwable $e) {
            Log::warning('Repair admission AI fill failed', [
                'message' => $e->getMessage(),
            ]);
            Flux::toast(__('app.repair_ai_failed'), variant: 'danger');
        } finally {
            $this->isGeneratingAi = false;
        }
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    protected function applyAiAdmissionFields(array $fields): void
    {
        $map = [
            'owner_name',
            'owner_organization',
            'owner_mobile',
            'owner_email',
            'owner_national_code',
            'owner_address',
            'warranty_type',
            'warranty_date',
            'device_type',
            'device_brand',
            'device_model',
            'device_serial_number',
            'device_problem',
            'device_accessories',
            'device_description',
            'admission_description',
        ];

        foreach ($map as $key) {
            if (! array_key_exists($key, $fields)) {
                continue;
            }

            $value = is_string($fields[$key]) ? trim($fields[$key]) : '';

            if ($value === '') {
                continue;
            }

            if ($key === 'warranty_type') {
                $normalized = strtolower($value);
                if (in_array($normalized, ['yes', 'no'], true)) {
                    $this->warranty_type = $normalized;
                }

                continue;
            }

            if ($key === 'warranty_date') {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    $this->warranty_date = $value;
                }

                continue;
            }

            $this->{$key} = $value;
        }
    }

    protected function syncRepairMedia(Repair $repair): void
    {
        foreach ($this->removedMediaIds as $mediaId) {
            $media = $repair->media()->whereKey($mediaId)->first();
            $media?->delete();
        }

        foreach ($this->devicePhotos as $photo) {
            if (! $photo instanceof TemporaryUploadedFile) {
                continue;
            }

            $repair->addMedia($photo->getRealPath())
                ->usingFileName($photo->getClientOriginalName())
                ->preservingOriginal()
                ->toMediaCollection('device_photos');
        }

        foreach ($this->attachments as $file) {
            if (! $file instanceof TemporaryUploadedFile) {
                continue;
            }

            $repair->addMedia($file->getRealPath())
                ->usingFileName($file->getClientOriginalName())
                ->preservingOriginal()
                ->toMediaCollection('attachments');
        }

        if (is_string($this->device_problem_file) && str_starts_with($this->device_problem_file, 'data:image')) {
            $repair->addMediaFromBase64($this->device_problem_file)
                ->usingFileName('whiteboard-'.now()->format('YmdHis').'.png')
                ->toMediaCollection('attachments');
        }

        $this->devicePhotos = [];
        $this->attachments = [];
        $this->removedMediaIds = [];
    }

    protected function resetAssistState(): void
    {
        $this->devicePhotos = [];
        $this->attachments = [];
        $this->voiceAudio = null;
        $this->voiceTargetField = 'device_problem';
        $this->aiPrompt = '';
        $this->mode = 'manual';
        $this->removedMediaIds = [];
        $this->isTranscribing = false;
        $this->isGeneratingAi = false;
    }
}
