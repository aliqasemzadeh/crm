<div class="space-y-6">
    <flux:card>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <flux:select wire:model.live="fiscalYear" :label="__('app.fiscal_year')">
                @foreach($this->getFiscalYears() as $fy)
                    <flux:select.option value="{{ $fy->FiscalYearId }}">{{ $fy->Title }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model.live="startDate" :label="__('app.start_date')" mask="9999/99/99" />
            <flux:input wire:model.live="endDate" :label="__('app.end_date')" mask="9999/99/99" />

            <flux:select wire:model.live="saleType" :label="__('app.sale_type')">
                <flux:select.option value="">{{ __('app.all') }}</flux:select.option>
                <flux:select.option value="1">{{ __('app.official') }}</flux:select.option>
                <flux:select.option value="2">{{ __('app.unofficial') }}</flux:select.option>
            </flux:select>

            <div class="flex justify-end">
                <flux:button icon="file-down" wire:click="export" variant="primary">
                    {{ __('app.export_excel') }}
                </flux:button>
            </div>
        </div>

        @if($startDate && $endDate)
            @php
                $startStr = $this->persianToGregorian($startDate);
                $endStr = $this->persianToGregorian($endDate);
            @endphp
            @if($startStr && $endStr)
                @php
                    $start = \Carbon\Carbon::parse($startStr);
                    $end = \Carbon\Carbon::parse($endStr);
                @endphp
                @if($start->diffInDays($end) > 100)
                    <div class="mt-4 text-red-500 text-sm">
                        {{ __('app.date_limit_exceeded') }}
                    </div>
                @endif
            @endif
        @endif
    </flux:card>

    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('app.economic_code') }}</flux:table.column>
                <flux:table.column>{{ __('app.identification_code') }}</flux:table.column>
                <flux:table.column>{{ __('app.customer_title') }}</flux:table.column>
                <flux:table.column>{{ __('app.invoices_count') }}</flux:table.column>
                <flux:table.column>{{ __('app.total_sales') }}</flux:table.column>
                <flux:table.column>{{ __('app.total_tax') }}</flux:table.column>
                <flux:table.column>{{ __('app.net_amount') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($reportData as $row)
                    <flux:table.row :key="$row->EconomicCode . $row->IdentificationCode">
                        <flux:table.cell>{{ $row->EconomicCode }}</flux:table.cell>
                        <flux:table.cell>{{ $row->IdentificationCode }}</flux:table.cell>
                        <flux:table.cell>{{ $row->Name }} {{ $row->LastName }}</flux:table.cell>
                        <flux:table.cell>{{ $row->invoices_count }}</flux:table.cell>
                        <flux:table.cell>{{ number_format($row->total_price) }}</flux:table.cell>
                        <flux:table.cell>{{ number_format($row->total_tax) }}</flux:table.cell>
                        <flux:table.cell>{{ number_format($row->total_net_price) }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center">{{ __('app.no_results_found') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
