<x-mail::message>
# {{ __('Reset Password Notification') }}

{{ __('You are receiving this email because we received a password reset request for your account.') }}

<x-mail::button :url="route('change-password', ['token' => $token, 'email' => $email])">
{{ __('Reset Password') }}
</x-mail::button>

{{ __('This password reset link will expire in :count minutes.', ['count' => config('auth.passwords.users.expire')]) }}

{{ __('If you did not request a password reset, no further action is required.') }}

{{ __('Regards,') }}<br>
{{ config('app.name') }}
</x-mail::message>
