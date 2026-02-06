<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Login extends Component
{
    public string $login_id = '';
    public string $password = '';
    public bool $remember = false;

    protected function rules(): array
    {
        return [
            'login_id' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ];
    }

    public function login()
    {
        $this->validate();

        $fieldType = filter_var($this->login_id, FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile';

        $credentials = [
            $fieldType => $this->login_id,
            'password' => $this->password,
        ];

        if (! Auth::guard('web')->attempt($credentials, $this->remember)) {
            throw ValidationException::withMessages([
                'login_id' => trans('auth.failed'),
            ]);
        }

        // Regenerate session to prevent fixation
        request()->session()->regenerate();

        // Clear sensitive field
        $this->reset('password');

        // Redirect to intended page or dashboard
        return $this->redirectIntended(default: route('home'), navigate: true);
    }

    #[Layout('layouts.auth')]
    public function render()
    {
        return view('livewire.auth.login');
    }
}
