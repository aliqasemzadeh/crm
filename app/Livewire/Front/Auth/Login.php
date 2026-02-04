<?php

namespace App\Livewire\Front\Auth;

use Livewire\Attributes\Layout;
use Livewire\Component;

class Login extends Component
{
    public string $email = '';
    public string $password = '';

    public function login()
    {

    }

    #[Layout('layouts.auth')]
    public function render()
    {
        return view('livewire.front.auth.login');
    }
}
