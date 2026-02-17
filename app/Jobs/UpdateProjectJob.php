<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class UpdateProjectJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Starting project update (git pull)...");
        $process = Process::forever()->run('git pull');

        if ($process->successful()) {
            Log::info("Git pull successful:\n" . $process->output());
        } else {
            Log::error("Git pull failed:\n" . $process->errorOutput());
        }

        Log::info("migrate...");
        Artisan::call("migrate");
        Log::info("migrate:\n" . Artisan::output());

        Log::info("route:clear...");
        Artisan::call("route:clear");
        Log::info("route:clear:\n" . Artisan::output());

        Log::info("queue:restart...");
        Artisan::call("queue:restart");
        Log::info("queue:restart:\n" . Artisan::output());
    }
}
