<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>{{ __('app.repair_admission_receipt') }} #{{ $repair->admission_code }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111;
            direction: rtl;
        }
        h1 { font-size: 20px; margin: 0 0 6px; }
        h2 { font-size: 14px; margin: 0 0 10px; }
        .header { width: 100%; border-bottom: 1px solid #ccc; padding-bottom: 12px; margin-bottom: 18px; }
        .header td { vertical-align: top; }
        .muted { color: #666; font-size: 11px; }
        .box { border: 1px solid #ddd; padding: 12px; margin-bottom: 16px; }
        .grid td { padding: 4px 0; width: 50%; vertical-align: top; }
        .qr { text-align: center; }
        .qr img { width: 120px; height: 120px; }
        .footer { color: #777; font-size: 10px; margin-top: 20px; }
    </style>
</head>
<body>
    <table class="header" width="100%">
        <tr>
            <td>
                <h1>{{ __('app.repair_admission_receipt') }}</h1>
                <div class="muted">{{ __('app.admission_code') }}: {{ $repair->admission_code }}</div>
                <div class="muted">{{ __('app.status') }}: {{ $statusLabel }}</div>
                <div class="muted">{{ __('app.date') }}: {{ $createdAt }}</div>
            </td>
            <td class="qr" width="140">
                <img src="{{ $qrDataUri }}" alt="QR">
                <div class="muted">{{ __('app.scan_qr_for_status') }}</div>
                <div class="muted" style="word-break: break-all;">{{ $shortUrl }}</div>
            </td>
        </tr>
    </table>

    <table class="grid" width="100%">
        <tr>
            <td><span class="muted">{{ __('app.owner_name') }}:</span> {{ $repair->owner_name }}</td>
            <td><span class="muted">{{ __('app.owner_mobile') }}:</span> {{ $repair->owner_mobile }}</td>
        </tr>
        @if ($repair->owner_organization)
            <tr>
                <td colspan="2"><span class="muted">{{ __('app.owner_organization') }}:</span> {{ $repair->owner_organization }}</td>
            </tr>
        @endif
    </table>

    <div class="box">
        <h2>{{ __('app.device_information') }}</h2>
        <table class="grid" width="100%">
            <tr>
                <td><span class="muted">{{ __('app.device_type') }}:</span> {{ $repair->device_type ?: '-' }}</td>
                <td><span class="muted">{{ __('app.device_brand') }}:</span> {{ $repair->device_brand ?: '-' }}</td>
            </tr>
            <tr>
                <td><span class="muted">{{ __('app.device_model') }}:</span> {{ $repair->device_model ?: '-' }}</td>
                <td><span class="muted">{{ __('app.device_serial_number') }}:</span> {{ $repair->device_serial_number ?: '-' }}</td>
            </tr>
        </table>
        @if ($repair->device_accessories)
            <p><span class="muted">{{ __('app.device_accessories') }}:</span> {{ $repair->device_accessories }}</p>
        @endif
        @if ($repair->device_problem)
            <p><span class="muted">{{ __('app.device_problem') }}:</span> {{ $repair->device_problem }}</p>
        @endif
    </div>

    <div class="footer">{{ __('app.repair_receipt_footer') }}</div>
</body>
</html>
