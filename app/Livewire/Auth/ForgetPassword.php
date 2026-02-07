<?php

namespace App\Livewire\Auth;

use App\Jobs\User\ChangePasswordCodeJob;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ForgetPassword extends Component
{
    public string $email = '';

    protected function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
        ];
    }

    public function sendResetCode()
    {
        $this->validate();

        $token = Str::random(60);

        // In a real app, you'd store this token in password_reset_tokens table
        // For this task, I'll assume we can use standard Laravel password reset or similar
        // But the requirement says "forget-password get email" and "change password check token"

        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $this->email],
            [
                'token' => \Illuminate\Support\Facades\Hash::make($token),
                'created_at' => now(),
            ]
        );

        ChangePasswordCodeJob::dispatch($this->email, $token);

        Flux::toast(__('app.login.password_reset_link_sent'));
    }

    #[Layout('layouts.auth')]
    public function render()
    {
        return view('livewire.auth.forget-password');
    }
}
