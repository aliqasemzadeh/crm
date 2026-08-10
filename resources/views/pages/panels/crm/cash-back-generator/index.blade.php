<?php

use App\Models\SetareganCo\DiscountCode;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

return new #[Layout('layouts.panels.crm')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        $this->authorize('crm_cash_back_generator_index');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[On('panels.crm.cash-back-generator.index.render')]
    public function refresh(): void
    {
        unset($this->discountCodes);
    }

    #[Computed]
    public function discountCodes()
    {
        return DiscountCode::query()
            ->when($this->search !== '', function ($query) {
                $search = $this->search;
                $query->where(function ($q) use ($search) {
                    $q->where('Code', 'like', "%{$search}%")
                        ->orWhere('NationalCode', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('Id')
            ->paginate(20);
    }

    public function codeStatus(DiscountCode $code): array
    {
        if (! $code->Status) {
            return ['label' => __('app.inactive'), 'color' => 'zinc'];
        }

        if ((int) $code->RemainCount <= 0) {
            return ['label' => __('app.cash_back_code_status_used'), 'color' => 'amber'];
        }

        if ($code->ToDate && $code->ToDate->isPast()) {
            return ['label' => __('app.cash_back_code_status_expired'), 'color' => 'red'];
        }

        return ['label' => __('app.active'), 'color' => 'green'];
    }
};
?>

<div>
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.cash_back_generator') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.cash_back_generator_description') }}</flux:subheading>
            </div>
            @can('crm_cash_back_generator_create')
                <flux:modal.trigger name="panels.crm.cash-back-generator.generator.modal">
                    <flux:button variant="primary" color="teal" icon="gift">{{ __('app.cash_back_generator_run') }}</flux:button>
                </flux:modal.trigger>
            @endcan
        </div>
        <flux:separator variant="subtle" />
    </div>

    <livewire:crm.cash-back-generator.generator :key="'cash-back-generator'" />

    <flux:table :paginate="$this->discountCodes">
        <flux:table.columns sticky class="bg-white dark:bg-zinc-900">
            <flux:table.column colspan="7" class="bg-white dark:bg-zinc-900">
                <div class="flex flex-col gap-1 pe-2 items-end">
                    <flux:input
                        size="sm"
                        placeholder="{{ __('app.search_placeholder') }}"
                        wire:model.live="search"
                    />
                </div>
            </flux:table.column>
        </flux:table.columns>
        <flux:table.columns>
            <flux:table.column>{{ __('app.code') }}</flux:table.column>
            <flux:table.column>{{ __('app.national_code') }}</flux:table.column>
            <flux:table.column>{{ __('app.cash_back_amount') }}</flux:table.column>
            <flux:table.column>{{ __('app.from_date') }}</flux:table.column>
            <flux:table.column>{{ __('app.to_date') }}</flux:table.column>
            <flux:table.column>{{ __('app.remaining') }}</flux:table.column>
            <flux:table.column>{{ __('app.status') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->discountCodes as $code)
                @php($status = $this->codeStatus($code))
                <flux:table.row :key="$code->Id">
                    <flux:table.cell class="font-mono">{{ $code->Code }}</flux:table.cell>
                    <flux:table.cell>{{ $code->NationalCode ?: '—' }}</flux:table.cell>
                    <flux:table.cell>
                        {{ number_format((float) $code->DiscountAmount) }}
                        @if($code->IsPercent)
                            <flux:badge color="blue" size="sm" class="ms-1">{{ __('app.percent') }}</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm" class="ms-1">{{ __('app.toman') }}</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $code->FromDate ? Jalalian::fromDateTime($code->FromDate)->format('Y/m/d') : '—' }}
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $code->ToDate ? Jalalian::fromDateTime($code->ToDate)->format('Y/m/d') : '—' }}
                    </flux:table.cell>
                    <flux:table.cell>{{ $code->RemainCount }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="{{ $status['color'] }}" size="sm">{{ $status['label'] }}</flux:badge>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="text-center py-4">
                        {{ __('app.no_records_found') }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
