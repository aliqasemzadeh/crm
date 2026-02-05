<flux:modal name="panel.service-center.repair.problem.modal" class="md:w-2/3" flyout position="right">
    @isset($this->repair)
        <div class="flex flex-col gap-6">
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="lg" level="2">
                        {{ __('app.device_problem') }}
                    </flux:heading>
                    <flux:subheading size="md">
                        {{ __('app.problem_description') }}
                    </flux:subheading>
                </div>
            </div>

            <div class="flex flex-col gap-3">
                @if ($this->repair->device_problem_file)
                    <div
                        class="rounded-md border border-zinc-200 bg-zinc-50 p-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 flex flex-col gap-2 items-start"
                    >
                        <span class="font-medium">
                            {{ __('app.device_problem_file') }}
                        </span>
                        <img
                            src="{{ $this->repair->device_problem_file }}"
                            alt="{{ __('app.device_problem_file') }}"
                            class="w-48 h-48 rounded-md object-contain"
                        >
                    </div>
                @endif

                @if ($this->repair->device_problem)
                    <div
                        class="rounded-md border border-zinc-200 bg-zinc-50 p-3 text-sm dark:border-zinc-700 dark:bg-zinc-900"
                    >
                        <span class="font-medium block mb-1">
                            {{ __('app.device_problem') }}
                        </span>
                        <span>
                            {{ $this->repair->device_problem }}
                        </span>
                    </div>
                @endif
            </div>
        </div>
    @endisset
</flux:modal>
