<flux:navbar class="lg:hidden w-full">
    <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

    <flux:spacer />

    <flux:dropdown position="top" align="start">
        @auth
            <flux:sidebar.profile name="{{ \Illuminate\Support\Facades\Auth::user()->name ?? \Illuminate\Support\Facades\Auth::mobile() }}" />
        @endauth

        @guest
                <flux:profile  />
        @endguest


        <flux:menu>
            <flux:menu.radio.group>

            </flux:menu.radio.group>

            <flux:menu.separator />

            <flux:menu.item icon="arrow-right-start-on-rectangle" href="{{ route('logout') }}">{{ __('app.logout.title') }}</flux:menu.item>
        </flux:menu>
    </flux:dropdown>
</flux:navbar>
