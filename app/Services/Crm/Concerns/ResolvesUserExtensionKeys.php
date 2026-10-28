<?php

namespace App\Services\Crm\Concerns;

use App\Models\Issabel\Device;
use App\Models\User;
use Illuminate\Support\Collection;

trait ResolvesUserExtensionKeys
{
    /**
     * @param  Collection<int, User>  $users
     * @return list<string>
     */
    protected function extensionKeysForUsers(Collection $users): array
    {
        $keys = [];

        foreach ($users as $user) {
            $user->loadMissing('internalPhones');
            foreach ($user->internalPhoneDevices() as $device) {
                foreach ($this->extensionKeysFromDevice($device) as $key) {
                    $keys[] = $key;
                }
            }
        }

        return array_values(array_unique(array_filter($keys)));
    }

    /**
     * @return list<string>
     */
    protected function extensionKeysFromDevice(Device $device): array
    {
        $keys = [];
        foreach ([trim((string) $device->user), trim((string) $device->dial)] as $col) {
            if ($col === '') {
                continue;
            }

            $keys[] = $col;
            $digits = preg_replace('/\D+/', '', $col) ?? '';
            if ($digits !== '') {
                $keys[] = $digits;
                if (strlen($digits) <= 5) {
                    $stripped = ltrim($digits, '0');
                    if ($stripped !== '' && $stripped !== $digits) {
                        $keys[] = $stripped;
                    }
                }
            }
        }

        return array_values(array_unique(array_filter($keys)));
    }
}
