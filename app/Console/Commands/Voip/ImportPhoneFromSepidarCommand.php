<?php

namespace App\Console\Commands\Voip;

use App\Models\Sepidar\GNR\PartyPhone;
use App\Models\Voip\Phone;
use App\Support\PersianFinglishConverter;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ImportPhoneFromSepidarCommand extends Command
{
    protected $signature = 'app:voip:import-phones-from-sepidar';

    protected $description = 'Import PartyPhone records from Sepidar (SQL Server) into MySQL phones with normalized numbers and Finglish names.';

    public function handle(): int
    {
        $converter = new PersianFinglishConverter;
        $now = Carbon::now();
        $imported = 0;
        $skipped = 0;

        PartyPhone::query()
            ->with(['party' => static fn ($q) => $q->select(['PartyId', 'Name', 'LastName'])])
            ->orderBy('PartyPhoneId')
            ->chunkById(500, function ($partyPhones) use ($converter, $now, &$imported, &$skipped) {
                $rows = [];

                foreach ($partyPhones as $partyPhone) {
                    $raw = (string) ($partyPhone->Phone ?? '');
                    $normalized = $this->normalizeIranPhoneNumber($raw);

                    if ($normalized === null) {
                        $skipped++;

                        continue;
                    }

                    $name = $this->buildPartyDisplayName($partyPhone->party);
                    $nameLatin = '';
                    if ($name !== '') {
                        $translated = trim($converter->convert($name));
                        $spaced = preg_replace('/\s+/u', ' ', $translated);
                        $nameLatin = Str::title(is_string($spaced) ? $spaced : '');
                    }

                    $rows[] = [
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
                        ['name', 'name_latin', 'updated_at']
                    );
                    $imported += count($rows);
                }
            }, 'PartyPhoneId');

        $this->info("Upserted {$imported} phone row(s); skipped {$skipped} invalid or empty number(s).");

        return self::SUCCESS;
    }

    /**
     * @param  array<int, array{number: string, name: string, name_latin: string, created_at: Carbon, updated_at: Carbon}>  $rows
     * @return array<int, array{number: string, name: string, name_latin: string, created_at: Carbon, updated_at: Carbon}>
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

    private function normalizeIranPhoneNumber(string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if ($digits === '' || strlen($digits) < 5) {
            return null;
        }

        if (str_starts_with($digits, '09')) {
            return substr($digits, 1);
        }

        if (str_starts_with($digits, '071')) {
            return substr($digits, 3);
        }

        if (str_starts_with($digits, '0')) {
            return substr($digits, 1);
        }

        return $digits;
    }
}
