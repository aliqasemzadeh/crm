<?php

namespace App\Jobs\SetareganCo;

use App\Jobs\Notification\BaleSendMessageJob;
use App\Models\SetareganCo\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Morilog\Jalali\Jalalian;

class SendPaidOrderBaleNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $orderId)
    {
    }

    public function handle(): void
    {
        $order = Order::query()
            ->with(['details.product'])
            ->find($this->orderId);

        if (! $order || ! (bool) $order->IsPayed) {
            return;
        }

        $lines = [
            __('app.setaregan_paid_order_bale_title'),
            '',
            __('app.setaregan_paid_order_id').': '.$order->Id,
            __('app.setaregan_paid_order_tracking_code').': '.($order->TrackingCode ?: '-'),
            __('app.setaregan_paid_order_customer').': '.($order->CustomerName ?: '-'),
            __('app.setaregan_paid_order_mobile').': '.($order->Mobile ?: '-'),
            __('app.setaregan_paid_order_total_amount').': '.number_format((int) $order->TotalAmount).' '.__('app.rial'),
            __('app.setaregan_paid_order_payment_date').': '.$this->formatPaymentDate($order->PaymentDate),
            __('app.setaregan_paid_order_products').':',
        ];

        $details = $order->details;

        if ($details->isEmpty()) {
            $lines[] = '-';
        } else {
            foreach ($details as $detail) {
                $productName = $detail->product?->Name ?: '-';
                $lines[] = __('app.setaregan_paid_order_product_line', [
                    'product' => $productName,
                    'count' => (int) $detail->Count,
                ]);
            }
        }

        BaleSendMessageJob::dispatch(trim(implode(PHP_EOL, $lines)), 'crm');
    }

    private function formatPaymentDate(mixed $paymentDate): string
    {
        if (blank($paymentDate)) {
            return '-';
        }

        try {
            return Jalalian::fromDateTime($paymentDate)->format('Y/m/d H:i');
        } catch (\Throwable) {
            return (string) $paymentDate;
        }
    }
}
