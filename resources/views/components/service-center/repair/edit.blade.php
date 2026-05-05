<?php

use App\Models\ServiceCenter\Repair;
use Flux\Flux;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

new class extends Component
{
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

    protected $rules = [
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
    ];

    #[Computed]
    public function types(): array
    {
        return Cache::remember('repair_device_types', 3600, fn () => Repair::query()
            ->whereNotNull('device_type')
            ->where('device_type', '!=', '')
            ->distinct()
            ->orderBy('device_type')
            ->pluck('device_type')
            ->filter()
            ->values()
            ->toArray());
    }

    #[Computed]
    public function brands(): array
    {
        return Cache::remember('repair_device_brands', 3600, fn () => Repair::query()
            ->whereNotNull('device_brand')
            ->where('device_brand', '!=', '')
            ->distinct()
            ->orderBy('device_brand')
            ->pluck('device_brand')
            ->filter()
            ->values()
            ->toArray());
    }

    #[Computed]
    public function models(): array
    {
        return Cache::remember('repair_device_models', 3600, fn () => Repair::query()
            ->whereNotNull('device_model')
            ->where('device_model', '!=', '')
            ->distinct()
            ->orderBy('device_model')
            ->pluck('device_model')
            ->filter()
            ->values()
            ->toArray());
    }

    #[Computed]
    public function owners(): array
    {
        return Cache::remember('repair_owners', 3600, fn () => Repair::query()
            ->whereNotNull('owner_mobile')
            ->where('owner_mobile', '!=', '')
            ->distinct()
            ->orderBy('owner_mobile')
            ->pluck('owner_mobile')
            ->filter()
            ->values()
            ->toArray());
    }

    #[Computed]
    public function ownerNames(): array
    {
        return Cache::remember('repair_owner_names', 3600, fn () => Repair::query()
            ->whereNotNull('owner_name')
            ->where('owner_name', '!=', '')
            ->distinct()
            ->orderBy('owner_name')
            ->pluck('owner_name')
            ->filter()
            ->values()
            ->toArray());
    }

    public function fillOwnerByName(string $name): void
    {
        $repair = Repair::where('owner_name', $name)->orderBy('created_at', 'desc')->first();

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
        $repair = Repair::where('owner_mobile', $mobile)->orderBy('created_at', 'desc')->first();

        if ($repair) {
            $this->fillOwner($repair->id);
        }
    }

    #[On('panels.service-center.repair.edit.assign-data')]
    public function assignData(int $id): void
    {
        $this->authorize('service_center_repair_edit');

        $this->repair = Repair::findOrFail($id);
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

        Flux::modal('panels.service-center.repair.edit.modal')->show();
    }

    public function update(): void
    {
        $this->authorize('service_center_repair_edit');

        if (!isset($this->repair)) {
            return;
        }

        $this->validate();

        if ((empty($this->device_problem) || trim((string) $this->device_problem) === '')
            && (empty($this->device_problem_file) || trim((string) $this->device_problem_file) === '')) {
            $this->addError('device_problem', __('validation.required', ['attribute' => __('app.device_problem')]));

            return;
        }

        $normalizedDeviceProblem = $this->device_problem;
        if ((empty($normalizedDeviceProblem) || trim((string) $normalizedDeviceProblem) === '')
            && !empty($this->device_problem_file) && trim((string) $this->device_problem_file) !== '') {
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
};
?>

<flux:modal name="panels.service-center.repair.edit.modal" class="md:w-2/3" flyout position="right">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.edit_repair') }}</flux:heading>
            <flux:text class="mt-2">{{ __('app.edit_repair_description') }}</flux:text>
        </div>
        <form wire:submit="update" method="post" class="space-y-6">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="space-y-4">
                    <flux:heading size="sm">{{ __('app.owner_information') }}</flux:heading>
                    <flux:field><flux:label>{{ __('app.owner_mobile') }}</flux:label><flux:autocomplete wire:model="owner_mobile" size="sm">@foreach($this->owners as $ownerMobile)<flux:autocomplete.item @click="$wire.fillOwnerByMobile('{{ $ownerMobile }}')">{{ $ownerMobile }}</flux:autocomplete.item>@endforeach</flux:autocomplete><flux:error name="owner_mobile" /></flux:field>
                    <flux:field><flux:label>{{ __('app.owner_name') }}</flux:label><flux:autocomplete wire:model="owner_name" size="sm">@foreach($this->ownerNames as $ownerName)<flux:autocomplete.item @click="$wire.fillOwnerByName('{{ $ownerName }}')">{{ $ownerName }}</flux:autocomplete.item>@endforeach</flux:autocomplete><flux:error name="owner_name" /></flux:field>
                    <flux:field><flux:label>{{ __('app.owner_organization') }}</flux:label><flux:input wire:model="owner_organization" type="text" size="sm" /><flux:error name="owner_organization" /></flux:field>
                    <flux:field><flux:label>{{ __('app.owner_email') }}</flux:label><flux:input wire:model="owner_email" type="email" size="sm" /><flux:error name="owner_email" /></flux:field>
                    <flux:field><flux:label>{{ __('app.owner_national_code') }}</flux:label><flux:input wire:model="owner_national_code" type="text" size="sm" /><flux:error name="owner_national_code" /></flux:field>
                    <flux:field><flux:label>{{ __('app.owner_address') }}</flux:label><flux:textarea wire:model="owner_address" rows="3" size="sm" /><flux:error name="owner_address" /></flux:field>
                </div>
                <div class="space-y-4">
                    <flux:heading size="sm">{{ __('app.device_information') }}</flux:heading>
                    <flux:field><flux:label>{{ __('app.device_type') }}</flux:label><flux:autocomplete wire:model="device_type" size="sm">@foreach($this->types as $type)<flux:autocomplete.item @click="$wire.fillDeviceType('{{ $type }}')">{{ $type }}</flux:autocomplete.item>@endforeach</flux:autocomplete><flux:error name="device_type" /></flux:field>
                    <flux:field><flux:label>{{ __('app.device_brand') }}</flux:label><flux:autocomplete wire:model="device_brand" size="sm">@foreach($this->brands as $brand)<flux:autocomplete.item @click="$wire.fillDeviceBrand('{{ $brand }}')">{{ $brand }}</flux:autocomplete.item>@endforeach</flux:autocomplete><flux:error name="device_brand" /></flux:field>
                    <flux:field><flux:label>{{ __('app.device_model') }}</flux:label><flux:autocomplete wire:model="device_model" size="sm" type="text">@foreach($this->models as $model)<flux:autocomplete.item @click="$wire.fillDeviceModel('{{ $model }}')">{{ $model }}</flux:autocomplete.item>@endforeach</flux:autocomplete><flux:error name="device_model" /></flux:field>
                    <flux:field><flux:label>{{ __('app.device_serial_number') }}</flux:label><flux:input wire:model="device_serial_number" type="text" size="sm" /><flux:error name="device_serial_number" /></flux:field>
                    <flux:field><flux:label>{{ __('app.warranty_type') }}</flux:label><flux:select wire:model="warranty_type" placeholder="{{ __('app.select_warranty_status') }}" size="sm"><flux:select.option value="no">{{ __('app.warranty_no') }}</flux:select.option><flux:select.option value="yes">{{ __('app.warranty_yes') }}</flux:select.option></flux:select><flux:error name="warranty_type" /></flux:field>
                    <flux:field><flux:label>{{ __('app.warranty_date') }}</flux:label><flux:input wire:model="warranty_date" type="date" size="sm" /><flux:error name="warranty_date" /></flux:field>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <flux:field>
                    <flux:label>{{ __('app.device_problem') }}</flux:label>
                    <flux:textarea wire:model="device_problem" rows="3" size="sm" />
                    <flux:error name="device_problem" />
                    <div x-data="{isOpen:false,drawing:false,ctx:null,color:'#111827',size:3,lastX:0,lastY:0,openBoard(){this.isOpen=true;this.$nextTick(()=>this.initCanvas());},closeBoard(){this.isOpen=false;},clearBoard(){const c=this.$refs.board;this.ctx.clearRect(0,0,c.width,c.height);},saveBoard(){const c=this.$refs.board;const data=c.toDataURL('image/png');this.$wire.set('device_problem_file',data);this.isOpen=false;},initCanvas(){const canvas=this.$refs.board;const parent=canvas.parentElement;const dpr=window.devicePixelRatio||1;const rect=parent.getBoundingClientRect();canvas.width=Math.floor(rect.width*dpr);canvas.height=Math.floor((rect.height-48)*dpr);canvas.style.width=rect.width+'px';canvas.style.height=(rect.height-48)+'px';this.ctx=canvas.getContext('2d');this.ctx.scale(dpr,dpr);this.ctx.lineCap='round';this.ctx.lineJoin='round';this.ctx.strokeStyle=this.color;this.ctx.lineWidth=this.size;},pointerDown(e){this.drawing=true;const p=this.point(e);this.lastX=p.x;this.lastY=p.y;},pointerMove(e){if(!this.drawing){return;}const p=this.point(e);this.ctx.strokeStyle=this.color;this.ctx.lineWidth=this.size;this.ctx.beginPath();this.ctx.moveTo(this.lastX,this.lastY);this.ctx.lineTo(p.x,p.y);this.ctx.stroke();this.lastX=p.x;this.lastY=p.y;},pointerUp(){this.drawing=false;},point(e){const canvas=this.$refs.board;const rect=canvas.getBoundingClientRect();const touch=e.touches?e.touches[0]:null;const clientX=touch?touch.clientX:e.clientX;const clientY=touch?touch.clientY:e.clientY;return {x:clientX-rect.left,y:clientY-rect.top};}}" x-cloak class="relative">
                        <input type="hidden" wire:model="device_problem_file" />
                        <div class="mt-2"><flux:button type="button" size="xs" variant="outline" @click="openBoard()">{{ __('app.open_whiteboard') }}</flux:button></div>
                        <div x-show="isOpen" wire:ignore class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                            <div class="w-full max-w-3xl rounded-lg bg-white p-3 shadow-xl dark:bg-neutral-900">
                                <div class="mb-2 flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-2"><flux:label class="!mb-0">{{ __('app.color') }}</flux:label><input type="color" x-model="color" class="h-6 w-10 cursor-pointer appearance-none border-0 bg-transparent p-0" /><flux:label class="!mb-0">{{ __('app.thickness') }}</flux:label><input type="range" min="1" max="20" x-model.number="size" class="w-32" /></div>
                                    <div class="flex items-center gap-2"><flux:button type="button" size="xs" variant="filled" @click="clearBoard()">{{ __('app.clear') }}</flux:button><flux:button type="button" size="xs" variant="primary" @click="saveBoard()">{{ __('app.save') }}</flux:button><flux:button type="button" size="xs" variant="ghost" @click="closeBoard()">{{ __('app.close') }}</flux:button></div>
                                </div>
                                <div class="h-[60vh] w-full overflow-hidden rounded border border-black/10 dark:border-white/10"><canvas x-ref="board" @mousedown.prevent="pointerDown($event)" @mousemove.prevent="pointerMove($event)" @mouseup.prevent="pointerUp()" @mouseleave.prevent="pointerUp()" @touchstart.passive="pointerDown($event)" @touchmove.passive="pointerMove($event)" @touchend.passive="pointerUp()"></canvas></div>
                            </div>
                        </div>
                    </div>
                </flux:field>
                <flux:field><flux:label>{{ __('app.device_accessories') }}</flux:label><flux:textarea wire:model="device_accessories" rows="3" size="sm" /><flux:error name="device_accessories" /></flux:field>
                <flux:field><flux:label>{{ __('app.device_description') }}</flux:label><flux:textarea wire:model="device_description" rows="3" size="sm" /><flux:error name="device_description" /></flux:field>
            </div>
            <flux:field><flux:label>{{ __('app.admission_description_label') }}</flux:label><flux:textarea wire:model="admission_description" rows="3" size="sm" /><flux:error name="admission_description" /></flux:field>
            <flux:button type="submit" class="w-full" variant="primary">{{ __('app.update') }}</flux:button>
        </form>
    </div>
</flux:modal>