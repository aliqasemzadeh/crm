<flux:modal name="panels.service-center.repair.view.modal" class="md:w-2/3" flyout position="right">
    @if ($repair)
        <div class="flex flex-col gap-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <flux:heading size="lg" level="2">
                        {{ __('app.repairs') }}
                        @if ($repair->admission_code)
                            <span class="text-zinc-500">#{{ $repair->admission_code }}</span>
                        @endif
                    </flux:heading>
                    <flux:subheading size="md">
                        {{ __('app.repair_view_description') }}
                    </flux:subheading>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <flux:button
                        size="xs"
                        variant="primary"
                        color="sky"
                        icon="printer"
                        href="{{ route('panels.service-center.repair.print', $repair) }}"
                        wire:navigate
                    >
                        {{ __('app.print') }}
                    </flux:button>
                    <flux:button
                        size="xs"
                        variant="primary"
                        color="teal"
                        icon="download"
                        href="{{ route('panels.service-center.repair.pdf', $repair) }}"
                    >
                        {{ __('app.download_pdf') }}
                    </flux:button>
                </div>
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
                            <span>{{ $repair->owner_name }}</span>
                        </div>
                        <div>
                            <span class="font-medium">{{ __('app.owner_mobile') }}:</span>
                            <span>{{ $repair->owner_mobile }}</span>
                        </div>
                        @if ($repair->owner_email)
                            <div>
                                <span class="font-medium">{{ __('app.owner_email') }}:</span>
                                <span>{{ $repair->owner_email }}</span>
                            </div>
                        @endif
                        @if ($repair->owner_national_code)
                            <div>
                                <span class="font-medium">{{ __('app.owner_national_code') }}:</span>
                                <span>{{ $repair->owner_national_code }}</span>
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
                            <span>{{ $repair->device_type }}</span>
                        </div>
                        @if ($repair->device_brand)
                            <div>
                                <span class="font-medium">{{ __('app.device_brand') }}:</span>
                                <span>{{ $repair->device_brand }}</span>
                            </div>
                        @endif
                        @if ($repair->device_model)
                            <div>
                                <span class="font-medium">{{ __('app.device_model') }}:</span>
                                <span>{{ $repair->device_model }}</span>
                            </div>
                        @endif
                        @if ($repair->device_serial_number)
                            <div>
                                <span class="font-medium">{{ __('app.device_serial_number') }}:</span>
                                <span>{{ $repair->device_serial_number }}</span>
                            </div>
                        @endif
                        @if ($repair->warranty_type)
                            <div>
                                <span class="font-medium">{{ __('app.warranty_type') }}:</span>
                                <span>{{ $repair->warranty_type }}</span>
                            </div>
                        @endif
                        @if ($repair->warranty_date)
                            <div>
                                <span class="font-medium">{{ __('app.warranty_date') }}:</span>
                                <span>{{ $repair->warranty_date }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Row 2: Owner address (optional) --}}
            @if ($repair->owner_address)
                <div
                    class="rounded-md border border-zinc-200 bg-zinc-50 p-4 text-sm dark:border-zinc-700 dark:bg-zinc-900"
                >
                    <flux:heading size="sm" level="3" class="mb-2">
                        {{ __('app.owner_address') }}
                    </flux:heading>
                    <div>
                        {{ $repair->owner_address }}
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

                    @if ($repair->device_problem_file)
                        <div class="flex flex-col gap-2">
                            <span class="font-medium">
                                {{ __('app.device_problem_file') }}
                            </span>
                            <img
                                src="{{ $repair->device_problem_file }}"
                                alt="{{ __('app.device_problem_file') }}"
                                class="w-full max-h-56 rounded-md object-contain"
                            >
                        </div>
                    @endif

                    @if ($repair->device_problem)
                        <div>
                            {{ $repair->device_problem }}
                        </div>
                    @endif
                </div>

                <div
                    class="rounded-md border border-zinc-200 bg-zinc-50 p-4 text-sm dark:border-zinc-700 dark:bg-zinc-900 flex flex-col gap-2"
                >
                    <flux:heading size="sm" level="3">
                        {{ __('app.device_description') }}
                    </flux:heading>

                    @if ($repair->device_description)
                        <div>
                            {{ $repair->device_description }}
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

                    @if ($repair->admission_description)
                        <div>
                            {{ $repair->admission_description }}
                        </div>
                    @else
                        <div class="text-zinc-400">
                            {{ __('app.empty_description') }}
                        </div>
                    @endif
                </div>
            </div>

            @include('livewire.panels.service-center.repair.partials.media-gallery', [
                'repair' => $repair,
            ])
        </div>
    @endif
</flux:modal>
