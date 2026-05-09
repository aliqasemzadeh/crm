<?php

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use App\Models\Sepidar\INV\Item;
use App\Models\Sepidar\INV\ItemStockSummary;
use App\Models\Sepidar\GNR\PartyAddress;
use App\Models\Sepidar\GNR\PartyPhone;
use App\Support\IranPhoneNumberNormalizer;
use Flux\Flux;
use Illuminate\Support\Collection;

new class extends Component
{
    public $phone = '';

    public $recipientName = '';

    public $message = '';

    public $search = '';

    public $selectedItemId = null;

    public ?int $smsPartyId = null;

    #[On('panels.crm.dashboard.index.send-sms')]
    public function show($phone, $name = '')
    {
        $this->smsPartyId = null;
        $this->phone = $phone;
        $this->recipientName = $name;
        $this->message = '';
        $this->search = '';
        $this->selectedItemId = null;
        $this->modal('send-sms-modal')->show();
    }

    #[On('panels.accounting.full-report.send-sms')]
    public function showDebtReminder(?int $partyId = null, string $recipientName = '', string $debtAmount = ''): void
    {
        $this->smsPartyId = $partyId;
        $this->recipientName = $recipientName;
        $this->search = '';
        $this->selectedItemId = null;
        $this->message = __('app.full_report_debt_sms_body', [
            'name' => $recipientName !== '' ? $recipientName : __('app.customer'),
            'amount' => $debtAmount,
        ]);

        $options = $this->buildPartyPhoneOptions($partyId);
        $this->phone = $options->first()['value'] ?? '';

        $this->modal('send-sms-modal')->show();
    }

    protected function buildPartyPhoneOptions(?int $partyId): Collection
    {
        if (! $partyId) {
            return collect();
        }

        $byNorm = [];

        foreach (PartyPhone::query()->where('PartyRef', $partyId)->orderByDesc('IsMain')->orderBy('PartyPhoneId')->cursor() as $pp) {
            $raw = trim((string) $pp->Phone);
            if ($raw === '') {
                continue;
            }
            $norm = IranPhoneNumberNormalizer::normalize($raw);
            $key = $norm ?? mb_strtolower($raw);

            $suffix = ((int) ($pp->IsMain ?? 0)) === 1
                ? ' ('.__('app.phone_label_main').')'
                : '';

            $byNorm[$key] = [
                'value' => $raw,
                'label' => $raw.' — '.__('app.sms_phone_source_party_phone').$suffix,
            ];
        }

        foreach (PartyAddress::query()->where('PartyRef', $partyId)->cursor() as $addr) {
            $text = (string) ($addr->Address ?? '');
            if ($text === '') {
                continue;
            }
            if (preg_match_all('/09\d{9}/', $text, $matches)) {
                foreach ($matches[0] as $rawFound) {
                    $norm = IranPhoneNumberNormalizer::normalize($rawFound);
                    if (! $norm) {
                        continue;
                    }
                    $key = $norm;
                    if (isset($byNorm[$key])) {
                        continue;
                    }
                    $display = IranPhoneNumberNormalizer::displayForUi($norm);
                    $byNorm[$key] = [
                        'value' => $display,
                        'label' => $display.' — '.__('app.sms_phone_source_address_text'),
                    ];
                }
            }
        }

        return collect(array_values($byNorm));
    }

    #[Computed]
    public function partySmsPhoneOptions(): Collection
    {
        return $this->buildPartyPhoneOptions($this->smsPartyId);
    }

    #[Computed]
    public function items()
    {
        if (strlen($this->search) < 3) return [];

        return Item::with('image')
            ->where(function($query) {
                $query->where('Title', 'like', '%' . $this->search . '%')
                    ->orWhere('Title_En', 'like', '%' . $this->search . '%')
                    ->orWhere('Code', 'like', '%' . $this->search . '%');
            })
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function selectedItem()
    {
        if (!$this->selectedItemId) return null;

        $item = Item::with('image')->find($this->selectedItemId);
        if (!$item) return null;

        $stock = ItemStockSummary::where('ItemRef', $item->ItemID)->sum('Quantity') ?? 0;
        $lastPurchasePrice = $item->getLastPurchasePrice();
        $lastSalePrice = $item->getLastSalePrice();

        return [
            'id' => $item->ItemID,
            'name' => $item->Title,
            'iran_code' => $item->IranCode,
            'stock' => $stock,
            'last_purchase_price' => $lastPurchasePrice,
            'last_sale_price' => $lastSalePrice,
            'image' => $item->image?->Thumbnail ?? $item->image?->Image,
        ];
    }

    public function updatedSelectedItemId($value)
    {
        // Auto-add removed as per request
    }

    public function addLink()
    {
        $item = $this->selectedItem();
        if ($item && $item['iran_code']) {
            $link = "https://setaregan.co/Product/" . $item['iran_code'];

            // Append if not already in message, or just append as requested
            if ($this->message && !str_ends_with($this->message, "\n")) {
                $this->message .= "\n";
            }

            $this->message .= $link;

            Flux::toast(__('app.link_added_to_message'));
        }
    }

    public function send()
    {
        $this->validate([
            'phone' => 'required',
            'message' => 'required',
        ]);

        \App\Jobs\Notification\SendSmsMessageJob::dispatch($this->phone, $this->message);

        $this->smsPartyId = null;
        $this->modal('send-sms-modal')->close();
        Flux::toast(__('app.sms_sent_successfully'));
    }
};
?>

<flux:modal name="send-sms-modal" flyout position="right" class="space-y-6 min-w-[450px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('app.send_sms') }}</flux:heading>
                @if ($smsPartyId)
                    <flux:subheading>{{ __('app.recipient') }}: {{ $recipientName ?: __('app.customer') }}</flux:subheading>
                @else
                    <flux:subheading>{{ __('app.recipient') }}: {{ $recipientName ?: $phone }} ({{ $phone }})</flux:subheading>
                @endif
            </div>

            @if ($smsPartyId)
                <div class="space-y-2">
                    @if ($this->partySmsPhoneOptions->isNotEmpty())
                        <flux:select wire:model.live="phone" :label="__('app.select_phone_for_sms')" searchable>
                            @foreach ($this->partySmsPhoneOptions as $opt)
                                <flux:select.option value="{{ $opt['value'] }}" wire:key="sms-phone-{{ $loop->index }}">{{ $opt['label'] }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    @else
                        <flux:input wire:model="phone" :label="__('app.phone')" />
                        <flux:text size="sm" class="text-amber-600 dark:text-amber-400">{{ __('app.full_report_no_party_phones_hint') }}</flux:text>
                    @endif
                </div>
            @endif

            <div class="space-y-2">
                <flux:text size="sm" weight="medium">{{ __('app.search_item') }}</flux:text>
                <flux:text size="xs">{{ __('app.search_item_description') }}</flux:text>

                <flux:select wire:model.live="selectedItemId" variant="combobox" :filter="false" :placeholder="__('app.search_placeholder')">
                    <x-slot name="input">
                        <flux:select.input wire:model.live.debounce.500ms="search" />
                    </x-slot>

                    @foreach ($this->items as $item)
                        <flux:select.option value="{{ $item->ItemID }}" wire:key="item-{{ $item->ItemID }}">
                            <div class="flex items-center gap-2">
                                @if($item->image?->Thumbnail || $item->image?->Image)
                                    <img src="data:image/jpeg;base64,{{ base64_encode($item->image?->Thumbnail ?? $item->image?->Image) }}" class="size-6 rounded object-cover" />
                                @else
                                    <flux:icon.package variant="mini" class="text-zinc-400" />
                                @endif
                                <div>
                                    <div class="text-sm">{{ $item->Title }}</div>
                                    <div class="text-xs text-zinc-500">{{ $item->Code }}</div>
                                </div>
                            </div>
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            @if ($this->selectedItem)
                <flux:card class="space-y-2 text-sm">
                    <div class="flex items-center gap-3">
                        @if($this->selectedItem['image'])
                            <img src="data:image/jpeg;base64,{{ base64_encode($this->selectedItem['image']) }}" class="size-12 rounded border border-zinc-200 dark:border-zinc-700 object-cover" />
                        @endif
                        <flux:heading size="sm">{{ __('app.item_details') }}: {{ $this->selectedItem['name'] }}</flux:heading>
                    </div>
                    <div class="flex justify-between border-b border-zinc-100 dark:border-zinc-800 pb-1">
                        <span class="text-zinc-500">{{ __('app.item_stock') }}:</span>
                        <span class="font-medium">{{ number_format($this->selectedItem['stock']) }}</span>
                    </div>
                    <div class="flex justify-between border-b border-zinc-100 dark:border-zinc-800 pb-1 text-orange-600">
                        <span>{{ __('app.last_purchase_price') }}:</span>
                        <span class="font-medium">{{ number_format($this->selectedItem['last_purchase_price']) }} {{ __('app.rial') }}</span>
                    </div>
                    <div class="flex justify-between text-teal-600">
                        <span>{{ __('app.last_sale_price') }}:</span>
                        <span class="font-medium">{{ number_format($this->selectedItem['last_sale_price']) }} {{ __('app.rial') }}</span>
                    </div>
                </flux:card>

                <flux:button variant="primary" color="green" icon="plus" class="w-full" wire:click="addLink">
                    {{ __('app.add_to_text') }}
                </flux:button>
            @endif

            <flux:textarea
                wire:model="message"
                :label="__('app.sms_message')"
                rows="10"
                class="w-full"
            />

            <div class="flex gap-2">
                <flux:button variant="primary" color="blue" class="w-full" wire:click="send" wire:confirm="{{ __('app.are_you_sure_to_send_sms') }}">
                    {{ __('app.send_sms') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
