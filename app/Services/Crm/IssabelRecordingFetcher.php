<?php

namespace App\Services\Crm;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use phpseclib4\Net\SFTP;
use Throwable;

class IssabelRecordingFetcher
{
    /**
     * Download a CDR recordingfile to local private storage and return absolute path.
     */
    public function fetchToLocal(string $recordingFile, CarbonInterface $callDate, string $uniqueId): ?string
    {
        $recordingFile = trim($recordingFile);
        if ($recordingFile === '') {
            return null;
        }

        $candidates = $this->candidateRemotePaths($recordingFile, $callDate);
        $contents = $this->readFromLocalMount($candidates)
            ?? $this->readViaSftp($candidates);

        if ($contents === null || $contents === '') {
            Log::info('Issabel recording not found.', [
                'recordingfile' => $recordingFile,
                'candidates' => $candidates,
            ]);

            return null;
        }

        $extension = pathinfo($recordingFile, PATHINFO_EXTENSION) ?: 'wav';
        $safeId = Str::slug($uniqueId, '_') ?: Str::random(8);
        $relative = sprintf(
            'call-reports/%s/%s.%s',
            $callDate->format('Y-m-d'),
            $safeId,
            $extension
        );

        Storage::disk('local')->put($relative, $contents);

        return Storage::disk('local')->path($relative);
    }

    public function deleteLocal(?string $absolutePath): void
    {
        if ($absolutePath === null || $absolutePath === '') {
            return;
        }

        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    /**
     * @param  list<string>  $candidates
     */
    private function readFromLocalMount(array $candidates): ?string
    {
        $root = rtrim((string) config('crm.call_report.local_mount_path', ''), '\\/');
        if ($root === '' || ! is_dir($root)) {
            return null;
        }

        foreach ($candidates as $candidate) {
            $path = $root.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $candidate);
            if (is_file($path)) {
                $contents = @file_get_contents($path);

                return $contents === false ? null : $contents;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $candidates
     */
    private function readViaSftp(array $candidates): ?string
    {
        $host = (string) config('crm.call_report.sftp.host', '');
        $username = (string) config('crm.call_report.sftp.username', '');
        $password = (string) config('crm.call_report.sftp.password', '');
        $port = (int) config('crm.call_report.sftp.port', 22);
        $root = rtrim((string) config('crm.call_report.recordings_base_path', '/var/spool/asterisk/monitor'), '/');

        if ($host === '' || $username === '') {
            return null;
        }

        try {
            $sftp = new SFTP($host, $port, (int) config('crm.call_report.sftp.timeout', 30));
            if (! $sftp->login($username, $password)) {
                Log::warning('Issabel SFTP login failed.', ['host' => $host, 'user' => $username]);

                return null;
            }

            foreach ($candidates as $candidate) {
                $remote = str_starts_with($candidate, '/')
                    ? $candidate
                    : $root.'/'.ltrim($candidate, '/');

                if (! $sftp->file_exists($remote)) {
                    continue;
                }

                $contents = $sftp->get($remote);
                if (is_string($contents) && $contents !== '') {
                    return $contents;
                }
            }
        } catch (Throwable $e) {
            report($e);
            Log::warning('Issabel SFTP fetch failed.', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function candidateRemotePaths(string $recordingFile, CarbonInterface $callDate): array
    {
        $normalized = str_replace('\\', '/', $recordingFile);
        $normalized = ltrim($normalized, '/');

        $base = trim((string) config('crm.call_report.recordings_base_path', ''), '/');
        $fileName = basename($normalized);
        $y = $callDate->format('Y');
        $m = $callDate->format('m');
        $d = $callDate->format('d');

        $candidates = [
            $normalized,
            $fileName,
            "{$y}/{$m}/{$d}/{$fileName}",
            "{$y}-{$m}-{$d}/{$fileName}",
        ];

        if ($base !== '' && str_contains($normalized, $base.'/')) {
            $candidates[] = ltrim(Str::after($normalized, $base.'/'), '/');
        }

        // Absolute path as stored in some Issabel setups
        if (str_starts_with($recordingFile, '/')) {
            array_unshift($candidates, $recordingFile);
        }

        return array_values(array_unique(array_filter($candidates)));
    }
}
