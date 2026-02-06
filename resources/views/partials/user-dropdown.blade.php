<flux:dropdown position="top" align="start" class="max-lg:hidden">
    @auth
    <flux:sidebar.profile name="{{ \Illuminate\Support\Facades\Auth::user()->name ?? \Illuminate\Support\Facades\Auth::mobile() }}" />
    @endauth



    <flux:menu>
        <flux:menu.radio.group>
            <flux:menu.radio>{{ __('app.change_password') }}</flux:menu.radio>
        </flux:menu.radio.group>

        <flux:menu.separator />
        <flux:menu.item icon="arrow-right-start-on-rectangle" href="{{ route('logout') }}">{{ __('app.logout.title') }}</flux:menu.item>
    </flux:menu>
</flux:dropdown>
