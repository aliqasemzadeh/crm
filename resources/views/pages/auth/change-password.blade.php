<?php

use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.auth')] class extends Component
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
}; ?>

<div class="space-y-6">
    <flux:heading class="text-center" size="xl">{{ __('app.reset_password') }}</flux:heading>

    <form wire:submit="changePassword" class="flex flex-col gap-6">
        <flux:input wire:model="email" label="{{ __('app.email') }}" type="email" readonly />

        <flux:input wire:model="password" label="{{ __('app.new_password') }}" type="password" viewable />
        <flux:input wire:model="password_confirmation" label="{{ __('app.password_confirmation') }}" type="password" viewable />

        <flux:button type="submit" variant="primary" class="w-full">{{ __('app.update') }}</flux:button>
    </form>
</div>
