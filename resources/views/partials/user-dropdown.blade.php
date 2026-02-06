<flux:dropdown position="top" align="start" class="max-lg:hidden">
    @auth
    <flux:sidebar.profile name="{{ \Illuminate\Support\Facades\Auth::user()->name ?? \Illuminate\Support\Facades\Auth::mobile() }}" />
    @endauth



    <flux:menu>
        <flux:sidebar.nav>
                <flux:sidebar.item icon="at-sign" href="{{ route('user.change-email') }}">{{ __('app.change_email') }}</flux:sidebar.item>
                <flux:sidebar.item icon="key" href="{{ route('user.change-password') }}">{{ __('app.change_password') }}</flux:sidebar.item>
        </flux:sidebar.nav>

        <flux:menu.separator />
        <flux:menu.item icon="arrow-right-start-on-rectangle" href="{{ route('logout') }}">{{ __('app.logout.title') }}</flux:menu.item>
    </flux:menu>
</flux:dropdown>
