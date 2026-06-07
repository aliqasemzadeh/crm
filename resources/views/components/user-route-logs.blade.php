<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\UserActivityLog;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Request;

new class extends Component
{
    use WithPagination;

    public $url;

    public function mount()
    {
        $this->url = Request::url();
    }

    #[Computed]
    public function logs()
    {
        return UserActivityLog::with('user')
            ->where('url', 'like', $this->url . '%')
            ->latest()
            ->paginate(10);
    }
};
?>

<div>
    <flux:modal name="user-route-logs" flyout position="right" class="min-w-[400px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('app.route_logs.title') }}</flux:heading>
                <flux:subheading>{{ $url }}</flux:subheading>
            </div>

            <flux:table :paginate="$this->logs">
                <flux:table.columns>
                    <flux:table.column>{{ __('app.route_logs.user') }}</flux:table.column>
                    <flux:table.column>{{ __('app.route_logs.date') }}</flux:table.column>
                    <flux:table.column>{{ __('app.route_logs.ip') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->logs as $log)
                        <flux:table.row :key="$log->id">
                            <flux:table.cell>
                                <div class="flex items-center gap-2">
                                    <flux:avatar size="xs" :src="$log->user->getAvatarUrl()" :initials="Str::substr($log->user->name, 0, 1)" />
                                    <span>{{ $log->user->name }}</span>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell dir="ltr" class="text-right">
                                {{ \Morilog\Jalali\Jalali::fromDateTime($log->created_at)->format('Y/m/d H:i') }}
                            </flux:table.cell>
                            <flux:table.cell dir="ltr" class="text-right">
                                {{ $log->ip }}
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="3" class="text-center text-zinc-500">
                                {{ __('app.route_logs.no_logs_found') }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:modal>
</div>
