<div>
    <flux:header class="bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:brand href="#" logo="/img/demo/logo.png" name="Acme Inc." class="max-lg:hidden dark:hidden" />
        <flux:brand href="#" logo="/img/demo/dark-mode-logo.png" name="Acme Inc." class="max-lg:hidden! hidden dark:flex" />

        <flux:navbar class="max-lg:hidden">
            <flux:dropdown>
                <flux:navbar.item icon:trailing="chevron-down">Recent</flux:navbar.item>

                <flux:navmenu>
                    <flux:navbar.item href="#">Projects</flux:navbar.item>
                    <flux:navbar.item href="#">Tasks</flux:navbar.item>
                    <flux:navbar.item href="#">Files</flux:navbar.item>
                </flux:navmenu>
            </flux:dropdown>

            <flux:dropdown>
                <flux:navbar.item icon:trailing="chevron-down">Starred</flux:navbar.item>

                <flux:navmenu>
                    <flux:navbar.item href="#">Users</flux:navbar.item>
                    <flux:navbar.item href="#">Events</flux:navbar.item>
                    <flux:navbar.item href="#">Products</flux:navbar.item>
                </flux:navmenu>
            </flux:dropdown>
        </flux:navbar>

        <flux:spacer />

        <flux:navbar class="mr-4">
            <flux:navbar.item square icon="magnifying-glass" href="#" label="Search" />
            <flux:navbar.item class="max-lg:hidden" square icon="cog-6-tooth" href="#" label="Settings" />
            <flux:navbar.item class="max-lg:hidden" square icon="information-circle" href="#" label="Help" />
        </flux:navbar>

        <flux:profile avatar="/img/demo/user.png" />
    </flux:header>

    <flux:sidebar sticky collapsible="mobile" class="lg:hidden bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700">
        <flux:sidebar.header>
            <flux:sidebar.brand
                href="#"
                logo="https://fluxui.dev/img/demo/logo.png"
                logo:dark="https://fluxui.dev/img/demo/dark-mode-logo.png"
                name="Acme Inc."
            />

            <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <flux:sidebar.item icon="home" href="#" current>Home</flux:sidebar.item>
            <flux:sidebar.item icon="inbox" badge="12" href="#">Inbox</flux:sidebar.item>
            <flux:sidebar.item icon="document-text" href="#">Documents</flux:sidebar.item>
            <flux:sidebar.item icon="calendar" href="#">Calendar</flux:sidebar.item>

            <flux:sidebar.group expandable heading="Favorites" class="grid">
                <flux:sidebar.item href="#">Marketing site</flux:sidebar.item>
                <flux:sidebar.item href="#">Android app</flux:sidebar.item>
                <flux:sidebar.item href="#">Brand guidelines</flux:sidebar.item>
            </flux:sidebar.group>
        </flux:sidebar.nav>

        <flux:sidebar.spacer />

        <flux:sidebar.nav>
            <flux:sidebar.item icon="cog-6-tooth" href="#">Settings</flux:sidebar.item>
            <flux:sidebar.item icon="information-circle" href="#">Help</flux:sidebar.item>
        </flux:sidebar.nav>
    </flux:sidebar>

    <flux:main>
        <div class="flex flex-col md:flex-row gap-6 justify-between md:items-center mb-6">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="#" divider="slash">Acme Inc.</flux:breadcrumbs.item>
                <flux:breadcrumbs.item href="#" divider="slash">iOS App V2</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            <div class="flex gap-4">
                <flux:dropdown position="bottom" align="end">
                    <flux:button size="sm" variant="filled" icon:trailing="chevron-down">Filters</flux:button>

                    <flux:menu>
                        <flux:menu.item>Archive</flux:menu.item>
                        <flux:menu.item>Delete</flux:menu.item>
                    </flux:menu>
                </flux:dropdown>

                <flux:tabs variant="segmented" size="sm" class="-my-px h-auto! max-md:hidden">
                    <flux:tab name="board" selected>Board</flux:tab>
                    <flux:tab name="list">List</flux:tab>
                    <flux:tab name="timeline">Timeline</flux:tab>
                </flux:tabs>

                <flux:separator vertical class="my-2" />

                <flux:avatar.group>
                    @foreach (['Caleb Porzio', 'River Porzio', 'Knox Porzio'] as $item)
                        <flux:avatar size="sm" tooltip name="{{ $item }}" src="https://i.pravatar.cc/100?img={{ $loop->index + 12 }}" />
                    @endforeach

                    <flux:avatar size="sm">3+</flux:avatar>
                </flux:avatar.group>

                <flux:button variant="filled" size="sm">Invite</flux:button>
            </div>
        </div>

        <div class="overflow-x-auto -m-6 p-6">
            <div class="flex gap-4">
                @foreach ($this->columns as $column)
                    <div>
                        <div class="rounded-lg w-80 max-w-80 bg-zinc-400/5 dark:bg-zinc-900">
                            <div class="px-4 py-4 flex justify-between items-start">
                                <div>
                                    <flux:heading>{{ $column['title'] }}</flux:heading>
                                    <flux:subheading class="mb-0!">11 tasks</flux:subheading>
                                </div>
                                <flux:button variant="subtle" icon="ellipsis-horizontal" size="sm" />
                            </div>
                            <div class="flex flex-col gap-2 px-2">
                                @foreach ($column['cards'] as $card)
                                    <div class="bg-white rounded-lg shadow-xs border border-zinc-200 dark:border-white/10 dark:bg-zinc-800 p-3 space-y-2">
                                        <div class="flex gap-2">
                                            @foreach ($card['badges'] as $badge)
                                                <flux:badge :color="$badge['color']" size="sm">{{ $badge['title'] }}</flux:badge>
                                            @endforeach
                                        </div>
                                        <flux:heading>{{ $card['title'] }}</flux:heading>
                                    </div>
                                @endforeach
                            </div>
                            <div class="px-2 py-2">
                                <flux:button variant="subtle" icon="plus" size="sm" class="w-full justify-start!">New task</flux:button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </flux:main>
</div>
