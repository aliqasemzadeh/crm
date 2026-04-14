<flux:sidebar.header>
    <div class="flex items-center gap-3">
        @auth
            @if(auth()->user()->avatar)
                <flux:avatar src="{{ auth()->user()->getAvatarUrl() }}" size="sm" />
            @else
                <flux:avatar name="{{ auth()->user()->name }}" color="auto" size="sm" />
            @endif
        @endauth
        <flux:sidebar.brand
            href="{{ route('home') }}"
            name="{{ config('app.name') }}"
        />
    </div>

    <flux:sidebar.collapse class="lg:hidden" />
</flux:sidebar.header>
