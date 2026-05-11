<?php

namespace App\Console\Commands\Voip;

use App\Models\Sepidar\GNR\PartyPhone;
use App\Models\Sepidar\GNR\PartyRelated;
use App\Models\Voip\Phone;
use App\Support\IranPhoneNumberNormalizer;
use App\Support\PersianFinglishConverter;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ImportPhoneFromSepidarCommand extends Command
{
    protected $signature = 'app:voip:import-phones-from-sepidar';

    protected $description = 'Import PartyRelated and PartyPhone from Sepidar into voip.phones (normalized numbers). PartyPhone overwrites duplicate numbers. Manual phones (is_manual=true) are preserved.';

    public function handle(): int
    {
        $converter = new PersianFinglishConverter;
        $now = Carbon::now();
        $importedRelated = 0;
        $importedPartyPhone = 0;
        $skipped = 0;
        $preserved = 0;

        $manualNumbers = Phone::query()
            ->where('is_manual', true)
            ->pluck('number')
            ->all();
        $manualSet = array_flip($manualNumbers);

        PartyRelated::query()
            ->with(['party' => static fn ($q) => $q->select(['PartyId', 'Name', 'LastName', 'Name_En', 'LastName_En'])])
            ->orderBy('PartyRelatedId')
            ->chunkById(500, function ($relatedRows) use ($converter, $now, $manualSet, &$importedRelated, &$skipped, &$preserved) {
                $rows = [];

                foreach ($relatedRows as $related) {
                    $raw = (string) ($related->Phone ?? '');
                    $normalized = IranPhoneNumberNormalizer::normalize($raw);

                    if ($normalized === null) {
                        $skipped++;

                        continue;
                    }

                    if (isset($manualSet[$normalized])) {
                        $preserved++;

                        continue;
                    }

                    $partyId = $related->PartyRef ?? ($related->party->PartyId ?? null);
                    $relatedId = $related->PartyRelatedId ?? null;

                    $name = $this->buildPartyRelatedDisplayNameFa($related->party, $related);
                    $nameLatin = $this->buildPartyRelatedDisplayNameLatin($related->party, $related, $converter, $name);

                    $rows[] = [
                        'party_id' => $partyId,
                        'party_phone_id' => null,
                        'party_related_id' => $relatedId,
                        'is_manual' => false,
                        'number' => $normalized,
                        'name' => $name,
                        'name_latin' => $nameLatin,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                $rows = $this->uniqueRowsByNumber($rows);

                if ($rows !== []) {
                    Phone::query()->upsert(
                        $rows,
                        ['number'],
                        ['party_id', 'party_phone_id', 'party_related_id', 'name', 'name_latin', 'updated_at']
                    );
                    $importedRelated += count($rows);
                }
            }, 'PartyRelatedId');

        PartyPhone::query()
            ->with(['party' => static fn ($q) => $q->select(['PartyId', 'Name', 'LastName', 'Name_En', 'LastName_En'])])
            ->orderBy('PartyPhoneId')
            ->chunkById(500, function ($partyPhones) use ($converter, $now, $manualSet, &$importedPartyPhone, &$skipped, &$preserved) {
                $rows = [];

                foreach ($partyPhones as $partyPhone) {
                    $raw = (string) ($partyPhone->Phone ?? '');
                    $normalized = IranPhoneNumberNormalizer::normalize($raw);

                    if ($normalized === null) {
                        $skipped++;

                        continue;
                    }

                    if (isset($manualSet[$normalized])) {
                        $preserved++;

                        continue;
                    }

                    $partyId = $partyPhone->PartyRef ?? ($partyPhone->party->PartyId ?? null);
                    $partyPhoneId = $partyPhone->PartyPhoneId ?? null;

                    $name = $this->buildPartyDisplayName($partyPhone->party);
                    $nameLatin = '';
                    if ($name !== '') {
                        $translated = trim($converter->convert($name));
                        $spaced = preg_replace('/\s+/u', ' ', $translated);
                        $nameLatin = Str::title(is_string($spaced) ? $spaced : '');
                    }

                    $rows[] = [
                        'party_id' => $partyId,
                        'party_phone_id' => $partyPhoneId,
                        'party_related_id' => null,
                        'is_manual' => false,
                        'number' => $normalized,
                        'name' => $name,
                        'name_latin' => $nameLatin,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                $rows = $this->uniqueRowsByNumber($rows);

                if ($rows !== []) {
                    Phone::query()->upsert(
                        $rows,
                        ['number'],
                        ['party_id', 'party_phone_id', 'party_related_id', 'name', 'name_latin', 'updated_at']
                    );
                    $importedPartyPhone += count($rows);
                }
            }, 'PartyPhoneId');

        $this->info("Upserted {$importedRelated} PartyRelated phone row(s) and {$importedPartyPhone} PartyPhone row(s); preserved {$preserved} manual number(s); skipped {$skipped} invalid or empty number(s).");

        return self::SUCCESS;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function uniqueRowsByNumber(array $rows): array
    {
        $byNumber = [];

        foreach ($rows as $row) {
            $byNumber[$row['number']] = $row;
        }

        return array_values($byNumber);
    }

    private function buildPartyDisplayName(?object $party): string
    {
        if ($party === null) {
            return '';
        }

        $parts = array_filter([
            isset($party->Name) ? trim((string) $party->Name) : '',
            isset($party->LastName) ? trim((string) $party->LastName) : '',
        ], static fn (string $p): bool => $p !== '');

        return trim(implode(' ', $parts));
    }

    /**
     * e.g. «علوم پزشکی (شیرین محمدی)»: post/سمت + نام فرد مرتبط؛ اگر پست خالی بود نام طرف اصلی در پرانتز.
     */
    private function buildPartyRelatedDisplayNameFa(?object $party, object $related): string
    {
        $post = isset($related->Post) ? trim((string) $related->Post) : '';
        $name = isset($related->Name) ? trim((string) $related->Name) : '';

        if ($post !== '' && $name !== '') {
            return $post.' ('.$name.')';
        }

        if ($name !== '') {
            $parent = $this->buildPartyDisplayName($party);

            return $parent !== '' ? $parent.' ('.$name.')' : $name;
        }

        if ($post !== '') {
            return $post;
        }

        return $this->buildPartyDisplayName($party);
    }

    private function buildPartyRelatedDisplayNameLatin(?object $party, object $related, PersianFinglishConverter $converter, string $faFallback): string
    {
        $post = isset($related->Post_En) ? trim((string) $related->Post_En) : '';
        $name = isset($related->Name_En) ? trim((string) $related->Name_En) : '';

        if ($post !== '' && $name !== '') {
            return $post.' ('.$name.')';
        }

        if ($name !== '') {
            $parent = $this->buildPartyDisplayNameLatin($party);

            return $parent !== '' ? $parent.' ('.$name.')' : $name;
        }

        if ($post !== '') {
            return $post;
        }

        $parentLatin = $this->buildPartyDisplayNameLatin($party);
        if ($parentLatin !== '') {
            return $parentLatin;
        }

        $translated = trim($converter->convert($faFallback));
        $spaced = preg_replace('/\s+/u', ' ', $translated);

        return Str::title(is_string($spaced) ? $spaced : '');
    }

    private function buildPartyDisplayNameLatin(?object $party): string
    {
        if ($party === null) {
            return '';
        }

        $parts = array_filter([
            isset($party->Name_En) ? trim((string) $party->Name_En) : '',
            isset($party->LastName_En) ? trim((string) $party->LastName_En) : '',
        ], static fn (string $p): bool => $p !== '');

        return trim(implode(' ', $parts));
    }
}
