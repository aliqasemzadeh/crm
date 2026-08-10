<?php

namespace App\Models\Sepidar\Local\INV;

use App\Models\Sepidar\INV\Item;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class Cluster extends Model
{
    protected $fillable = [
        'title',
        'description',
        'item_refs',
        'available_item_refs',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'item_refs' => 'array',
            'available_item_refs' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return Collection<int, Item>
     */
    public function items(): Collection
    {
        $refs = collect($this->item_refs ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($refs->isEmpty()) {
            return collect();
        }

        return Item::query()
            ->whereIn('ItemID', $refs->all())
            ->orderBy('Title')
            ->get();
    }

    /**
     * @return Collection<int, Item>
     */
    public function availableItems(): Collection
    {
        $refs = collect($this->available_item_refs ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($refs->isEmpty()) {
            return collect();
        }

        return Item::query()
            ->whereIn('ItemID', $refs->all())
            ->orderBy('Title')
            ->get();
    }

    public function itemCount(): int
    {
        return count($this->item_refs ?? []);
    }

    public function availableItemCount(): int
    {
        return count($this->available_item_refs ?? []);
    }

    public function syncAvailableItemRefs(?string $fiscalYearRef = null): bool
    {
        $fiscalYearRef ??= (string) config('sepidar.FiscalYearRef');

        $refs = collect($this->item_refs ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($refs->isEmpty()) {
            if (($this->available_item_refs ?? []) === []) {
                return false;
            }

            return $this->update(['available_item_refs' => []]);
        }

        $inStockIds = Item::query()
            ->whereIn('ItemID', $refs->all())
            ->whereInStock($fiscalYearRef)
            ->pluck('ItemID')
            ->map(fn ($id) => (int) $id)
            ->flip();

        $available = $refs
            ->filter(fn (int $id) => isset($inStockIds[$id]))
            ->values()
            ->all();

        if ($available === ($this->available_item_refs ?? [])) {
            return false;
        }

        return $this->update(['available_item_refs' => $available]);
    }
}
