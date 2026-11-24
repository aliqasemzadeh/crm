<?php

use App\Models\ServiceCenter\Repair;
use App\Support\QrCodeGenerator;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

new #[Layout('layouts.panels.service-center')] class extends Component {
    public Repair $repair;

    public string $shortUrl = '';

    public string $qrDataUri = '';

    public function mount(Repair $repair): void
    {
        $this->authorize('service_center_repair_view');

        $this->repair = $repair;
        $this->shortUrl = $repair->ensureShortUrl();
        $this->qrDataUri = QrCodeGenerator::pngDataUri($this->shortUrl, 180);
    }
};
?>

<x-slot name="title">
    {{ __('app.print') }} #{{ $repair->admission_code }}
</x-slot>

<div class="print:p-0">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2 print:hidden">
        <flux:button variant="ghost" href="{{ route('panels.service-center.repair.index') }}" wire:navigate icon="arrow-right">
            {{ __('app.back') }}
        </flux:button>
        <div class="flex flex-wrap items-center gap-2">
            <flux:button variant="primary" color="teal" icon="download" href="{{ route('panels.service-center.repair.pdf', $repair) }}">
                {{ __('app.download_pdf') }}
            </flux:button>
            <flux:button variant="primary" color="sky" icon="printer" x-on:click="window.print()">
                {{ __('app.print') }}
            </flux:button>
        </div>
    </div>

    @include('pdf.service-center.repair-content', [
        'repair' => $repair,
        'shortUrl' => $shortUrl,
        'qrDataUri' => $qrDataUri,
        'createdAt' => $repair->created_at
            ? Jalalian::fromCarbon($repair->created_at)->format('Y/m/d H:i')
            : '-',
        'statusLabel' => $repair->status_enum->label(),
        'forScreen' => true,
    ])
</div>

<style>
    @media print {
        .panel-shell-sidebar,
        [data-flux-sidebar],
        nav,
        aside {
            display: none !important;
        }

        body {
            background: white !important;
        }

        main, .print\:p-0 {
            margin: 0 !important;
            padding: 0 !important;
            max-width: none !important;
        }
    }
</style>
