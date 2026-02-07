<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ChangePassword extends Component
{
    public string $token = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(string $token)
    {
        $this->token = $token;
        $this->email = request()->query('email', '');
    }

    protected function rules(): array
    {
        return [
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function changePassword()
    {
        $this->validate();

        $record = DB::table('password_reset_tokens')->where('email', $this->email)->first();

        if (!$record || !Hash::check($this->token, $record->token)) {
            $this->addError('email', trans('app.passwords_token'));
            return;
        }

        $user = User::where('email', $this->email)->first();
        if (!$user) {
            $this->addError('email', trans('app.passwords_user'));
            return;
        }

        $user->update([
            'password' => Hash::make($this->password),
        ]);

        DB::table('password_reset_tokens')->where('email', $this->email)->delete();

        Flux::toast(trans('app.passwords_reset'));

        return $this->redirect(route('login'), navigate: true);
    }

    #[Layout('layouts.auth')]
    public function render()
    {
        return view('livewire.auth.change-password');
    }
}
