<?php

namespace App\Domains\Inventory\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryLotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ownership' => $this->ownership,
            'quantity_received' => $this->quantity_received,
            'quantity_available' => $this->quantity_available,
            'quantity_sold' => (string) ((float) $this->quantity_received - (float) $this->quantity_available),
            'settlement_cost_cents' => $this->settlement_cost_cents,
            'received_date' => $this->received_date?->toDateString(),
            'supplier' => $this->whenLoaded('supplier', fn () => ['id' => $this->supplier?->id, 'code' => $this->supplier?->code, 'name' => $this->supplier?->name]),
            'product' => $this->whenLoaded('inventoryObject', fn () => ['id' => $this->inventoryObject?->id, 'code' => $this->inventoryObject?->code, 'name' => $this->inventoryObject?->name]),
            'warehouse' => $this->whenLoaded('warehouse', fn () => ['id' => $this->warehouse?->id, 'code' => $this->warehouse?->code, 'name' => $this->warehouse?->name]),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
