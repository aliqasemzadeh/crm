<?php

namespace App\Models\Issabel;

use App\Models\User;
use App\Models\UserInternalPhone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Device extends Model
{
    protected $connection = 'issabel-config';

    protected $table = 'devices';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];

    /**
     * کاربران CRM که این خط داخلی به آن‌ها در user_internal_phones وصل است.
     *
     * @return Collection<int, User>
     */
    public function crmUsers(): Collection
    {
        $userIds = UserInternalPhone::query()
            ->where('device_id', (string) $this->id)
            ->pluck('user_id');

        if ($userIds->isEmpty()) {
            return collect();
        }

        return User::query()->whereIn('id', $userIds)->get();
    }
}
