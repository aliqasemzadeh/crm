<div>
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
