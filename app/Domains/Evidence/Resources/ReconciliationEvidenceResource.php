<?php

namespace App\Domains\Evidence\Resources;

use Illuminate\Http\Request;

class ReconciliationEvidenceResource extends EvidenceRecordResource
{
    public function toArray(Request $request): array
    {
        $raw = $this->raw_payload ?? [];
        $context = $this->context_payload ?? [];
        $legacyNormalized = data_get($context, 'market_context.normalized_price_php_per_kg', data_get($raw, 'normalized_price_php_per_kg'));
        $normalized = data_get($context, 'normalized', []);
        $hasGenericAmount = is_array($normalized) && array_key_exists('unit_amount', $normalized);

        return [
            ...parent::toArray($request),
            // Display supplied context only. No currency/UOM conversion or inference.
            'source_context' => [
                'source_name' => data_get($raw, 'source_name'),
                'product_description_raw' => data_get($raw, 'product_description_raw'),
                'listed_price' => data_get($raw, 'listed_price_php', data_get($raw, 'listed_price')),
                'currency' => isset($raw['listed_price_php']) ? 'PHP' : data_get($raw, 'currency_code'),
                'original_quantity' => data_get($raw, 'package_qty'),
                'original_uom' => data_get($raw, 'package_uom'),
                'normalized_price' => $hasGenericAmount ? $normalized['unit_amount'] : $legacyNormalized,
                'normalized_currency' => $hasGenericAmount ? ($normalized['currency_code'] ?? null) : ($legacyNormalized !== null ? 'PHP' : null),
                'normalized_uom' => $hasGenericAmount ? ($normalized['unit_code'] ?? null) : ($legacyNormalized !== null ? 'KG' : null),
                'observed_period' => data_get($raw, 'observed_period'),
                'retrieved_date' => data_get($raw, 'retrieved_date'),
                'source_url' => data_get($raw, 'source_url'),
            ],
            'current_reconciliation' => new EvidenceReconciliationResource($this->whenLoaded('currentReconciliation')),
        ];
    }
}
