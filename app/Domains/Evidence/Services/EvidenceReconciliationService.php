<?php

namespace App\Domains\Evidence\Services;

use App\Domains\Evidence\Models\EvidenceReconciliation;
use App\Domains\Evidence\Models\EvidenceRecord;
use App\Domains\Inventory\Models\InventoryObject;
use App\Domains\Inventory\Models\InventoryObjectUnit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Human identity-review workflow for evidence that deliberately has no direct
 * product link. Reconciliation history is append-only: new decisions
 * supersede older decisions without modifying the underlying raw evidence.
 */
class EvidenceReconciliationService
{
    public const SCOPES = ['unresolved', 'rejected', 'all'];

    // This review workflow handles external product evidence, not respondent data.
    public const SOURCE_TYPES = ['market', 'supplier', 'government', 'competitor'];

    private function reviewable(): Builder
    {
        return EvidenceRecord::query()->whereNull('inventory_object_id')
            ->whereIn('source_type', self::SOURCE_TYPES)
            ->where(function ($query) {
                $query->whereNull('source_entity_type')
                    ->orWhereNotIn('source_entity_type', ['survey', 'survey_response', 'survey_respondent']);
            })
            ->doesntHave('simulatedPurchaseEvent');
    }

    public function assertReviewable(EvidenceRecord $record): void
    {
        abort_unless($this->reviewable()->whereKey($record->id)->exists(), 404);
    }

    /** Candidate hints establish queue relevance only, never a product relationship. */
    public function unresolvedCandidateCount(int $productId): int
    {
        return $this->reviewable()->where(fn ($query) => $query->doesntHave('currentReconciliation')
            ->orWhereHas('currentReconciliation', fn ($review) => $review->where('relationship_type', 'unresolved')))
            ->select(['id', 'context_payload'])->cursor()->filter(fn ($record) => collect(
                data_get($record->context_payload, 'product_reconciliation.candidate_inventory_objects', [])
            )->contains(fn ($candidate) => (int) data_get($candidate, 'id') === $productId))->count();
    }

    public function unlinkedEvidence(
        int $perPage = 15,
        string $scope = 'unresolved'
    ): LengthAwarePaginator {
        if (! in_array($scope, self::SCOPES, true)) {
            throw ValidationException::withMessages([
                'scope' => 'The reconciliation scope is invalid.',
            ]);
        }

        return $this->reviewable()
            ->when($scope === 'unresolved', function ($query) {
                $query->where(function ($unresolved) {
                    $unresolved->doesntHave('currentReconciliation')
                        ->orWhereHas('currentReconciliation', function ($reconciliation) {
                            $reconciliation->where(
                                'relationship_type',
                                EvidenceReconciliation::RELATIONSHIP_UNRESOLVED
                            );
                        });
                });
            })
            ->when($scope === 'rejected', function ($query) {
                $query->whereHas('currentReconciliation', function ($reconciliation) {
                    $reconciliation->where(
                        'relationship_type',
                        EvidenceReconciliation::RELATIONSHIP_REJECTED
                    );
                });
            })
            ->with($this->evidenceRelations())
            ->latest('observed_at')
            ->latest('id')
            ->paginate($perPage);
    }

    public function detail(EvidenceRecord $evidenceRecord): EvidenceRecord
    {
        $this->assertReviewable($evidenceRecord);

        return $evidenceRecord->load($this->evidenceRelations(includeHistory: true));
    }

    /**
     * @return array{0: LengthAwarePaginator, 1: array<int, int>}
     */
    public function candidates(EvidenceRecord $evidenceRecord, string $search = '', int $perPage = 20): array
    {
        $this->assertReviewable($evidenceRecord);
        $suggestedIds = collect(data_get(
            $evidenceRecord->context_payload,
            'product_reconciliation.candidate_inventory_objects',
            []
        ))
            ->pluck('id')
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $objects = InventoryObject::query()
            ->where('is_active', true)
            ->with(['baseUnit', 'units.unit'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($matches) use ($search) {
                    foreach (['code', 'name', 'brand', 'variant', 'packaging_description', 'specification'] as $field) {
                        $matches->orWhere($field, 'like', '%'.$search.'%');
                    }
                });
            })
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($perPage);

