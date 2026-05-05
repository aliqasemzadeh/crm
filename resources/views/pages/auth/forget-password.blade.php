<?php

use App\Jobs\User\ChangePasswordCodeJob;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.auth')] class extends Component
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
}; ?>

<div class="space-y-6">
    <div class="text-center">
        <flux:heading size="xl">{{ __('app.reset_password') }}</flux:heading>
        <flux:subheading>{{ __('app.enter_email_to_reset') ?? 'لطفا ایمیل خود را برای بازیابی رمز عبور وارد کنید' }}</flux:subheading>
    </div>

    <form wire:submit="sendResetCode" class="flex flex-col gap-6">
        <flux:input wire:model="email" label="{{ __('app.email') }}" type="email" placeholder="email@example.com" />

        <flux:button type="submit" variant="primary" class="w-full">{{ __('app.send') }}</flux:button>
    </form>

    <flux:subheading class="text-center">
        <flux:link href="{{ route('login') }}" wire:navigate>{{ __('app.back_to_login') }}</flux:link>
    </flux:subheading>
</div>
