<?php

namespace App\Domains\Sales\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SalesPriceResolver
{
    public function resolve(int $inventoryObjectId, string $saleType, float $quantity): int
    {
        if ($saleType === 'RETAIL') {
            $price = DB::table('inventory_objects')->where('id', $inventoryObjectId)->value('retail_price_cents');
            if ($price === null) {
                throw new InvalidArgumentException("Product {$inventoryObjectId} has no retail price configured.");
            }

            return (int) $price;
        }
        $tiers = DB::table('inventory_wholesale_price_tiers')
            ->where('inventory_object_id', $inventoryObjectId)
            ->where('min_quantity', '<=', $quantity)
            ->where(fn ($query) => $query->whereNull('max_quantity')->orWhere('max_quantity', '>=', $quantity))
            ->get();
        if ($tiers->count() !== 1) {
            throw new InvalidArgumentException("Product {$inventoryObjectId} has no unambiguous wholesale price tier for quantity {$quantity}.");
        }

        return (int) $tiers->first()->unit_price_cents;
    }
}
