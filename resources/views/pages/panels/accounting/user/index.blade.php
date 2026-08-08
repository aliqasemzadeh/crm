<?php

use App\Models\Sepidar\FMK\User as SepidarUser;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

new #[Layout('layouts.panels.accounting')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $sortBy = 'invoice_count';

    public string $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->authorize('accounting_user_index');
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function users()
    {
        $fiscalYearRef = config('sepidar.FiscalYearRef');

        return SepidarUser::query()
            ->where('IsDeleted', 0)
            ->withCount([
                'invoices as invoice_count' => fn (Builder $query) => $query->where('FiscalYearRef', $fiscalYearRef),
            ])
            ->when($this->search !== '', function (Builder $query) {
                $search = '%'.$this->search.'%';
                $query->where(function (Builder $inner) use ($search) {
                    $inner->where('Name', 'like', $search)
                        ->orWhere('Name_En', 'like', $search)
                        ->orWhere('UserName', 'like', $search);
                });
            })
            ->when($this->sortBy === 'invoice_count', function (Builder $query) {
                $query->orderBy('invoice_count', $this->sortDirection);
            })
            ->when($this->sortBy === 'Name', function (Builder $query) {
                $query->orderBy('Name', $this->sortDirection);
            })
            ->when($this->sortBy === 'UserName', function (Builder $query) {
                $query->orderBy('UserName', $this->sortDirection);
            })
            ->when($this->sortBy === 'CreationDate', function (Builder $query) {
                $query->orderBy('CreationDate', $this->sortDirection);
            })
            ->paginate(50);
    }
};
?>

<x-slot name="title">
    {{ __('app.accounting_users') }}
</x-slot>

<div>
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.accounting_users') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.accounting_users_description') }}</flux:subheading>
            </div>
        </div>

        <flux:separator variant="subtle" />
    </div>

    <flux:card class="panel-filter-card mb-4">
        <flux:input
            wire:model.live.debounce.400ms="search"
            icon="search"
            placeholder="{{ __('app.search_placeholder') }}"
        />
    </flux:card>

    <flux:table :paginate="$this->users">
        <flux:table.columns>
            <flux:table.column>#</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'Name'" :direction="$sortDirection" wire:click="sort('Name')">{{ __('app.name') }}</flux:table.column>
            <flux:table.column>{{ __('app.name_en') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'UserName'" :direction="$sortDirection" wire:click="sort('UserName')">{{ __('app.username') }}</flux:table.column>
            <flux:table.column>{{ __('app.status') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'invoice_count'" :direction="$sortDirection" wire:click="sort('invoice_count')">{{ __('app.invoice_count') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'CreationDate'" :direction="$sortDirection" wire:click="sort('CreationDate')">{{ __('app.created_at') }}</flux:table.column>
            <flux:table.column class="w-1 whitespace-nowrap">{{ __('app.options') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->users as $index => $user)
                <flux:table.row :key="$user->UserID">
                    <flux:table.cell>{{ $this->users->firstItem() + $index }}</flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $user->Name ?: '-' }}</flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $user->Name_En ?: '-' }}</flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $user->UserName }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="{{ $user->Status ? 'emerald' : 'zinc' }}" size="sm">
                            {{ $user->Status ? __('app.active') : __('app.inactive') }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ number_format($user->invoice_count) }}</flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">
                        {{ $user->CreationDate ? Jalalian::fromDateTime($user->CreationDate)->format('%Y-%m-%d') : '-' }}
                    </flux:table.cell>
                    <flux:table.cell class="w-1 whitespace-nowrap">
                        <flux:tooltip content="{{ __('app.user_invoices_report') }}">
                            <flux:button
                                size="xs"
                                variant="primary"
                                color="sky"
                                icon="chart-column"
                                icon:variant="outline"
                                href="{{ route('panels.accounting.user.report', $user->UserID) }}"
                                wire:navigate
                            />
                        </flux:tooltip>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>
