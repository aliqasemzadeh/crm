<?php

namespace App\Models\ServiceCenter;

use App\Enums\AssemblyLogTypeEnum;
use App\Enums\AssemblyOrderStatusEnum;
use App\Enums\AssemblyStatusEnum;
use App\Models\Sepidar\SLS\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;

class AssemblyOrder extends Model
{
    use SoftDeletes;

    protected $table = 'service_center_assembly_orders';

    protected $fillable = [
        'code',
        'sepidar_invoice_id',
        'invoice_number',
        'customer_party_ref',
        'customer_name',
        'quantity',
        'status',
        'ordered_by_user_id',
        'notes',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => AssemblyOrderStatusEnum::class,
            'quantity' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function orderedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ordered_by_user_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'sepidar_invoice_id', 'InvoiceId');
    }

    public function parts(): HasMany
    {
        return $this->hasMany(AssemblyOrderPart::class, 'assembly_order_id')->orderBy('sort_order');
    }

    public function assemblies(): HasMany
    {
        return $this->hasMany(Assembly::class, 'assembly_order_id')->orderBy('unit_number');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AssemblyLog::class, 'assembly_order_id')->latest();
    }

    public function getStatusEnumAttribute(): AssemblyOrderStatusEnum
    {
        return $this->status instanceof AssemblyOrderStatusEnum
            ? $this->status
            : AssemblyOrderStatusEnum::tryFromSafe($this->status);
    }

    public function readyCount(): int
    {
        return $this->assemblies()
            ->whereIn('status', [
                AssemblyStatusEnum::Ready->value,
                AssemblyStatusEnum::Delivered->value,
            ])
            ->count();
    }

    public function durationMinutes(): ?int
    {
        if (! $this->started_at || ! $this->completed_at) {
            return null;
        }

        return (int) $this->started_at->diffInMinutes($this->completed_at);
    }

    public static function hasActiveForInvoice(int $invoiceId): bool
    {
        return static::query()
            ->where('sepidar_invoice_id', $invoiceId)
            ->where('status', '!=', AssemblyOrderStatusEnum::Cancelled->value)
            ->exists();
    }

    public static function generateCode(): string
    {
        $prefix = 'ASM-'.Jalalian::now()->format('Y');

        $last = static::withTrashed()
            ->where('code', 'like', $prefix.'-%')
            ->orderByDesc('id')
            ->value('code');

        $next = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $matches)) {
            $next = ((int) $matches[1]) + 1;
        }

        return sprintf('%s-%04d', $prefix, $next);
    }

    /**
     * @param  array<int, array{title: string, sepidar_item_id?: int|null, sepidar_invoice_item_id?: int|null, quantity_per_unit?: int, requires_serial?: bool, notes?: string|null}>  $bomParts
     */
    public static function createFromInvoice(
        Invoice $invoice,
        int $quantity,
        array $bomParts,
        ?string $notes = null,
        ?int $orderedByUserId = null,
    ): self {
        return DB::transaction(function () use ($invoice, $quantity, $bomParts, $notes, $orderedByUserId) {
            $party = $invoice->customer;
            $customerName = $invoice->CustomerRealName;
            if ($party) {
                $customerName = trim(implode(' ', array_filter([
                    trim((string) ($party->Name ?? '')),
                    trim((string) ($party->LastName ?? '')),
                ]))) ?: $customerName;
            }

            $order = static::create([
                'code' => static::generateCode(),
                'sepidar_invoice_id' => $invoice->InvoiceId,
                'invoice_number' => (string) $invoice->Number,
                'customer_party_ref' => $invoice->CustomerPartyRef,
                'customer_name' => $customerName,
                'quantity' => max(1, $quantity),
                'status' => AssemblyOrderStatusEnum::Pending,
                'ordered_by_user_id' => $orderedByUserId ?? Auth::id(),
                'notes' => $notes,
            ]);

            $orderParts = [];
            foreach (array_values($bomParts) as $index => $part) {
                $orderParts[] = $order->parts()->create([
                    'sepidar_item_id' => $part['sepidar_item_id'] ?? null,
                    'sepidar_invoice_item_id' => $part['sepidar_invoice_item_id'] ?? null,
                    'title' => $part['title'],
                    'quantity_per_unit' => max(1, (int) ($part['quantity_per_unit'] ?? 1)),
                    'requires_serial' => (bool) ($part['requires_serial'] ?? true),
                    'sort_order' => $index,
                    'notes' => $part['notes'] ?? null,
                ]);
            }

            for ($unit = 1; $unit <= $order->quantity; $unit++) {
                $assembly = $order->assemblies()->create([
                    'unit_number' => $unit,
                    'status' => AssemblyStatusEnum::Pending,
                ]);

                foreach ($orderParts as $orderPart) {
                    $qty = max(1, (int) $orderPart->quantity_per_unit);
                    for ($i = 0; $i < $qty; $i++) {
                        $assembly->parts()->create([
                            'assembly_order_part_id' => $orderPart->id,
                            'sepidar_item_id' => $orderPart->sepidar_item_id,
                            'title' => $orderPart->title,
                        ]);
                    }
                }
            }

            AssemblyLog::create([
                'assembly_order_id' => $order->id,
                'user_id' => $orderedByUserId ?? Auth::id(),
                'type' => AssemblyLogTypeEnum::StatusChange,
                'status' => AssemblyOrderStatusEnum::Pending->value,
                'description' => __('app.assembly_order_created_log', [
                    'code' => $order->code,
                    'invoice' => $order->invoice_number,
                ]),
            ]);

            return $order->fresh(['parts', 'assemblies.parts', 'orderedBy']);
        });
    }

    public function recalculateStatus(): void
    {
        if ($this->status === AssemblyOrderStatusEnum::Cancelled) {
            return;
        }

        $statuses = $this->assemblies()->pluck('status')->map(
            fn ($status) => $status instanceof AssemblyStatusEnum ? $status->value : (string) $status
        );

        if ($statuses->isEmpty()) {
            return;
        }

        $total = $statuses->count();
        $readyLike = $statuses->filter(fn (string $s) => in_array($s, [
            AssemblyStatusEnum::Ready->value,
            AssemblyStatusEnum::Delivered->value,
        ], true))->count();
        $inProgress = $statuses->contains(AssemblyStatusEnum::InProgress->value);
        $allDelivered = $statuses->every(fn (string $s) => $s === AssemblyStatusEnum::Delivered->value);
        $allCancelled = $statuses->every(fn (string $s) => $s === AssemblyStatusEnum::Cancelled->value);

        $newStatus = match (true) {
            $allCancelled => AssemblyOrderStatusEnum::Cancelled,
            $allDelivered => AssemblyOrderStatusEnum::Delivered,
            $readyLike === $total => AssemblyOrderStatusEnum::Ready,
            $readyLike > 0 => AssemblyOrderStatusEnum::PartiallyReady,
            $inProgress => AssemblyOrderStatusEnum::InProgress,
            default => AssemblyOrderStatusEnum::Pending,
        };

        $updates = ['status' => $newStatus];

        if ($newStatus !== AssemblyOrderStatusEnum::Pending && ! $this->started_at) {
            $updates['started_at'] = now();
        }

        if (in_array($newStatus, [AssemblyOrderStatusEnum::Ready, AssemblyOrderStatusEnum::Delivered], true)) {
            $updates['completed_at'] = $this->completed_at ?? now();
        } else {
            $updates['completed_at'] = null;
        }

        $this->update($updates);
    }
}
