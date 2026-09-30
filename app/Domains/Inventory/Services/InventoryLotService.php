<?php

namespace App\Domains\Inventory\Services;

use App\Domains\Inventory\Models\InventoryLot;
use App\Domains\Inventory\Models\InventoryMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InventoryLotService
{
    /** Receives a separately owned quantity into the canonical Inventory ledger. */
    public function receive(array $data, int $actorId): InventoryLot
    {
        return DB::transaction(function () use ($data, $actorId) {
            $ownership = strtoupper((string) $data['ownership']);
            if (! in_array($ownership, ['OWNED', 'CONSIGNMENT'], true)) {
                $this->invalid('ownership', 'Ownership must be OWNED or CONSIGNMENT.');
            }

            $warehouse = DB::table('warehouses')->where('id', $data['warehouse_id'])->where('is_active', true)->lockForUpdate()->first();
            if (! $warehouse) {
                $this->invalid('warehouse_id', 'Select an active warehouse.');
            }
            $product = DB::table('inventory_objects')->where('id', $data['inventory_object_id'])->where('track_inventory', true)->where('is_active', true)->lockForUpdate()->first();
            if (! $product) {
                $this->invalid('inventory_object_id', 'Select an active stock-tracked product.');
            }

            $supplierId = $data['supplier_id'] ?? null;
            if ($ownership === 'CONSIGNMENT' && ! $supplierId) {
                $this->invalid('supplier_id', 'A consignment lot must identify its dealer or consignor.');
            }
            if ($supplierId && ! DB::table('suppliers')->where('id', $supplierId)->where('is_active', true)->whereNull('deleted_at')->exists()) {
                $this->invalid('supplier_id', 'Select an active supplier or dealer.');
            }

            $unit = DB::table('inventory_object_units')->where('inventory_object_id', $product->id)->where('unit_id', $product->unit_id)->where('conversion_factor', 1)->first();
            if (! $unit) {
                $this->invalid('inventory_object_id', 'The product must have its base unit configured with a conversion factor of one.');
            }
            $quantity = (float) $data['quantity_received'];
            $cost = (int) $data['settlement_cost_cents'];
            if ($quantity <= 0 || $cost < 0) {
                $this->invalid('quantity_received', 'Received quantity must be positive and settlement cost cannot be negative.');
            }

            $lot = InventoryLot::create([
                'inventory_object_id' => $product->id, 'inventory_object_unit_id' => $unit->id,
                'warehouse_id' => $warehouse->id, 'supplier_id' => $supplierId, 'ownership' => $ownership,
                'quantity_received' => $quantity, 'quantity_available' => $quantity, 'settlement_cost_cents' => $cost,
                'received_date' => $data['received_date'], 'notes' => $data['notes'] ?? null, 'created_by' => $actorId,
            ]);
            $movementItemId = $this->recordMovement($warehouse, $unit->id, $quantity, $lot->id, $actorId, $data['received_date'], $data['notes'] ?? null);
            DB::table('inventory_lot_movements')->insert([
                'inventory_lot_id' => $lot->id, 'inventory_movement_item_id' => $movementItemId,
                'quantity' => $quantity, 'created_at' => now(), 'updated_at' => now(),
            ]);

            return $lot->fresh(['supplier', 'inventoryObject', 'inventoryObjectUnit.unit', 'warehouse']);
        }, 3);
    }

    private function recordMovement(object $warehouse, int $objectUnitId, float $quantity, int $lotId, int $actorId, string $receivedDate, ?string $notes): int
    {
        $type = DB::table('inventory_movement_types')->where('code', 'LOT_RECEIPT')->first();
        if (! $type || $type->direction !== 'IN') {
            throw new \LogicException('The LOT_RECEIPT inventory movement type is missing.');
        }
        $movement = InventoryMovement::create([
            'transaction_no' => 'LOT-'.Str::uuid(), 'movement_type_id' => $type->id, 'movement_date' => $receivedDate,
            'branch_id' => $warehouse->branch_id, 'warehouse_id' => $warehouse->id,
            'reference_type' => InventoryLot::class, 'reference_id' => $lotId, 'remarks' => $notes, 'created_by' => $actorId,
        ]);

        return DB::table('inventory_movement_items')->insertGetId([
            'inventory_movement_id' => $movement->id, 'inventory_object_unit_id' => $objectUnitId,
            'quantity' => $quantity, 'remarks' => 'Lot receipt', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function invalid(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => [$message]]);
    }
}
