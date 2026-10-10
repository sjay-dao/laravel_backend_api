<?php

namespace App\Domains\Shared\Analytics;

/** Read-only analytical contract, never an operational command or public resource. */
final readonly class BusinessEventProjection
{
    public function __construct(
        public string $event_type,
        public string $source_record,
        public ?int $inventory_object_id,
        public array $counterparty,
        public ?string $quantity,
        public array $unit,
        public ?string $unit_amount,
        public ?string $total_amount,
        public string $amount_basis,
        public ?string $currency,
        public ?string $occurred_at,
        public string $source,
        public string $epistemic_status,
        public ?string $scenario,
        public array $provenance,
    ) {}
}
