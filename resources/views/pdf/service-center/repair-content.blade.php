@php
    $forScreen = $forScreen ?? false;
@endphp

<div @class([
    'mx-auto max-w-4xl bg-white p-8 text-black print:max-w-none print:p-0' => $forScreen,
])>
    <div class="mb-8 flex items-start justify-between border-b border-zinc-300 pb-4 gap-4">
        <div>
            <h1 class="text-2xl font-bold">{{ __('app.repair_admission_receipt') }}</h1>
            <p class="mt-1 text-sm text-zinc-600">{{ __('app.admission_code') }}: {{ $repair->admission_code }}</p>
            <p class="mt-1 text-sm text-zinc-600">{{ __('app.status') }}: {{ $statusLabel }}</p>
        </div>
        <div class="text-center shrink-0">
            <img src="{{ $qrDataUri }}" alt="QR" width="140" height="140" style="width:140px;height:140px;">
            <div class="mt-1 text-xs text-zinc-500">{{ __('app.scan_qr_for_status') }}</div>
            <div class="mt-1 text-[10px] text-zinc-400 break-all max-w-[140px]">{{ $shortUrl }}</div>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4 text-sm">
        <div>
            <div class="text-zinc-500">{{ __('app.date') }}</div>
            <div class="font-semibold">{{ $createdAt }}</div>
        </div>
        <div>
            <div class="text-zinc-500">{{ __('app.owner_name') }}</div>
            <div class="font-semibold">{{ $repair->owner_name }}</div>
        </div>
        <div>
            <div class="text-zinc-500">{{ __('app.owner_mobile') }}</div>
            <div class="font-semibold">{{ $repair->owner_mobile }}</div>
        </div>
        @if ($repair->owner_organization)
            <div>
                <div class="text-zinc-500">{{ __('app.owner_organization') }}</div>
                <div class="font-semibold">{{ $repair->owner_organization }}</div>
            </div>
        @endif
    </div>

    <div class="mb-6 rounded-lg border border-zinc-200 p-4 text-sm">
        <h2 class="mb-3 font-bold">{{ __('app.device_information') }}</h2>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <span class="text-zinc-500">{{ __('app.device_type') }}:</span>
                {{ $repair->device_type ?: '-' }}
            </div>
            <div>
                <span class="text-zinc-500">{{ __('app.device_brand') }}:</span>
                {{ $repair->device_brand ?: '-' }}
            </div>
            <div>
                <span class="text-zinc-500">{{ __('app.device_model') }}:</span>
                {{ $repair->device_model ?: '-' }}
            </div>
            <div>
                <span class="text-zinc-500">{{ __('app.device_serial_number') }}:</span>
                {{ $repair->device_serial_number ?: '-' }}
            </div>
        </div>
        @if ($repair->device_accessories)
            <div class="mt-3">
                <span class="text-zinc-500">{{ __('app.device_accessories') }}:</span>
                {{ $repair->device_accessories }}
            </div>
        @endif
        @if ($repair->device_problem)
            <div class="mt-3">
                <span class="text-zinc-500">{{ __('app.device_problem') }}:</span>
                {{ $repair->device_problem }}
            </div>
        @endif
    </div>

    <p class="text-xs text-zinc-500">{{ __('app.repair_receipt_footer') }}</p>
</div>
