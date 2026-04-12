<?php

namespace App\Models\Workspace;

use App\Jobs\Notification\SendSmsMessageJob;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Morilog\Jalali\Jalalian;
use RingleSoft\LaravelProcessApproval\Contracts\ApprovableModel;
use RingleSoft\LaravelProcessApproval\Models\ProcessApproval;


class Request extends Model implements ApprovableModel
{
    use \RingleSoft\LaravelProcessApproval\Traits\Approvable;

    public $table = 'workspace_requests';

    protected $fillable = [
        'request_number',
        'request_counter',
        'user_id',
        'type',
        'title',
        'item_name',
        'item_description',
        'quantity',
        'unit',
        'estimated_price',
        'estimated_total',
        'reason',
        'status',
        'current_step',
        'requester_signature',
        'submitted_at',
        'approved_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::created(function (Request $request) {
            $request->saveRequestNumber();
            $request->sendRequestSms();
        });
    }

    /**
     * Send admission SMS to owner and administrator.
     */
    public function sendRequestSms(): void
    {
        // SMS to requester
        if ($this->user && $this->user->mobile) {
            $message = __('app.request_admission_sms', [
                'request_number' => $this->request_number,
            ]) . PHP_EOL . "لغو 11";
            SendSmsMessageJob::dispatch($this->user->mobile, $message);
        }

        // SMS to administrator
        $admins = User::role('administrator')->get();
        foreach ($admins as $admin) {
            if ($admin->mobile) {
                $adminMessage = "درخواست جدید با شماره {$this->request_number} توسط {$this->user?->name} ثبت شد و منتظر تایید شماست.";
                SendSmsMessageJob::dispatch($admin->mobile, $adminMessage);
            }
        }
    }

    /**
     * Generate and save request number based on Jalali date.
     */
    public function saveRequestNumber(): void
    {
        if ($this->request_number) {
            return;
        }

        $createdAt = $this->created_at ?? now();
        $jalaliDate = Jalalian::fromCarbon($createdAt);
        $year = $jalaliDate->getYear();
        $month = $jalaliDate->getMonth();

        // Find requests in the same Jalali month to get correct counter
        $sameMonthRequests = static::query()
            ->whereNotNull('created_at')
            ->get()
            ->filter(function ($request) use ($year, $month) {
                if ($request->id === $this->id) {
                    return false;
                }
                $requestJalali = Jalalian::fromCarbon($request->created_at);

                return $requestJalali->getYear() === $year && $requestJalali->getMonth() === $month;
            })
            ->count();

        $requestCounter = $sameMonthRequests + 1;

        $this->request_counter = $requestCounter;
        $this->request_number = sprintf('%d%02d%03d', $year, $month, $requestCounter);

        $this->saveQuietly();
    }

    public function onApprovalCompleted(ProcessApproval $approval): bool
    {
        return true;
    }
}