        return [$objects, $suggestedIds];
    }

    public function reconcile(
        EvidenceRecord $evidenceRecord,
        array $data,
        int $userId
    ): EvidenceReconciliation {
        return DB::transaction(function () use ($evidenceRecord, $data, $userId) {
            $evidence = EvidenceRecord::query()
                ->lockForUpdate()
                ->findOrFail($evidenceRecord->id);

            $this->assertReviewable($evidence);

            if ($evidence->inventory_object_id !== null) {
                throw ValidationException::withMessages([
                    'evidence_record' => 'Only unlinked evidence may be reconciled through this review workflow.',
                ]);
            }

            $relationshipType = $data['relationship_type'];
            $this->validateRelationship($relationshipType, $data);

            $this->currentReconciliations($evidence)
                ->update([
                    'reconciliation_status' => EvidenceReconciliation::STATUS_SUPERSEDED,
                    'superseded_at' => now(),
                    'updated_at' => now(),
                ]);

            $reconciliation = EvidenceReconciliation::create([
                'evidence_record_id' => $evidence->id,
                'inventory_object_id' => $data['inventory_object_id'] ?? null,
                'inventory_object_unit_id' => $data['inventory_object_unit_id'] ?? null,
                'relationship_type' => $relationshipType,
                'reconciliation_status' => EvidenceReconciliation::STATUS_ACTIVE,
                'notes' => $data['notes'],
                'rationale' => $data['rationale'] ?? null,
                'confidence' => $data['confidence'] ?? null,
                'reconciled_by' => $userId,
                'reconciled_at' => now(),
            ]);

            return $reconciliation->load([
                'inventoryObject.baseUnit',
                'inventoryObjectUnit.inventoryObject',
                'inventoryObjectUnit.unit',
                'reconciler',
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateRelationship(string $relationshipType, array $data): void
    {
        if (! in_array($relationshipType, EvidenceReconciliation::RELATIONSHIP_TYPES, true)) {
            throw ValidationException::withMessages([
                'relationship_type' => 'The relationship type is invalid.',
            ]);
        }

        $requiresProduct = in_array(
            $relationshipType,
            EvidenceReconciliation::PRODUCT_RELATIONSHIP_TYPES,
            true
        );
        $productId = $data['inventory_object_id'] ?? null;
        $objectUnitId = $data['inventory_object_unit_id'] ?? null;

        if ($requiresProduct && ! $productId) {
            throw ValidationException::withMessages([
                'inventory_object_id' => 'A product is required for exact, variant, and category-comparable evidence.',
            ]);
        }

        if (! $requiresProduct && ($productId || $objectUnitId)) {
            throw ValidationException::withMessages([
                'inventory_object_id' => 'Unresolved and rejected evidence must not be linked to a product or product unit.',
            ]);
        }

        if (! $requiresProduct) {
            return;
        }

        InventoryObject::query()->findOrFail($productId);

        if (! $objectUnitId) {
            return;
        }

        $objectUnit = InventoryObjectUnit::query()->findOrFail($objectUnitId);
        if ((int) $objectUnit->inventory_object_id !== (int) $productId) {
            throw ValidationException::withMessages([
                'inventory_object_unit_id' => 'The selected product unit does not belong to the selected product.',
            ]);
        }
    }

    private function currentReconciliations(EvidenceRecord $evidence): mixed
    {
        return EvidenceReconciliation::query()
            ->where('evidence_record_id', $evidence->id)
            ->where('reconciliation_status', EvidenceReconciliation::STATUS_ACTIVE)
            ->lockForUpdate();
    }

    /** Active reviewed links only; never rewrite the direct source product ID. */
    public function forProduct(int $productId, array $relationships): Collection
    {
        return $this->reviewable()
            ->whereHas('currentReconciliation', fn ($query) => $query
                ->where('inventory_object_id', $productId)
                ->whereIn('relationship_type', $relationships))
            ->with($this->evidenceRelations())
            ->latest('observed_at')->latest('id')->get();
    }

    /**
     * @return array<int, string>
     */
    private function evidenceRelations(bool $includeHistory = false): array
    {
        $relations = [
            'collector',
            'supplierObservation.supplier',
            'supplierObservation.inventoryObjectUnit.inventoryObject',
            'supplierObservation.inventoryObjectUnit.unit',
            'marketObservation.inventoryObjectUnit.inventoryObject',
            'marketObservation.inventoryObjectUnit.unit',
            'currentReconciliation.inventoryObject.baseUnit',
            'currentReconciliation.inventoryObjectUnit.inventoryObject',
            'currentReconciliation.inventoryObjectUnit.unit',
            'currentReconciliation.reconciler',
        ];

        if ($includeHistory) {
            $relations[] = 'reconciliations.inventoryObject.baseUnit';
            $relations[] = 'reconciliations.inventoryObjectUnit.inventoryObject';
            $relations[] = 'reconciliations.inventoryObjectUnit.unit';
            $relations[] = 'reconciliations.reconciler';
        }

        return $relations;
    }
}
