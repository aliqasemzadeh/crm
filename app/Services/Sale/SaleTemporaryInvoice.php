<?php

namespace App\Services\Sale;

class SaleTemporaryInvoice
{
    public const SESSION_KEY = 'sale.temporary_invoice';

    /**
     * @return array<int, int> itemId => quantity
     */
    public function all(): array
    {
        $items = session(self::SESSION_KEY, []);

        // Migrate legacy session key if present.
        if ((! is_array($items) || $items === []) && session()->has('sale.cart')) {
            $items = session('sale.cart', []);
            session()->forget('sale.cart');

            if (is_array($items) && $items !== []) {
                $this->store($this->normalize($items));

                return $this->all();
            }
        }

        return $this->normalize(is_array($items) ? $items : []);
    }

    public function count(): int
    {
        return (int) array_sum($this->all());
    }

    public function isEmpty(): bool
    {
        return $this->all() === [];
    }

    public function quantity(int $itemId): int
    {
        return $this->all()[$itemId] ?? 0;
    }

    public function add(int $itemId, int $quantity = 1): void
    {
        if ($itemId <= 0 || $quantity <= 0) {
            return;
        }

        $items = $this->all();
        $items[$itemId] = ($items[$itemId] ?? 0) + $quantity;
        $this->store($items);
    }

    public function setQuantity(int $itemId, int $quantity): void
    {
        if ($itemId <= 0) {
            return;
        }

        $items = $this->all();

        if ($quantity <= 0) {
            unset($items[$itemId]);
        } else {
            $items[$itemId] = $quantity;
        }

        $this->store($items);
    }

    public function increment(int $itemId, int $by = 1): void
    {
        $this->add($itemId, max(1, $by));
    }

    public function decrement(int $itemId, int $by = 1): void
    {
        if ($itemId <= 0) {
            return;
        }

        $this->setQuantity($itemId, $this->quantity($itemId) - max(1, $by));
    }

    public function remove(int $itemId): void
    {
        $items = $this->all();
        unset($items[$itemId]);
        $this->store($items);
    }

    public function clear(): void
    {
        session()->forget([self::SESSION_KEY, 'sale.cart']);
    }

    /**
     * @param  array<int|string, mixed>  $items
     * @return array<int, int>
     */
    protected function normalize(array $items): array
    {
        $normalized = [];

        foreach ($items as $itemId => $quantity) {
            $id = (int) $itemId;
            $qty = (int) $quantity;

            if ($id > 0 && $qty > 0) {
                $normalized[$id] = $qty;
            }
        }

        return $normalized;
    }

    /**
     * @param  array<int, int>  $items
     */
    protected function store(array $items): void
    {
        session([self::SESSION_KEY => $items]);
    }
}
