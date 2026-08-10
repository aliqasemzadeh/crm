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
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'item_refs' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

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

    public function itemCount(): int
    {
        return count($this->item_refs ?? []);
    }
}
