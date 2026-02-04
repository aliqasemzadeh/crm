<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Login extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    protected function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ];
    }

    public function login()
    {
        $validated = $this->validate();

        $credentials = [
            'email' => $validated['email'],
            'password' => $validated['password'],
        ];

        if (! Auth::guard('web')->attempt($credentials, $this->remember)) {
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // Regenerate session to prevent fixation
        request()->session()->regenerate();

        // Clear sensitive field
        $this->reset('password');

        // Redirect to intended page or dashboard
        return $this->redirectIntended(default: route('user.dashboard.index'), navigate: true);
    }

    #[Layout('layouts.auth')]
    public function render()
    {
        return view('livewire.auth.login');
    }
}
