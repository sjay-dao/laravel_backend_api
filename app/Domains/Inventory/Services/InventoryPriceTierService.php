<?php

namespace App\Domains\Inventory\Services;

use App\Domains\Inventory\Models\InventoryObject;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryPriceTierService
{
    public function replace(InventoryObject $inventoryObject, array $tiers): void
    {
        usort($tiers, fn (array $a, array $b) => (float) $a['min_quantity'] <=> (float) $b['min_quantity']);
        $previousMaximum = null;
        foreach ($tiers as $index => $tier) {
            $minimum = (float) $tier['min_quantity'];
            $maximum = $tier['max_quantity'] ?? null;
            $maximum = $maximum === null ? null : (float) $maximum;
            if ($minimum <= 0 || ($maximum !== null && $maximum < $minimum) || ($index > 0 && ($previousMaximum === null || $minimum <= $previousMaximum))) {
                throw ValidationException::withMessages(["wholesale_price_tiers.$index" => ['Wholesale price tiers must be valid and must not overlap.']]);
            }
            $previousMaximum = $maximum;
        }
        DB::table('inventory_wholesale_price_tiers')->where('inventory_object_id', $inventoryObject->id)->delete();
        foreach ($tiers as $tier) {
            DB::table('inventory_wholesale_price_tiers')->insert(['inventory_object_id' => $inventoryObject->id, 'min_quantity' => $tier['min_quantity'], 'max_quantity' => $tier['max_quantity'] ?? null, 'unit_price_cents' => $tier['unit_price_cents'], 'created_at' => now(), 'updated_at' => now()]);
        }
    }
}
