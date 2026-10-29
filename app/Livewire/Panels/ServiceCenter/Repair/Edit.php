<?php

namespace App\Livewire\Panels\ServiceCenter\Repair;

use App\Livewire\Panels\ServiceCenter\Repair\Concerns\HandlesRepairAdmissionAssist;
use App\Models\ServiceCenter\Repair;
use Flux\Flux;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Edit extends Component
{
    use HandlesRepairAdmissionAssist;

    public Repair $repair;

    public int $id;

    public string $owner_name = '';

    public ?string $owner_organization = null;

    public string $owner_mobile = '';

    public ?string $owner_email = null;

    public ?string $owner_national_code = null;

    public ?string $owner_address = null;

    public ?string $warranty_type = null;

    public ?string $warranty_date = null;

    public string $device_type = '';

    public string $device_brand = '';

    public string $device_model = '';

    public string $device_serial_number = '';

    public ?string $device_problem = null;

    public ?string $device_accessories = null;

    public ?string $device_description = null;

    public ?string $device_problem_file = null;

    public ?string $admission_description = null;

    protected function rules(): array
    {
        return array_merge([
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_organization' => ['nullable', 'string', 'max:255'],
            'owner_mobile' => ['required', 'string', 'max:50', 'ir_mobile'],
            'owner_email' => ['nullable', 'email', 'max:255'],
            'owner_national_code' => ['nullable', 'string', 'max:50', 'ir_national_code'],
            'owner_address' => ['nullable', 'string'],

            'warranty_type' => ['required', 'string', 'in:yes,no'],
            'warranty_date' => ['nullable', 'date'],
            'device_type' => ['required', 'string', 'max:255'],
            'device_brand' => ['required', 'string', 'max:255'],
            'device_model' => ['required', 'string', 'max:255'],
            'device_serial_number' => ['required', 'string', 'max:255'],

            'device_problem' => ['nullable', 'string'],
            'device_problem_file' => ['nullable', 'string'],
            'device_accessories' => ['nullable', 'string'],
            'device_description' => ['nullable', 'string'],

            'admission_description' => ['nullable', 'string'],
        ], $this->mediaValidationRules());
    }

    #[Computed]
    public function types(): array
    {
        return Cache::remember('repair_device_types', 3600, function () {
            return Repair::query()
                ->whereNotNull('device_type')
                ->where('device_type', '!=', '')
                ->distinct()
                ->orderBy('device_type')
                ->pluck('device_type')
                ->filter()
                ->values()
                ->toArray();
        });
    }

    #[Computed]
    public function brands(): array
    {
        return Cache::remember('repair_device_brands', 3600, function () {
            return Repair::query()
                ->whereNotNull('device_brand')
                ->where('device_brand', '!=', '')
                ->distinct()
                ->orderBy('device_brand')
                ->pluck('device_brand')
                ->filter()
                ->values()
                ->toArray();
        });
    }

    #[Computed]
    public function models(): array
    {
        return Cache::remember('repair_device_models', 3600, function () {
            return Repair::query()
                ->whereNotNull('device_model')
                ->where('device_model', '!=', '')
                ->distinct()
                ->orderBy('device_model')
                ->pluck('device_model')
                ->filter()
                ->values()
                ->toArray();
        });
    }

    #[Computed]
    public function owners(): array
    {
        return Cache::remember('repair_owners', 3600, function () {
            return Repair::query()
                ->whereNotNull('owner_mobile')
                ->where('owner_mobile', '!=', '')
                ->distinct()
                ->orderBy('owner_mobile')
                ->pluck('owner_mobile')
                ->filter()
                ->values()
                ->toArray();
        });
    }

    #[Computed]
    public function ownerNames(): array
    {
        return Cache::remember('repair_owner_names', 3600, function () {
            return Repair::query()
                ->whereNotNull('owner_name')
                ->where('owner_name', '!=', '')
                ->distinct()
                ->orderBy('owner_name')
                ->pluck('owner_name')
                ->filter()
                ->values()
                ->toArray();
        });
    }

    #[Computed]
    public function existingDevicePhotos()
    {
        if (! isset($this->repair)) {
            return collect();
        }

        return $this->repair->getMedia('device_photos')
            ->reject(fn ($media) => in_array($media->id, $this->removedMediaIds, true));
    }

    #[Computed]
    public function existingAttachments()
    {
        if (! isset($this->repair)) {
            return collect();
        }

        return $this->repair->getMedia('attachments')
            ->reject(fn ($media) => in_array($media->id, $this->removedMediaIds, true));
    }

    public function fillOwnerByName(string $name): void
    {
        $repair = Repair::where('owner_name', $name)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($repair) {
            $this->fillOwner($repair->id);
            Flux::toast(__('app.owner_filled_by_name'));
        }
    }

    public function fillOwner(int $repairId): void
    {
        $repair = Repair::findOrFail($repairId);
        $this->owner_name = $repair->owner_name;
        $this->owner_organization = $repair->owner_organization;
        $this->owner_mobile = $repair->owner_mobile;
        $this->owner_email = $repair->owner_email;
        $this->owner_national_code = $repair->owner_national_code;
        $this->owner_address = $repair->owner_address;

        Flux::toast(__('app.owner_filled'));
    }

    public function fillOwnerByMobile(string $mobile): void
    {
        $repair = Repair::where('owner_mobile', $mobile)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($repair) {
            $this->fillOwner($repair->id);
        }
    }

    #[On('panels.service-center.repair.edit.assign-data')]
    public function assignData(int $id): void
    {
        $this->authorize('service_center_repair_edit');

        $this->resetAssistState();

        $this->repair = Repair::with('media')->findOrFail($id);
        $this->id = $this->repair->id;

        $this->owner_name = $this->repair->owner_name ?? '';
        $this->owner_organization = $this->repair->owner_organization;
        $this->owner_mobile = $this->repair->owner_mobile ?? '';
        $this->owner_email = $this->repair->owner_email;
        $this->owner_national_code = $this->repair->owner_national_code;
        $this->owner_address = $this->repair->owner_address;

        $this->warranty_type = $this->repair->warranty_type;
        $this->warranty_date = $this->repair->warranty_date?->format('Y-m-d');
        $this->device_type = $this->repair->device_type ?? '';
        $this->device_brand = $this->repair->device_brand ?? '';
        $this->device_model = $this->repair->device_model ?? '';
        $this->device_serial_number = $this->repair->device_serial_number ?? '';

        $this->device_problem = $this->repair->device_problem;
        $this->device_accessories = $this->repair->device_accessories;
        $this->device_description = $this->repair->device_description;
        $this->device_problem_file = $this->repair->device_problem_file;

        $this->admission_description = $this->repair->admission_description;

        unset($this->existingDevicePhotos, $this->existingAttachments);

        Flux::modal('panels.service-center.repair.edit.modal')->show();
    }

    public function update(): void
    {
        $this->authorize('service_center_repair_edit');

        if (! isset($this->repair)) {
            return;
        }

        $this->validate();

        $hasExistingMedia = $this->repair->media()
            ->whereNotIn('id', $this->removedMediaIds)
            ->exists();
        $hasAttachments = count($this->attachments) > 0 || count($this->devicePhotos) > 0 || $hasExistingMedia;

        if ((empty($this->device_problem) || trim((string) $this->device_problem) === '')
            && (empty($this->device_problem_file) || trim((string) $this->device_problem_file) === '')
            && ! $hasAttachments) {
            $this->addError('device_problem', __('validation.required', ['attribute' => __('app.device_problem')]));

            return;
        }

        $normalizedDeviceProblem = $this->device_problem;
        if ((empty($normalizedDeviceProblem) || trim((string) $normalizedDeviceProblem) === '')
            && ((! empty($this->device_problem_file) && trim((string) $this->device_problem_file) !== '') || $hasAttachments)) {
            $normalizedDeviceProblem = 'file';
        }

        $this->repair->update([
            'admission_description' => $this->admission_description,

            'owner_name' => $this->owner_name,
            'owner_organization' => $this->owner_organization,
            'owner_mobile' => $this->owner_mobile,
            'owner_email' => $this->owner_email,
            'owner_national_code' => $this->owner_national_code,
            'owner_address' => $this->owner_address,

            'warranty_type' => $this->warranty_type,
            'warranty_date' => $this->warranty_date,
            'device_type' => $this->device_type,
            'device_brand' => $this->device_brand,
            'device_model' => $this->device_model,
            'device_serial_number' => $this->device_serial_number,

            'device_problem' => $normalizedDeviceProblem,
            'device_accessories' => $this->device_accessories,
            'device_description' => $this->device_description,
            'device_problem_file' => $this->device_problem_file,
        ]);

        $this->syncRepairMedia($this->repair->fresh());

        Flux::toast(__('app.repair_updated_message'));

        $this->dispatch('panels.service-center.repair.index.render');
        Flux::modal('panels.service-center.repair.edit.modal')->close();
    }

    public function fillDeviceType(string $device_type): void
    {
        $this->device_type = $device_type;
    }

    public function fillDeviceBrand(string $device_brand): void
    {
        $this->device_brand = $device_brand;
    }

    public function fillDeviceModel(string $device_model): void
    {
        $this->device_model = $device_model;
    }

    public function render()
    {
        return view('livewire.panels.service-center.repair.edit');
    }
}
