<?php

namespace App\Domains\Evidence\Controllers;

use App\Domains\Evidence\Models\EvidenceRecord;
use App\Domains\Evidence\Requests\StoreEvidenceReconciliationRequest;
use App\Domains\Evidence\Resources\EvidenceReconciliationResource;
use App\Domains\Evidence\Resources\ReconciliationEvidenceResource;
use App\Domains\Evidence\Services\EvidenceReconciliationService;
use App\Domains\Inventory\Resources\InventoryObjectResource;
use App\Domains\Shared\Controllers\BaseApiController;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EvidenceReconciliationController extends BaseApiController
{
    public function __construct(protected EvidenceReconciliationService $service) {}

    public function index(Request $request)
    {
        $this->authorizeAbility($request, 'evidence.reconciliations.view');
        $data = $request->validate([
            'scope' => ['sometimes', Rule::in(EvidenceReconciliationService::SCOPES)],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        return $this->paginated(
            $this->service->unlinkedEvidence($data['per_page'] ?? 20, $data['scope'] ?? 'unresolved'),
            ReconciliationEvidenceResource::class,
        );
    }

    public function show(Request $request, EvidenceRecord $evidenceRecord)
    {
        $this->authorizeAbility($request, 'evidence.reconciliations.view');
        $record = $this->service->detail($evidenceRecord);

        return $this->success([
            'evidence_record' => new ReconciliationEvidenceResource($record),
            'current_reconciliation' => $record->currentReconciliation
                ? new EvidenceReconciliationResource($record->currentReconciliation) : null,
            'reconciliation_history' => EvidenceReconciliationResource::collection($record->reconciliations),
        ]);
    }

    public function candidates(Request $request, EvidenceRecord $evidenceRecord)
    {
        $this->authorizeAbility($request, 'evidence.reconciliations.view');
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);
        [$products, $suggestedIds] = $this->service->candidates(
            $evidenceRecord, trim($data['search'] ?? ''), $data['per_page'] ?? 20,
        );

        return $this->success([
            'candidates' => InventoryObjectResource::collection($products->getCollection()),
            'suggested_ids' => $suggestedIds,
            'meta' => ['current_page' => $products->currentPage(), 'per_page' => $products->perPage(), 'total' => $products->total()],
        ]);
    }

    public function store(StoreEvidenceReconciliationRequest $request, EvidenceRecord $evidenceRecord)
    {
        return $this->created(new EvidenceReconciliationResource($this->service->reconcile(
            $evidenceRecord, $request->validated(), $request->user()->id,
        )), 'Review saved. The original evidence is unchanged.');
    }
}
