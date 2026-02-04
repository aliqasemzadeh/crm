<flux:dropdown position="top" align="start" class="max-lg:hidden">
    @auth
    <flux:sidebar.profile name="{{ \Illuminate\Support\Facades\Auth::user()->name ?? \Illuminate\Support\Facades\Auth::mobile() }}" />
    @endauth



    <flux:menu>
        @if(0)
        <flux:menu.radio.group>
            <flux:menu.radio checked>Olivia Martin</flux:menu.radio>
            <flux:menu.radio>Truly Delta</flux:menu.radio>
        </flux:menu.radio.group>

        <flux:menu.separator />
         @endif

        <flux:menu.item icon="arrow-right-start-on-rectangle" href="{{ route('logout') }}">{{ __('app.logout.title') }}</flux:menu.item>
    </flux:menu>
</flux:dropdown>
