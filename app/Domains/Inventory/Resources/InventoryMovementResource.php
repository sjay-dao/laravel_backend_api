<?php

namespace App\Domains\Inventory\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Domains\Inventory\Resources\InventoryMovementItemResource;
use App\Domains\Inventory\Resources\InventoryMovementTypeResource;

class InventoryMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'transaction_no' => $this->transaction_no,

            'movement_date' => $this->movement_date,

            'remarks' => $this->remarks,

            'reference_type' => $this->reference_type,

            'reference_id' => $this->reference_id,

            'movement_type' => new InventoryMovementTypeResource(
                $this->whenLoaded('movementType')
            ),

            'warehouse' => $this->whenLoaded('warehouse', function () {
                return [
                    'id' => $this->warehouse->id,
                    'name' => $this->warehouse->name,
                ];
            }),

            'branch' => $this->whenLoaded('branch', function () {
                return [
                    'id' => $this->branch->id,
                    'name' => $this->branch->name,
                ];
            }),

            'items' => InventoryMovementItemResource::collection(
                $this->whenLoaded('items')
            ),

            'created_at' => $this->created_at,

        ];
    }
}