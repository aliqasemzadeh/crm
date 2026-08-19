<?php

use App\Models\Hr\Record;
use App\Models\UserDevice;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.panels.hr')] class extends Component
{
    public string $message = '';
    public string $statusType = '';

    #[Computed]
    public function isAccessAllowed(): bool
    {
        $config = config('hr');
        $ip = request()->ip();
        $host = request()->getHost();
        $mode = $config['check_mode'] ?? 'both';

        $ipAllowed = $this->checkIp($ip, $config['allowed_ips'] ?? []);
        $domainAllowed = in_array($host, $config['allowed_domains'] ?? []);

        return match ($mode) {
            'ip' => $ipAllowed,
            'domain' => $domainAllowed,
            'both' => $ipAllowed || $domainAllowed,
            default => false,
        };
    }

    private function checkIp(string $ip, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (str_contains($pattern, '*')) {
                $regex = '/^' . str_replace(['*', '.'], ['[0-9]+', '\\.'], $pattern) . '$/';
                if (preg_match($regex, $ip)) {
                    return true;
                }
            } elseif ($ip === $pattern) {
                return true;
            }
        }
        return false;
    }

    #[Computed]
    public function todayRecords()
    {
        return Record::where('user_id', auth()->id())
            ->whereDate('recorded_at', today())
            ->with('device')
            ->latest('recorded_at')
            ->get();
    }

    public function clockIn(string $deviceToken): void
    {
        $this->record($deviceToken, 'clock_in');
    }

    public function clockOut(string $deviceToken): void
    {
        $this->record($deviceToken, 'clock_out');
    }

    private function record(string $deviceToken, string $type): void
    {
        if (!$this->isAccessAllowed) {
            $this->message = __('app.access_denied_ip');
            $this->statusType = 'error';
            return;
        }

        $device = UserDevice::firstOrCreate(
            ['token' => $deviceToken, 'user_id' => auth()->id()],
            [
                'name' => request()->userAgent(),
                'ip' => request()->ip(),
            ]
        );

        if ($device->ip !== request()->ip()) {
            $device->update(['ip' => request()->ip()]);
        }

        Record::create([
            'user_id' => auth()->id(),
            'user_device_id' => $device->id,
            'type' => $type,
            'recorded_at' => now(),
            'is_device_approved' => $device->is_approved,
        ]);

        $this->message = $type === 'clock_in' ? __('app.clock_in_success') : __('app.clock_out_success');
        $this->statusType = 'success';

        if (!$device->is_approved) {
            $this->message .= ' ' . __('app.device_not_approved_warning');
        }

        Flux::toast($this->message);
        unset($this->todayRecords);
    }

    public function mount(): void
    {
        $this->authorize('hr_access');
    }
};

?>

<x-slot name="title">
    {{ __('app.attendance') }}
</x-slot>
<div x-data="{
    deviceToken: '',
    init() {
        let token = localStorage.getItem('attendance_device_token');
        if (!token) {
            token = 'dev_' + Math.random().toString(36).substring(2) + Date.now().toString(36);
            localStorage.setItem('attendance_device_token', token);
        }
        this.deviceToken = token;
    }
}">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('app.attendance') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('app.attendance_description') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    @if($this->isAccessAllowed)
        <div class="max-w-md mx-auto mt-6">
            <flux:card>
                <div class="flex flex-col gap-4">
                    <flux:button variant="primary" color="green" class="w-full" icon="log-in" @click="$wire.clockIn(deviceToken)">
                        {{ __('app.clock_in') }}
                    </flux:button>
                    <flux:button variant="primary" color="red" class="w-full" icon="log-out" @click="$wire.clockOut(deviceToken)">
                        {{ __('app.clock_out') }}
                    </flux:button>
                </div>
            </flux:card>
        </div>
    @else
        <flux:card class="max-w-md mx-auto mt-6">
            <div class="text-center text-red-600">
                {{ __('app.access_denied_ip') }}
            </div>
        </flux:card>
    @endif

    {{-- Today's Records --}}
    <div class="max-w-md mx-auto mt-6">
        <flux:heading size="lg" class="mb-4">{{ __('app.today_records') }}</flux:heading>
        @if($this->todayRecords->count() > 0)
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('app.type') }}</flux:table.column>
                    <flux:table.column>{{ __('app.time') }}</flux:table.column>
                    <flux:table.column>{{ __('app.status') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($this->todayRecords as $record)
                        <flux:table.row :key="$record->id">
                            <flux:table.cell>
                                @if($record->type === 'clock_in')
                                    <flux:badge color="green">{{ __('app.clock_in') }}</flux:badge>
                                @else
                                    <flux:badge color="red">{{ __('app.clock_out') }}</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>{{ \Morilog\Jalali\Jalalian::fromDateTime($record->recorded_at)->format('H:i:s') }}</flux:table.cell>
                            <flux:table.cell>
                                @if($record->is_device_approved)
                                    <flux:badge color="green" size="sm">{{ __('app.approved') }}</flux:badge>
                                @else
                                    <flux:badge color="orange" size="sm">{{ __('app.pending_approval') }}</flux:badge>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @else
            <p class="text-sm text-zinc-500">{{ __('app.no_records_today') }}</p>
        @endif
    </div>
</div>
