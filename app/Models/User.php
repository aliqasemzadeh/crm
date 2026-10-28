<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Hr\DayRecord;
use App\Models\Hr\Record;
use App\Models\Issabel\Device as IssabelDevice;
use App\Models\Sepidar\FMK\User as SepidarUser;
use App\Models\Workspace\Task;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'mobile',
        'password',
        'avatar',
        'signature',
        'bale_code',
        'personnel_code',
        'timex_code',
        'sepidar_user_id',
        'requires_local_network',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'name',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'mobile_verified_at' => 'datetime',
            'password' => 'hashed',
            'requires_local_network' => 'boolean',
        ];
    }

    /**
     * Accessor: Concatenated first and last name.
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $first = (string) ($this->first_name ?? '');
                $last = (string) ($this->last_name ?? '');

                return trim($first.' '.$last);
            },
        );
    }

    public function internalPhones(): HasMany
    {
        return $this->hasMany(UserInternalPhone::class);
    }

    /**
     * @return list<string>
     */
    public function internalPhoneDeviceIds(): array
    {
        $ids = $this->relationLoaded('internalPhones')
            ? $this->internalPhones->pluck('device_id')
            : $this->internalPhones()->pluck('device_id');

        return $ids
            ->map(fn ($id): string => (string) $id)
            ->filter(fn (string $id): bool => $id !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Issabel devices assigned to this user (cross-DB via device_id strings).
     *
     * @return Collection<int, IssabelDevice>
     */
    public function internalPhoneDevices(): Collection
    {
        $ids = $this->internalPhoneDeviceIds();
        if ($ids === []) {
            return collect();
        }

        try {
            return IssabelDevice::query()
                ->whereIn('id', $ids)
                ->get()
                ->keyBy(fn (IssabelDevice $device): string => (string) $device->id)
                ->values();
        } catch (\Throwable $e) {
            report($e);

            return collect();
        }
    }

    /**
     * @param  list<string|int>|array<int, string|int>  $deviceIds
     */
    public function syncInternalPhones(array $deviceIds): void
    {
        $normalized = collect($deviceIds)
            ->map(fn ($id): string => trim((string) $id))
            ->filter(fn (string $id): bool => $id !== '')
            ->unique()
            ->values();

        $this->internalPhones()->delete();

        if ($normalized->isEmpty()) {
            return;
        }

        $this->internalPhones()->createMany(
            $normalized->map(fn (string $deviceId): array => [
                'device_id' => $deviceId,
            ])->all()
        );
    }

    public function sepidarUser(): BelongsTo
    {
        return $this->belongsTo(SepidarUser::class, 'sepidar_user_id', 'UserID');
    }

    public function resolveSepidarCreatorId(): int
    {
        return (int) ($this->sepidar_user_id ?: config('sepidar.Creator', 1));
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'workspace_task_user')
            ->withPivot(['role', 'assigned_at', 'assigned_by'])
            ->withTimestamps();
    }

    public function assignedTasks(): BelongsToMany
    {
        return $this->tasks()->wherePivot('role', 'assignee');
    }

    public function reviewTasks(): BelongsToMany
    {
        return $this->tasks()->wherePivot('role', 'reviewer');
    }

    public function announcements(): BelongsToMany
    {
        return $this->belongsToMany(Announcement::class)
            ->withPivot('viewed_at')
            ->withTimestamps();
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(UserActivityLog::class);
    }

    public function getSignature()
    {
        $signatures = json_decode($this->signature, true);
        if ($signatures && count($signatures) > 0) {
            return asset('storage/'.$signatures[0]);
        }

        return null;
    }

    public function getAvatarUrl()
    {
        if ($this->avatar) {
            return asset('storage/'.$this->avatar);
        }

        return null;
    }

    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    public function hrRecords(): HasMany
    {
        return $this->hasMany(Record::class);
    }

    public function hrDayRecords(): HasMany
    {
        return $this->hasMany(DayRecord::class);
    }
}
