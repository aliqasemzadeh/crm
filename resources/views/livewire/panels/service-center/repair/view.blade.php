<flux:modal name="panel.service-center.repair.view.modal" class="md:w-2/3" flyout position="right">
    @isset($this->repair)
        <div class="flex flex-col gap-6">
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="lg" level="2">
                        {{ __('app.repairs') }}
                    </flux:heading>
                    <flux:subheading size="md">
                        {{ __('app.repair_view_description') }}
                    </flux:subheading>
                </div>

                <flux:button
                    size="xs"
                    variant="outline"
                    color="zinc"
                    onclick="window.print()"
                >
                    {{ __('app.print') }}
                </flux:button>
            </div>

            {{-- Row 1: Owner (without address) & Device information --}}
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div
                    class="rounded-md border border-zinc-200 bg-zinc-50 p-4 text-sm dark:border-zinc-700 dark:bg-zinc-900 flex flex-col gap-2"
                >
                    <flux:heading size="sm" level="3">
                        {{ __('app.owner_information') }}
                    </flux:heading>

                    <div class="flex flex-col gap-1">
                        <div>
                            <span class="font-medium">{{ __('app.owner_name') }}:</span>
                            <span>{{ $this->repair->owner_name }}</span>
                        </div>
                        <div>
                            <span class="font-medium">{{ __('app.owner_mobile') }}:</span>
                            <span>{{ $this->repair->owner_mobile }}</span>
                        </div>
                        @if ($this->repair->owner_email)
                            <div>
                                <span class="font-medium">{{ __('app.owner_email') }}:</span>
                                <span>{{ $this->repair->owner_email }}</span>
                            </div>
                        @endif
                        @if ($this->repair->owner_national_code)
                            <div>
                                <span class="font-medium">{{ __('app.owner_national_code') }}:</span>
                                <span>{{ $this->repair->owner_national_code }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <div
                    class="rounded-md border border-zinc-200 bg-zinc-50 p-4 text-sm dark:border-zinc-700 dark:bg-zinc-900 flex flex-col gap-2"
                >
                    <flux:heading size="sm" level="3">
                        {{ __('app.device_information') }}
                    </flux:heading>

                    <div class="flex flex-col gap-1">
                        <div>
                            <span class="font-medium">{{ __('app.device_type') }}:</span>
                            <span>{{ $this->repair->device_type }}</span>
                        </div>
                        @if ($this->repair->device_brand)
                            <div>
                                <span class="font-medium">{{ __('app.device_brand') }}:</span>
                                <span>{{ $this->repair->device_brand }}</span>
                            </div>
                        @endif
                        @if ($this->repair->device_model)
                            <div>
                                <span class="font-medium">{{ __('app.device_model') }}:</span>
                                <span>{{ $this->repair->device_model }}</span>
                            </div>
                        @endif
                        @if ($this->repair->device_serial_number)
                            <div>
                                <span class="font-medium">{{ __('app.device_serial_number') }}:</span>
                                <span>{{ $this->repair->device_serial_number }}</span>
                            </div>
                        @endif
                        @if ($this->repair->warranty_type)
                            <div>
                                <span class="font-medium">{{ __('app.warranty_type') }}:</span>
                                <span>{{ $this->repair->warranty_type }}</span>
                            </div>
                        @endif
                        @if ($this->repair->warranty_date)
                            <div>
                                <span class="font-medium">{{ __('app.warranty_date') }}:</span>
                                <span>{{ $this->repair->warranty_date }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Row 2: Owner address (optional) --}}
            @if ($this->repair->owner_address)
                <div
                    class="rounded-md border border-zinc-200 bg-zinc-50 p-4 text-sm dark:border-zinc-700 dark:bg-zinc-900"
                >
                    <flux:heading size="sm" level="3" class="mb-2">
                        {{ __('app.owner_address') }}
                    </flux:heading>
                    <div>
                        {{ $this->repair->owner_address }}
                    </div>
                </div>
            @endif

            {{-- Row 3: Problems & descriptions --}}
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div
                    class="rounded-md border border-zinc-200 bg-zinc-50 p-4 text-sm dark:border-zinc-700 dark:bg-zinc-900 flex flex-col gap-2"
                >
                    <flux:heading size="sm" level="3">
                        {{ __('app.device_problem') }}
                    </flux:heading>

                    @if ($this->repair->device_problem_file)
                        <div class="flex flex-col gap-2">
                            <span class="font-medium">
                                {{ __('app.device_problem_file') }}
                            </span>
                            <img
                                src="{{ $this->repair->device_problem_file }}"
                                alt="{{ __('app.device_problem_file') }}"
                                class="w-full max-h-56 rounded-md object-contain"
                            >
                        </div>
                    @endif

                    @if ($this->repair->device_problem)
                        <div>
                            {{ $this->repair->device_problem }}
                        </div>
                    @endif
                </div>

                <div
                    class="rounded-md border border-zinc-200 bg-zinc-50 p-4 text-sm dark:border-zinc-700 dark:bg-zinc-900 flex flex-col gap-2"
                >
                    <flux:heading size="sm" level="3">
                        {{ __('app.device_description') }}
                    </flux:heading>

                    @if ($this->repair->device_description)
                        <div>
                            {{ $this->repair->device_description }}
                        </div>
                    @else
                        <div class="text-zinc-400">
                            {{ __('app.empty_description') }}
                        </div>
                    @endif
                </div>

                <div
                    class="rounded-md border border-zinc-200 bg-zinc-50 p-4 text-sm dark:border-zinc-700 dark:bg-zinc-900 flex flex-col gap-2"
                >
                    <flux:heading size="sm" level="3">
                        {{ __('app.admission_description_label') }}
                    </flux:heading>

                    @if ($this->repair->admission_description)
                        <div>
                            {{ $this->repair->admission_description }}
                        </div>
                    @else
                        <div class="text-zinc-400">
                            {{ __('app.empty_description') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endisset
</flux:modal>
