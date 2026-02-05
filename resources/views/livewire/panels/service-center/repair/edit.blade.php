<flux:modal name="panel.service-center.repair.edit.modal" class="md:w-2/3" flyout position="right">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.edit_repair') }}</flux:heading>
            <flux:text class="mt-2">{{ __('app.edit_repair_description') }}</flux:text>
        </div>

        <!-- Modal body -->
        <form wire:submit="update" method="post" class="space-y-6">
            <!-- First row: Owner / Device info -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Owner column -->
                <div class="space-y-4">
                    <flux:heading size="sm">{{ __('app.owner_information') }}</flux:heading>

                    <flux:field>
                        <flux:label>{{ __('app.owner_mobile') }}</flux:label>
                        <flux:autocomplete wire:model="owner_mobile" size="sm">
                            @foreach($this->owners as $ownerMobile)
                                <flux:autocomplete.item @click="$wire.fillOwnerByMobile('{{ $ownerMobile }}')">{{ $ownerMobile }}</flux:autocomplete.item>
                            @endforeach
                        </flux:autocomplete>
                        <flux:error name="owner_mobile" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('app.owner_name') }}</flux:label>
                        <flux:autocomplete wire:model="owner_name" size="sm">
                            @foreach($this->ownerNames as $ownerName)
                                <flux:autocomplete.item @click="$wire.fillOwnerByName('{{ $ownerName }}')">{{ $ownerName }}</flux:autocomplete.item>
                            @endforeach
                        </flux:autocomplete>
                        <flux:error name="owner_name" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('app.owner_organization') }}</flux:label>
                        <flux:input wire:model="owner_organization" type="text" size="sm" />
                        <flux:error name="owner_organization" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('app.owner_email') }}</flux:label>
                        <flux:input wire:model="owner_email" type="email" size="sm" />
                        <flux:error name="owner_email" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('app.owner_national_code') }}</flux:label>
                        <flux:input wire:model="owner_national_code" type="text" size="sm" />
                        <flux:error name="owner_national_code" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('app.owner_address') }}</flux:label>
                        <flux:textarea wire:model="owner_address" rows="3" size="sm" />
                        <flux:error name="owner_address" />
                    </flux:field>
                </div>

                <!-- Device column -->
                <div class="space-y-4">
                    <flux:heading size="sm">{{ __('app.device_information') }}</flux:heading>

                    <flux:field>
                        <flux:label>{{ __('app.device_type') }}</flux:label>
                        <flux:autocomplete wire:model="device_type" size="sm">
                            @foreach($this->types as $type)
                                <flux:autocomplete.item @click="$wire.fillDeviceType('{{ $type }}')">{{ $type }}</flux:autocomplete.item>
                            @endforeach
                        </flux:autocomplete>
                        <flux:error name="device_type" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('app.device_brand') }}</flux:label>
                        <flux:autocomplete wire:model="device_brand" size="sm">
                            @foreach($this->brands as $brand)
                                <flux:autocomplete.item @click="$wire.fillDeviceBrand('{{ $brand }}')">{{ $brand }}</flux:autocomplete.item>
                            @endforeach
                        </flux:autocomplete>
                        <flux:error name="device_brand" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('app.device_model') }}</flux:label>
                        <flux:autocomplete wire:model="device_model" size="sm" type="text">
                            @foreach($this->models as $model)
                                <flux:autocomplete.item @click="$wire.fillDeviceModel('{{ $model }}')">{{ $model }}</flux:autocomplete.item>
                            @endforeach
                        </flux:autocomplete>
                        <flux:error name="device_model" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('app.device_serial_number') }}</flux:label>
                        <flux:input wire:model="device_serial_number" type="text" size="sm" />
                        <flux:error name="device_serial_number" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('app.warranty_type') }}</flux:label>
                        <flux:select wire:model="warranty_type" placeholder="{{ __('app.select_warranty_status') }}" size="sm">
                            <flux:select.option value="no">{{ __('app.warranty_no') }}</flux:select.option>
                            <flux:select.option value="yes">{{ __('app.warranty_yes') }}</flux:select.option>
                        </flux:select>
                        <flux:error name="warranty_type" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('app.warranty_date') }}</flux:label>
                        <flux:input wire:model="warranty_date" type="date" size="sm" />
                        <flux:error name="warranty_date" />
                    </flux:field>
                </div>
            </div>

            <!-- Second row: Problem / Accessories / Description -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <flux:field>
                    <flux:label>{{ __('app.device_problem') }}</flux:label>
                    <flux:textarea wire:model="device_problem" rows="3" size="sm" />
                    <flux:error name="device_problem" />

                    <!-- Whiteboard -->
                    <div
                        x-data="{
                                isOpen: false,
                                drawing: false,
                                ctx: null,
                                color: '#111827',
                                size: 3,
                                lastX: 0,
                                lastY: 0,
                                openBoard() { this.isOpen = true; this.$nextTick(() => this.initCanvas()); },
                                closeBoard() { this.isOpen = false; },
                                clearBoard() { const c = this.$refs.board; this.ctx.clearRect(0,0,c.width,c.height); },
                                saveBoard() {
                                    const c = this.$refs.board;
                                    const data = c.toDataURL('image/png');
                                    this.$wire.set('device_problem_file', data);
                                    this.isOpen = false;
                                },
                                initCanvas() {
                                    const canvas = this.$refs.board;
                                    const parent = canvas.parentElement;
                                    const dpr = window.devicePixelRatio || 1;
                                    const rect = parent.getBoundingClientRect();
                                    canvas.width = Math.floor(rect.width * dpr);
                                    canvas.height = Math.floor((rect.height - 48) * dpr);
                                    canvas.style.width = rect.width + 'px';
                                    canvas.style.height = (rect.height - 48) + 'px';
                                    this.ctx = canvas.getContext('2d');
                                    this.ctx.scale(dpr, dpr);
                                    this.ctx.lineCap = 'round';
                                    this.ctx.lineJoin = 'round';
                                    this.ctx.strokeStyle = this.color;
                                    this.ctx.lineWidth = this.size;
                                },
                                pointerDown(e) {
                                    this.drawing = true;
                                    const p = this.point(e);
                                    this.lastX = p.x; this.lastY = p.y;
                                },
                                pointerMove(e) {
                                    if (!this.drawing) { return; }
                                    const p = this.point(e);
                                    this.ctx.strokeStyle = this.color;
                                    this.ctx.lineWidth = this.size;
                                    this.ctx.beginPath();
                                    this.ctx.moveTo(this.lastX, this.lastY);
                                    this.ctx.lineTo(p.x, p.y);
                                    this.ctx.stroke();
                                    this.lastX = p.x; this.lastY = p.y;
                                },
                                pointerUp() { this.drawing = false; },
                                point(e) {
                                    const canvas = this.$refs.board;
                                    const rect = canvas.getBoundingClientRect();
                                    const touch = e.touches ? e.touches[0] : null;
                                    const clientX = touch ? touch.clientX : e.clientX;
                                    const clientY = touch ? touch.clientY : e.clientY;
                                    return { x: clientX - rect.left, y: clientY - rect.top };
                                }
                            }"
                        x-cloak
                        class="relative"
                    >
                        <input type="hidden" wire:model="device_problem_file" />
                        <div class="mt-2">
                            <flux:button type="button" size="xs" variant="outline" @click="openBoard()">
                                {{ __('app.open_whiteboard') }}
                            </flux:button>
                        </div>

                        <!-- Overlay -->
                        <div x-show="isOpen" wire:ignore class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                            <div class="w-full max-w-3xl rounded-lg bg-white p-3 shadow-xl dark:bg-neutral-900">
                                <div class="mb-2 flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-2">
                                        <flux:label class="!mb-0">{{ __('app.color') }}</flux:label>
                                        <input type="color" x-model="color" class="h-6 w-10 cursor-pointer appearance-none border-0 bg-transparent p-0" />
                                        <flux:label class="!mb-0">{{ __('app.thickness') }}</flux:label>
                                        <input type="range" min="1" max="20" x-model.number="size" class="w-32" />
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <flux:button type="button" size="xs" variant="filled" @click="clearBoard()">{{ __('app.clear') }}</flux:button>
                                        <flux:button type="button" size="xs" variant="primary" @click="saveBoard()">{{ __('app.save') }}</flux:button>
                                        <flux:button type="button" size="xs" variant="ghost" @click="closeBoard()">{{ __('app.close') }}</flux:button>
                                    </div>
                                </div>
                                <div class="h-[60vh] w-full overflow-hidden rounded border border-black/10 dark:border-white/10">
                                    <canvas
                                        x-ref="board"
                                        @mousedown.prevent="pointerDown($event)"
                                        @mousemove.prevent="pointerMove($event)"
                                        @mouseup.prevent="pointerUp()"
                                        @mouseleave.prevent="pointerUp()"
                                        @touchstart.passive="pointerDown($event)"
                                        @touchmove.passive="pointerMove($event)"
                                        @touchend.passive="pointerUp()"
                                    ></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('app.device_accessories') }}</flux:label>
                    <flux:textarea wire:model="device_accessories" rows="3" size="sm" />
                    <flux:error name="device_accessories" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('app.device_description') }}</flux:label>
                    <flux:textarea wire:model="device_description" rows="3" size="sm" />
                    <flux:error name="device_description" />
                </flux:field>
            </div>

            <!-- Admission description (optional) -->
            <div>
                <flux:field>
                    <flux:label>{{ __('app.admission_description_label') }}</flux:label>
                    <flux:textarea wire:model="admission_description" rows="3" size="sm" />
                    <flux:error name="admission_description" />
                </flux:field>
            </div>

            <flux:button type="submit" class="w-full" variant="primary">
                {{ __('app.update') }}
            </flux:button>
        </form>
    </div>
</flux:modal>
