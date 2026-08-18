<?php

namespace App\Domains\Purchasing\Services\Concerns;

use App\Domains\Inventory\Models\InventoryObjectUnit;

trait BuildsPurchasingDocuments
{
    protected function snapshot(InventoryObjectUnit $objectUnit): array
    {
        $objectUnit->loadMissing('inventoryObject', 'unit');

        return [
            'inventory_object_code' => $objectUnit->inventoryObject->code,
            'inventory_object_name' => $objectUnit->inventoryObject->name,
            'unit_code' => $objectUnit->unit->code,
            'unit_name' => $objectUnit->unit->name,
            'conversion_factor' => (string) $objectUnit->conversion_factor,
        ];
    }

    protected function baseQuantity(float|string $quantity, float|string $conversionFactor): string
    {
        return number_format(round((float) $quantity * (float) $conversionFactor, 6), 6, '.', '');
    }

    protected function lineTotal(
        float|string $quantity,
        float|string $unitPrice,
        float|string $discount = 0,
        float|string $tax = 0,
    ): string {
        return number_format(
            round(((float) $quantity * (float) $unitPrice) - (float) $discount + (float) $tax, 6),
            6,
            '.',
            ''
        );
    }

    protected function documentNumber(string $prefix, string $modelClass): string
    {
        $next = ((int) $modelClass::query()->max('id')) + 1;

        return sprintf('%s-%s-%06d', $prefix, now()->format('Ymd'), $next);
    }

    protected function requireStatus(object $document, array $statuses, string $action): void
    {
        if (! in_array($document->status, $statuses, true)) {
            abort(422, sprintf('%s is not allowed while document status is %s.', $action, $document->status));
        }
    }
}
