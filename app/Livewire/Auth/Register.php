<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Register extends Component
{
    public string $first_name = '';
    public string $last_name = '';
    public string $mobile = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    protected function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:255', 'unique:users,mobile'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', PasswordRule::min(8), 'confirmed'],
        ];
    }

    public function register()
    {
        $validated = $this->validate();

        // Create the user; password will be hashed via User model casts
        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'mobile' => $validated['mobile'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Log the user in
        Auth::login($user, true);

        // Optionally flash a success message
        Flux::toast(__('Registration successful.'));

        // Redirect to intended location or home
        return $this->redirectIntended(default: '/', navigate: true);
    }

    #[Layout('layouts.auth')]
    public function render()
    {
        return view('livewire.auth.register');
    }
}
