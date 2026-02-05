<?php

namespace App\Models\ServiceCenter;

use App\Enums\StatusEnum;
use App\Jobs\Notification\SendSmsMessageJob;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Morilog\Jalali\Jalalian;

class Repair extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'admission_user_id',
        'admission_description',
        'admission_code',
        'admission_counter',
        'status',
        'status_description',
        'status_date',
        'estimate_date',
        'owner_name',
        'owner_organization',
        'owner_mobile',
        'owner_email',
        'owner_national_code',
        'owner_address',
        'warranty_type',
        'warranty_date',
        'device_serial_number',
        'device_brand',
        'device_type',
        'device_model',
        'device_color',
        'device_image',
        'device_problem',
        'device_accessories',
        'device_description',
        'device_problem_file',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status_date' => 'datetime',
        'estimate_date' => 'datetime',
        'warranty_date' => 'datetime',
    ];

    public function services(): HasMany
    {
        return $this->hasMany(RepairService::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(RepairLog::class);
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::created(function (Repair $repair) {
            $repair->saveAdmissionCode();
            $repair->sendAdmissionSms();
        });
    }

    /**
     * Send admission SMS to owner.
     */
    public function sendAdmissionSms(): void
    {
        if (! $this->owner_mobile) {
            return;
        }

        $message = __('app.repair_admission_sms', [
            'admission_code' => $this->admission_code,
        ]).PHP_EOL. "لغو 11";

        SendSmsMessageJob::dispatch($this->owner_mobile, $message);
    }

    /**
     * Generate and save admission code based on Jalali date.
     */
    public function saveAdmissionCode(): void
    {
        if ($this->admission_code) {
            return;
        }

        $createdAt = $this->created_at ?? now();
        $jalaliDate = Jalalian::fromCarbon($createdAt);
        $year = $jalaliDate->getYear();
        $month = $jalaliDate->getMonth();

        // Find repairs in the same Jalali month to get correct counter
        $sameMonthRepairs = static::query()
            ->whereNotNull('created_at')
            ->get()
            ->filter(function ($repair) use ($year, $month) {
                if ($repair->id === $this->id) {
                    return false;
                }
                $repairJalali = Jalalian::fromCarbon($repair->created_at);

                return $repairJalali->getYear() === $year && $repairJalali->getMonth() === $month;
            })
            ->count();

        $admissionCounter = $sameMonthRepairs + 1;

        $this->admission_counter = $admissionCounter;
        $this->admission_code = sprintf('%d%02d%03d', $year, $month, $admissionCounter);

        $this->saveQuietly();
    }

    /**
     * Get the device name by combining brand, type, and model.
     */
    public function getDeviceNameAttribute(): string
    {
        $parts = array_filter([
            $this->device_brand,
            $this->device_type,
            $this->device_model,
        ]);

        return !empty($parts) ? implode(' ', $parts) : __('app.unknown_device');
    }

    /**
     * Get the status enum instance.
     */
    public function getStatusEnumAttribute(): StatusEnum
    {
        return StatusEnum::tryFromSafe($this->status);
    }
}
