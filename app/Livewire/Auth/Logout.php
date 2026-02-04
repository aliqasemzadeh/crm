<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Flux\Flux;

class Logout extends Component
{
    public function logout()
    {
        Auth::logout();

        session()->invalidate();
        session()->regenerateToken();

        Flux::toast(
            heading: __('main.logged_out_successfully'),
            variant: 'success',
        );

        return $this->redirectRoute('login', navigate: true);
    }

    public function cancel()
    {
        return $this->redirect(url()->previous(), navigate: true);
    }

    #[Layout('layouts.auth')]
    public function render()
    {
        return view('livewire.auth.logout');
    }
}
