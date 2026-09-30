<?php

namespace App\Domains\Inventory\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryProductSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'code' => $this->code, 'name' => $this->name,
            'category' => $this->category ? ['id' => $this->category->id, 'code' => $this->category->code, 'name' => $this->category->name] : null,
            'retail_price_cents' => $this->retail_price_cents,
            'has_wholesale_pricing' => $this->wholesalePriceTiers->isNotEmpty(),
            'wholesale_price_tiers' => $this->wholesalePriceTiers->map(fn ($tier) => ['id' => $tier->id, 'min_quantity' => $tier->min_quantity, 'max_quantity' => $tier->max_quantity, 'unit_price_cents' => $tier->unit_price_cents]),
            'stock' => [
                'total_available' => (string) ($this->total_available_quantity ?? 0),
                'owned_available' => (string) ($this->owned_available_quantity ?? 0),
                'consignment_available' => (string) ($this->consignment_available_quantity ?? 0),
            ],
        ];
    }
}
