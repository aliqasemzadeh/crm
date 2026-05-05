<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Issabel\Device as IssabelDevice;
use App\Models\Workspace\Task;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
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
        'internal_phone_id',
        'bale_code',
        'personnel_code',
        'timex_code',
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

    public function internalPhoneDevice(): BelongsTo
    {
        return $this->belongsTo(IssabelDevice::class, 'internal_phone_id', 'id');
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
}
