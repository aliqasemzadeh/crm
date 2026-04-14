<flux:navbar class="lg:hidden w-full">
    <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

    <flux:spacer />

    <flux:dropdown position="top" align="start">
        @auth
            @if (\Illuminate\Support\Facades\Auth::user()->avatar)
                <flux:sidebar.profile src="{{ \Illuminate\Support\Facades\Auth::user()->getAvatarUrl() }}" name="{{ auth()->user()->name ?? __('app.no_name') }}" size="xs" />
            @else
                <flux:sidebar.profile name="{{ auth()->user()->name ?? __('app.no_name') }}" color="auto" size="xs" />
            @endif
        @endauth

        @guest
                <flux:profile  />
        @endguest


        <flux:menu>
            <flux:sidebar.nav>
                <flux:sidebar.item icon="at-sign" href="{{ route('panels.user.setting.change-email') }}">{{ __('app.change_email') }}</flux:sidebar.item>
                <flux:sidebar.item icon="key" href="{{ route('panels.user.setting.change-email') }}">{{ __('app.change_password') }}</flux:sidebar.item>
            </flux:sidebar.nav>

            <flux:menu.separator />

            <flux:menu.item icon="arrow-right-start-on-rectangle" href="{{ route('logout') }}">{{ __('app.logout.title') }}</flux:menu.item>
        </flux:menu>
    </flux:dropdown>
</flux:navbar>
