<?php

namespace App\Services\Sepidar;

use App\Models\Sepidar\GNR\Party;
use App\Models\Sepidar\GNR\PartyPhone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PartyCreator
{
    /**
     * @param  array{name: string, last_name: string, mobile: string}  $data
     */
    public function create(array $data): Party
    {
        $name = trim($data['name']);
        $lastName = trim($data['last_name']);
        $mobile = preg_replace('/\D+/', '', trim($data['mobile'])) ?? '';

        $creator = (int) (auth()->user()?->resolveSepidarCreatorId() ?? config('sepidar.Creator', 1));
        $now = now();

        return DB::connection('sqlsrv')->transaction(function () use ($name, $lastName, $mobile, $creator, $now) {
            $template = Party::query()
                ->whereNotNull('Name')
                ->orderByDesc('PartyId')
                ->firstOrFail();

            $partyId = ((int) Party::query()->max('PartyId')) + 1;

            $attrs = $template->getAttributes();
            unset($attrs['PartyId']);

            $nullKeys = [
                'DLRef',
                'EconomicCode',
                'IdentificationCode',
                'Name_En',
                'LastName_En',
                'BirthDate',
                'RegistrationNumber',
                'Address',
                'Email',
                'Website',
                'Comment',
                'Description',
            ];

            foreach ($nullKeys as $key) {
                if (array_key_exists($key, $attrs)) {
                    $attrs[$key] = null;
                }
            }

            if (array_key_exists('Version', $attrs)) {
                $attrs['Version'] = 1;
            }

            if (array_key_exists('Creator', $attrs)) {
                $attrs['Creator'] = $creator;
            }

            if (array_key_exists('CreationDate', $attrs)) {
                $attrs['CreationDate'] = $now;
            }

            if (array_key_exists('LastModifier', $attrs)) {
                $attrs['LastModifier'] = $creator;
            }

            if (array_key_exists('LastModificationDate', $attrs)) {
                $attrs['LastModificationDate'] = $now;
            }

            if (array_key_exists('Guid', $attrs)) {
                $attrs['Guid'] = (string) Str::uuid();
            }

            $attrs['Name'] = $name;
            $attrs['LastName'] = $lastName;
            $attrs['PartyId'] = $partyId;

            $party = new Party;
            $party->forceFill($attrs);
            $party->save();

            if ($mobile !== '') {
                $phoneId = ((int) PartyPhone::query()->max('PartyPhoneId')) + 1;

                PartyPhone::query()->create([
                    'PartyPhoneId' => $phoneId,
                    'PartyRef' => $partyId,
                    'IsMain' => 1,
                    'Type' => 1,
                    'Phone' => Str::limit($mobile, 20, ''),
                    'Version' => 1,
                ]);
            }

            return $party->fresh(['phones']);
        });
    }
}
