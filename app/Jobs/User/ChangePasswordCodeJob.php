<?php

namespace App\Jobs\User;

use App\Mail\User\ChangePasswordCodeMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class ChangePasswordCodeJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public string $email, public string $token)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->email)->send(new ChangePasswordCodeMail($this->email, $this->token));
    }
}
