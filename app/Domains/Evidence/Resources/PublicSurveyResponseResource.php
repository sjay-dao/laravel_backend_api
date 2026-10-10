<?php

namespace App\Domains\Evidence\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicSurveyResponseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'simulated_purchase_events' => $this->whenLoaded(
                'simulatedPurchaseEvents',
                function () {
                    return $this->simulatedPurchaseEvents->map(function ($event) {
                        return [
                            'id' => $event->id,
                            'source_type' => $event->source_type,
                            'epistemic_status' => $event->epistemic_status,
                            'would_purchase' => $event->would_purchase,
                            'proposed_price' => $event->proposed_price,
                            'currency_code' => $event->currency_code,
                            'simulated_quantity' => $event->simulated_quantity,
                            'simulated_at' => $event->simulated_at?->toIso8601String(),
                        ];
                    })->values();
                }
            ),
        ];
    }
}
