<flux:modal name="panels.administrator.announcement.users.modal" class="md:min-w-[600px]">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.viewed_users') }}</flux:heading>
            @if(isset($announcement))
                <flux:subheading>{{ $announcement->title }}</flux:subheading>
            @endif
        </div>

        <flux:table :paginate="$this->viewedUsers">
            <flux:table.columns>
                <flux:table.column>{{ __('app.name') }}</flux:table.column>
                <flux:table.column>{{ __('app.mobile') }}</flux:table.column>
                <flux:table.column>{{ __('app.date') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->viewedUsers as $user)
                    <flux:table.row :key="$user->id">
                        <flux:table.cell>{{ $user->name }}</flux:table.cell>
                        <flux:table.cell>{{ $user->mobile }}</flux:table.cell>
                        <flux:table.cell>{{ $user->pivot->viewed_at }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="3" class="text-center py-4">
                            {{ __('app.no_records_found') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <div class="flex">
            <flux:spacer />
            <flux:modal.close>
                <flux:button variant="ghost">{{ __('app.logout.cancel') }}</flux:button>
            </flux:modal.close>
        </div>
    </div>
</flux:modal>
