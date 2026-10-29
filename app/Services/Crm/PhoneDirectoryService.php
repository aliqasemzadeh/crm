<?php

namespace App\Services\Crm;

use App\Models\Sepidar\GNR\Party;
use App\Models\Sepidar\GNR\PartyPhone;
use App\Models\Voip\Phone;
use App\Support\IranPhoneNumberNormalizer;
use Illuminate\Support\Facades\Cache;

class PhoneDirectoryService
{
    public const CACHE_KEY = 'crm.voip_phones_number_to_name_v2';

    private const CACHE_TTL_SECONDS = 600;

    /**
     * Map every known phone variant → display name (Voip Phone + Sepidar PartyPhone/Party).
     *
     * @return array<string, string>
     */
    public function numberToNameMap(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function (): array {
            return $this->buildNumberToNameMap();
        });
    }

    /**
     * @return array<string, array{name: string, party_id: ?int}>
     */
    public function numberToPartyMetaMap(): array
    {
        return Cache::remember(self::CACHE_KEY.'_meta', self::CACHE_TTL_SECONDS, function (): array {
            return $this->buildNumberToPartyMetaMap();
        });
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::CACHE_KEY.'_meta');
        // Legacy key used by older dashboard / update-phone component
        Cache::forget('crm.voip_phones_number_to_name');
    }

    public function resolveName(string $raw, ?array $nameMap = null): ?string
    {
        $nameMap ??= $this->numberToNameMap();

        foreach (IranPhoneNumberNormalizer::lookupKeyVariants($raw) as $key) {
            if (isset($nameMap[$key]) && trim($nameMap[$key]) !== '') {
                return $nameMap[$key];
            }
        }

        return null;
    }

    /**
     * @return array{name: string, party_id: ?int}|null
     */
    public function resolvePartyMeta(string $raw, ?array $metaMap = null): ?array
    {
        $metaMap ??= $this->numberToPartyMetaMap();

        foreach (IranPhoneNumberNormalizer::lookupKeyVariants($raw) as $key) {
            if (isset($metaMap[$key])) {
                return $metaMap[$key];
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function buildNumberToNameMap(): array
    {
        $meta = $this->buildNumberToPartyMetaMap();
        $map = [];
        foreach ($meta as $key => $row) {
            $map[$key] = $row['name'];
        }

        return $map;
    }

    /**
     * @return array<string, array{name: string, party_id: ?int}>
     */
    private function buildNumberToPartyMetaMap(): array
    {
        $map = [];

        try {
            foreach (Phone::query()->get(['number', 'name', 'party_id']) as $phone) {
                $name = trim((string) $phone->name);
                if ($name === '') {
                    continue;
                }
                $partyId = $phone->party_id ? (int) $phone->party_id : null;
                $this->registerVariants((string) $phone->number, $name, $partyId, $map);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            PartyPhone::query()
                ->with(['party' => static fn ($q) => $q->select(['PartyId', 'Name', 'LastName'])])
                ->orderBy('PartyPhoneId')
                ->chunkById(500, function ($phones) use (&$map): void {
                    foreach ($phones as $partyPhone) {
                        $raw = trim((string) $partyPhone->Phone);
                        if ($raw === '') {
                            continue;
                        }
                        $name = $this->partyDisplayName($partyPhone->party);
                        if ($name === '') {
                            continue;
                        }
                        $partyId = $partyPhone->PartyRef ? (int) $partyPhone->PartyRef : null;
                        $this->registerVariants($raw, $name, $partyId, $map);
                    }
                }, 'PartyPhoneId');
        } catch (\Throwable $e) {
            report($e);
        }

        return $map;
    }

    /**
     * @param  array<string, array{name: string, party_id: ?int}>  $map
     */
    private function registerVariants(string $raw, string $name, ?int $partyId, array &$map): void
    {
        $payload = ['name' => $name, 'party_id' => $partyId];

        foreach (IranPhoneNumberNormalizer::lookupKeyVariants($raw) as $key) {
            // Prefer entries that already have party_id when colliding
            if (isset($map[$key]) && $map[$key]['party_id'] !== null && $partyId === null) {
                continue;
            }
            $map[$key] = $payload;
        }
    }

    private function partyDisplayName(?Party $party): string
    {
        if ($party === null) {
            return '';
        }

        return trim(implode(' ', array_filter([
            trim((string) ($party->Name ?? '')),
            trim((string) ($party->LastName ?? '')),
        ], static fn (string $p): bool => $p !== '')));
    }
}
