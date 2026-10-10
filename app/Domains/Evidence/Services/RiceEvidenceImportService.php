<?php

namespace App\Domains\Evidence\Services;

use App\Domains\Evidence\Models\EvidenceRecord;
use App\Domains\Evidence\Models\MarketObservation;
use App\Domains\Inventory\Models\InventoryObject;
use App\Domains\Inventory\Models\InventoryObjectUnit;
use App\Domains\Reference\Models\Supplier;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Imports the deliberately small public Rice evidence pack.
 *
 * This is intentionally separate from operational procurement and sales
 * services. It only writes to the Evidence domain, and only creates a
 * MarketObservation when an exact existing product and compatible UOM can be
 * identified. Ambiguous products remain unlinked EvidenceRecord rows for
 * human reconciliation.
 */
class RiceEvidenceImportService
{
    private const DATASET_NAMESPACE = 'rice-evidence-pack-v1';

    private const LEAD_UNPERSISTED_REASON = 'The Supplier master has no lead/prospect lifecycle or source-provenance fields, and supplier commercial observations require a known numeric purchase price. No supplier or supplier commercial observation was created.';

    /**
     * @param array<int, array<string, mixed>> $marketRows
     * @param array<int, array<string, mixed>> $supplierLeadRows
     * @return array<string, mixed>
     */
    public function import(
        array $marketRows,
        array $supplierLeadRows,
        bool $dryRun = false
    ): array {
        [$preparedMarkets, $marketDuplicates, $invalidMarkets] = $this->prepareMarketRows($marketRows);
        [$preparedLeads, $leadDuplicates] = $this->prepareSupplierLeads($supplierLeadRows);

        $result = [
            'dry_run' => $dryRun,
            'market' => [
                'input_rows' => count($marketRows),
                'unique_records' => count($preparedMarkets),
                'duplicate_rows' => $marketDuplicates,
                'invalid_rows' => $invalidMarkets,
                'imported' => [],
                'existing' => [],
                'would_import' => [],
                'unresolved' => [],
            ],
            'supplier_leads' => [
                'input_rows' => count($supplierLeadRows),
                'unique_records' => count($preparedLeads),
                'duplicate_rows' => $leadDuplicates,
                'unpersisted' => [],
            ],
        ];

        foreach ($preparedMarkets as $prepared) {
            $outcome = $this->importMarketRecord($prepared, $dryRun);
            $result['market'][$outcome['state']][] = $outcome;

            if ($outcome['reconciliation_status'] !== 'resolved') {
                $result['market']['unresolved'][] = $outcome;
            }
        }

        foreach ($preparedLeads as $prepared) {
            $row = $prepared['row'];
            $result['supplier_leads']['unpersisted'][] = [
                'supplier_name' => $this->stringValue($row, 'supplier_name'),
                'source_url' => $this->nullableStringValue($row, 'source_url'),
                'commercial_price_raw' => $row['price'] ?? null,
                'existing_supplier_candidate' => $this->existingSupplierCandidate(
                    $this->stringValue($row, 'supplier_name')
                ),
                'input_sources' => $prepared['input_sources'],
                'reason' => self::LEAD_UNPERSISTED_REASON,
                'raw' => $row,
            ];
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $row
     */
    public function marketImportKey(array $row): string
    {
        $this->validateMarketRow($row);

        return hash('sha256', implode('|', [
            self::DATASET_NAMESPACE,
            $this->stableValue($this->stringValue($row, 'source_url')),
            $this->stableValue($this->stringValue($row, 'source_name')),
            $this->stableValue($this->stringValue($row, 'source_type')),
            $this->stableValue($this->stringValue($row, 'product_description_raw')),
            $this->stableNumber($row['package_qty']),
            $this->stableValue($this->stringValue($row, 'package_uom')),
            $this->stableNumber($row['listed_price_php']),
            $this->stableValue($this->stringValue($row, 'observed_period')),
        ]));
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>, 2: array<int, array<string, mixed>>}
     */
    private function prepareMarketRows(array $rows): array
    {
        $recordsByKey = [];
        $duplicates = [];
        $invalid = [];

        foreach ($rows as $index => $rawRow) {
            if (! is_array($rawRow)) {
                $invalid[] = [
                    'row_number' => $index + 1,
                    'reason' => 'Market input row is not an object.',
                ];
                continue;
            }

            try {
                $key = $this->marketImportKey($rawRow);
            } catch (InvalidArgumentException $exception) {
                $invalid[] = [
                    'row_number' => $index + 1,
                    'product_description_raw' => $rawRow['product_description_raw'] ?? null,
                    'reason' => $exception->getMessage(),
                    'raw' => $this->sourceRow($rawRow),
                ];
                continue;
            }

            $source = $this->inputSource($rawRow);
            if (isset($recordsByKey[$key])) {
                $recordsByKey[$key]['input_sources'] = array_values(array_unique([
                    ...$recordsByKey[$key]['input_sources'],
                    $source,
                ]));
                $duplicates[] = [
                    'import_key' => $key,
                    'product_description_raw' => $this->stringValue($rawRow, 'product_description_raw'),
                    'input_source' => $source,
                ];
                continue;
            }

            $recordsByKey[$key] = [
                'import_key' => $key,
                'row' => $this->sourceRow($rawRow),
                'input_sources' => [$source],
            ];
        }

        return [array_values($recordsByKey), $duplicates, $invalid];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>}
     */
    private function prepareSupplierLeads(array $rows): array
    {
        $recordsByKey = [];
        $duplicates = [];

        foreach ($rows as $rawRow) {
            if (! is_array($rawRow)) {
                continue;
            }

            $name = $this->nullableStringValue($rawRow, 'supplier_name');
            $sourceUrl = $this->nullableStringValue($rawRow, 'source_url');
            if (! $name || ! $sourceUrl) {
                $duplicates[] = [
                    'supplier_name' => $name,
                    'input_source' => $this->inputSource($rawRow),
                    'reason' => 'Lead is missing a supplier name or source URL and was not considered importable.',
                ];
                continue;
            }

            $key = hash('sha256', implode('|', [
                self::DATASET_NAMESPACE,
                'supplier_lead',
                $this->stableValue($name),
                $this->stableValue($sourceUrl),
            ]));
            $source = $this->inputSource($rawRow);

            if (isset($recordsByKey[$key])) {
                $recordsByKey[$key]['input_sources'] = array_values(array_unique([
                    ...$recordsByKey[$key]['input_sources'],
                    $source,
                ]));
                $duplicates[] = [
                    'lead_key' => $key,
                    'supplier_name' => $name,
                    'input_source' => $source,
                ];
                continue;
            }

            $recordsByKey[$key] = [
                'lead_key' => $key,
                'row' => $this->sourceRow($rawRow),
                'input_sources' => [$source],
            ];
        }

        return [array_values($recordsByKey), $duplicates];
    }

    /**
     * @param array<string, mixed> $prepared
     * @return array<string, mixed>
     */
    private function importMarketRecord(array $prepared, bool $dryRun): array
    {
        $row = $prepared['row'];
        $resolution = $this->resolveProduct($row);
        $baseOutcome = $this->outcomeDetails($prepared, $resolution);

        $existing = EvidenceRecord::query()
            ->where('import_key', $prepared['import_key'])
            ->first();

        if ($existing) {
            return [
                ...$baseOutcome,
                'state' => 'existing',
                'evidence_record_id' => $existing->id,
            ];
        }

        if ($dryRun) {
            return [
                ...$baseOutcome,
                'state' => 'would_import',
                'evidence_record_id' => null,
            ];
        }

        return DB::transaction(function () use ($prepared, $row, $resolution, $baseOutcome) {
            $existing = EvidenceRecord::query()
                ->where('import_key', $prepared['import_key'])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return [
                    ...$baseOutcome,
                    'state' => 'existing',
                    'evidence_record_id' => $existing->id,
                ];
            }

            try {
                $evidence = EvidenceRecord::create([
                    'inventory_object_id' => $resolution['inventory_object_id'],
                    'source_type' => $this->stringValue($row, 'source_type'),
                    'epistemic_status' => $this->stringValue($row, 'epistemic_status'),
                    'collection_method' => $this->nullableStringValue($row, 'collection_method'),
                    'source_reference' => $this->nullableStringValue($row, 'source_url'),
                    'source_entity_type' => $this->sourceEntityType($row),
                    'source_entity_id' => null,
                    'observed_at' => $this->observedAt($this->stringValue($row, 'observed_period')),
                    'recorded_at' => now(),
                    'location_name' => $this->nullableStringValue($row, 'location'),
                    'raw_payload' => $row,
                    'context_payload' => $this->contextPayload($prepared, $resolution),
                    'notes' => $this->nullableStringValue($row, 'notes'),
                    'collected_by' => null,
                    'import_key' => $prepared['import_key'],
                ]);
            } catch (QueryException $exception) {
                $existing = EvidenceRecord::query()
                    ->where('import_key', $prepared['import_key'])
                    ->first();

                if ($existing) {
                    return [
                        ...$baseOutcome,
                        'state' => 'existing',
                        'evidence_record_id' => $existing->id,
                    ];
                }

                throw $exception;
            }

            if ($resolution['inventory_object_unit_id']) {
                MarketObservation::create([
                    'evidence_record_id' => $evidence->id,
                    'inventory_object_unit_id' => $resolution['inventory_object_unit_id'],
                    'store_name' => $this->stringValue($row, 'source_name'),
                    'channel' => $this->stringValue($row, 'channel'),
                    'selling_price' => $this->numericValue($row, 'listed_price_php'),
                    'currency_code' => 'PHP',
                    'package_size' => $this->packageSize($row),
                    'availability_status' => null,
                    'promotion' => null,
                ]);
            }

            return [
                ...$baseOutcome,
                'state' => 'imported',
                'evidence_record_id' => $evidence->id,
            ];
        });
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function resolveProduct(array $row): array
    {
        $canonicalCandidate = $this->nullableStringValue($row, 'canonical_candidate');
        $products = InventoryObject::query()
            ->with('units.unit')
            ->get();
        $riceFamilyCandidates = $products
            ->filter(fn (InventoryObject $product) => $this->looksLikeRiceProduct($product))
            ->map(fn (InventoryObject $product) => $this->productSummary($product))
            ->values()
            ->all();

        if (! $canonicalCandidate) {
            return [
                'status' => 'unresolved',
                'reason' => 'The source does not provide a canonical product candidate.',
                'inventory_object_id' => null,
                'inventory_object_unit_id' => null,
                'canonical_candidate' => null,
                'candidate_inventory_objects' => $riceFamilyCandidates,
            ];
        }

        $canonicalIdentity = $this->entityIdentity($canonicalCandidate);
        $exactMatches = $products
            ->filter(fn (InventoryObject $product) => $this->entityIdentity($product->name) === $canonicalIdentity)
            ->values();

        if ($exactMatches->count() !== 1) {
            return [
                'status' => 'unresolved',
                'reason' => $exactMatches->isEmpty()
                    ? 'No exact Inventory Object name matches the supplied canonical candidate.'
                    : 'More than one Inventory Object exactly matches the supplied canonical candidate.',
                'inventory_object_id' => null,
                'inventory_object_unit_id' => null,
                'canonical_candidate' => $canonicalCandidate,
                'candidate_inventory_objects' => $riceFamilyCandidates,
            ];
        }

        $product = $exactMatches->first();
        $objectUnit = $product->units
            ->first(fn (InventoryObjectUnit $unit) => $unit->unit
                && $this->entityIdentity($unit->unit->code) === $this->entityIdentity(
                    $this->stringValue($row, 'package_uom')
                ));

        if (! $objectUnit) {
            return [
                'status' => 'unresolved',
                'reason' => 'An exact product exists, but it has no compatible Inventory Object Unit for the supplied package UOM.',
                'inventory_object_id' => null,
                'inventory_object_unit_id' => null,
                'canonical_candidate' => $canonicalCandidate,
                'candidate_inventory_objects' => [$this->productSummary($product)],
            ];
        }

        return [
            'status' => 'resolved',
            'reason' => 'Exact canonical product and package UOM match.',
            'inventory_object_id' => $product->id,
            'inventory_object_unit_id' => $objectUnit->id,
            'canonical_candidate' => $canonicalCandidate,
            'candidate_inventory_objects' => [$this->productSummary($product)],
        ];
    }

    /**
     * @param array<string, mixed> $prepared
     * @param array<string, mixed> $resolution
     * @return array<string, mixed>
     */
    private function outcomeDetails(array $prepared, array $resolution): array
    {
        $row = $prepared['row'];

        return [
            'import_key' => $prepared['import_key'],
            'source_name' => $this->stringValue($row, 'source_name'),
            'source_url' => $this->stringValue($row, 'source_url'),
            'product_description_raw' => $this->stringValue($row, 'product_description_raw'),
            'canonical_candidate' => $resolution['canonical_candidate'],
            'input_sources' => $prepared['input_sources'],
            'reconciliation_status' => $resolution['status'],
            'reconciliation_reason' => $resolution['reason'],
            'candidate_inventory_objects' => $resolution['candidate_inventory_objects'],
            'inventory_object_id' => $resolution['inventory_object_id'],
            'inventory_object_unit_id' => $resolution['inventory_object_unit_id'],
        ];
    }

    /**
     * @param array<string, mixed> $prepared
     * @param array<string, mixed> $resolution
     * @return array<string, mixed>
     */
    private function contextPayload(array $prepared, array $resolution): array
    {
        $row = $prepared['row'];

        return [
            'evidence_kind' => 'market_observation',
            'import' => [
                'dataset' => self::DATASET_NAMESPACE,
                'import_key' => $prepared['import_key'],
                'input_sources' => $prepared['input_sources'],
            ],
            'product_reconciliation' => [
                'status' => $resolution['status'],
                'reason' => $resolution['reason'],
                'canonical_candidate' => $resolution['canonical_candidate'],
                'candidate_inventory_objects' => $resolution['candidate_inventory_objects'],
            ],
            'market_context' => [
                'channel' => $this->stringValue($row, 'channel'),
                'package_qty' => $row['package_qty'],
                'package_uom' => $this->stringValue($row, 'package_uom'),
                'listed_price_php' => $row['listed_price_php'],
                'normalized_price_php_per_kg' => $row['normalized_price_php_per_kg'] ?? null,
                'observed_period_raw' => $this->stringValue($row, 'observed_period'),
                'retrieved_date_raw' => $row['retrieved_date'] ?? null,
                'source_url' => $this->stringValue($row, 'source_url'),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function validateMarketRow(array $row): void
    {
        foreach ([
            'source_name',
            'source_type',
            'epistemic_status',
            'collection_method',
            'product_description_raw',
            'package_uom',
            'observed_period',
            'source_url',
            'channel',
        ] as $field) {
            $this->stringValue($row, $field);
        }

        foreach (['package_qty', 'listed_price_php'] as $field) {
            $this->numericValue($row, $field);
        }

        $status = $this->stableValue($this->stringValue($row, 'epistemic_status'));
        if (! in_array($status, EvidenceService::EPISTEMIC_STATUSES, true)) {
            throw new InvalidArgumentException("Unsupported epistemic_status [{$status}].");
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    private function observedAt(string $observedPeriod): ?CarbonImmutable
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $observedPeriod)) {
            return null;
        }

        return CarbonImmutable::createFromFormat('!Y-m-d', $observedPeriod);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function sourceEntityType(array $row): string
    {
        return $this->stringValue($row, 'source_type') === 'government_statistics'
            ? 'government_statistics'
            : 'market_listing';
    }

    /**
     * @param array<string, mixed> $row
     */
    private function packageSize(array $row): string
    {
        return trim($this->stableNumber($row['package_qty']) . ' ' . $this->stringValue($row, 'package_uom'));
    }

    /**
     * @param array<string, mixed> $row
     */
    private function numericValue(array $row, string $field): float
    {
        $value = $row[$field] ?? null;
        if (! is_int($value) && ! is_float($value) && ! (is_string($value) && is_numeric(trim($value)))) {
            throw new InvalidArgumentException("Field [{$field}] must be numeric and cannot be unknown.");
        }

        return (float) $value;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function stringValue(array $row, string $field): string
    {
        $value = $this->nullableStringValue($row, $field);
        if ($value === null) {
            throw new InvalidArgumentException("Field [{$field}] is required.");
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function nullableStringValue(array $row, string $field): ?string
    {
        $value = $row[$field] ?? null;
        if ($value === null) {
            return null;
        }

        if (! is_scalar($value)) {
            throw new InvalidArgumentException("Field [{$field}] must be a scalar value.");
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function sourceRow(array $row): array
    {
        unset($row['_input_source']);

        return $row;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function inputSource(array $row): string
    {
        return $this->nullableStringValue($row, '_input_source') ?? 'unspecified_input';
    }

    private function stableValue(string $value): string
    {
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);

        return function_exists('mb_strtolower')
            ? mb_strtolower($value, 'UTF-8')
            : strtolower($value);
    }

    private function stableNumber(mixed $value): string
    {
        if (! is_int($value) && ! is_float($value) && ! (is_string($value) && is_numeric(trim($value)))) {
            throw new InvalidArgumentException('Stable import key requires a numeric value.');
        }

        return number_format((float) $value, 6, '.', '');
    }

    private function entityIdentity(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $normalized = preg_replace('/[^\pL\pN]+/u', ' ', $value) ?? $value;

        return $this->stableValue($normalized);
    }

    private function looksLikeRiceProduct(InventoryObject $product): bool
    {
        return str_contains($this->entityIdentity(implode(' ', [
            $product->code,
            $product->name,
            $product->brand,
            $product->variant,
            $product->specification,
        ])), 'rice');
    }

    /**
     * @return array<string, mixed>
     */
    private function productSummary(InventoryObject $product): array
    {
        return [
            'id' => $product->id,
            'code' => $product->code,
            'name' => $product->name,
            'brand' => $product->brand,
            'variant' => $product->variant,
            'specification' => $product->specification,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function existingSupplierCandidate(string $supplierName): ?array
    {
        $identity = $this->entityIdentity($supplierName);
        $supplier = Supplier::withTrashed()
            ->get()
            ->first(fn (Supplier $candidate) => $this->entityIdentity($candidate->name) === $identity);

        return $supplier ? [
            'id' => $supplier->id,
            'code' => $supplier->code,
            'name' => $supplier->name,
        ] : null;
    }
}
