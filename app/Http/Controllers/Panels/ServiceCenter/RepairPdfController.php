<?php

namespace App\Http\Controllers\Panels\ServiceCenter;

use App\Http\Controllers\Controller;
use App\Models\ServiceCenter\Repair;
use App\Support\QrCodeGenerator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Morilog\Jalali\Jalalian;

class RepairPdfController extends Controller
{
    public function __invoke(Repair $repair): Response
    {
        Gate::authorize('service_center_repair_view');

        $shortUrl = $repair->ensureShortUrl();
        $qrDataUri = QrCodeGenerator::pngDataUri($shortUrl, 180);

        $pdf = Pdf::loadView('pdf.service-center.repair', [
            'repair' => $repair,
            'shortUrl' => $shortUrl,
            'qrDataUri' => $qrDataUri,
            'createdAt' => $repair->created_at
                ? Jalalian::fromCarbon($repair->created_at)->format('Y/m/d H:i')
                : '-',
            'statusLabel' => $repair->status_enum->label(),
        ])->setPaper('a4');

        $filename = 'repair-'.$repair->admission_code.'.pdf';

        return $pdf->download($filename);
    }
}
