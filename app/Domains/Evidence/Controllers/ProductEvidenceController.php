<?php

namespace App\Domains\Evidence\Controllers;

use App\Domains\Evidence\Resources\EvidenceRecordResource;
use App\Domains\Evidence\Resources\MarketObservationResource;
use App\Domains\Evidence\Resources\ReconciliationEvidenceResource;
use App\Domains\Evidence\Resources\SimulatedPurchaseEventResource;
use App\Domains\Evidence\Resources\SupplierObservationResource;
use App\Domains\Evidence\Resources\SurveyResponseResource;
use App\Domains\Evidence\Services\EvidenceReconciliationService;
use App\Domains\Evidence\Services\EvidenceService;
use App\Domains\Evidence\Services\ProductEvidenceSummaryService;
use App\Domains\Inventory\Models\InventoryObject;
use App\Domains\Inventory\Resources\InventoryObjectResource;
use App\Domains\Shared\Controllers\BaseApiController;
use Illuminate\Http\Request;

class ProductEvidenceController extends BaseApiController
{
    public function __construct(protected EvidenceService $service, protected EvidenceReconciliationService $reconciliation, protected ProductEvidenceSummaryService $summary) {}

    public function index(Request $request)
    {
        $this->authorizeAbility($request, 'evidence.products.view');
        $data = $request->validate(['search' => ['nullable', 'string', 'max:200'], 'per_page' => ['sometimes', 'integer', 'min:1', 'max:100']]);
        $products = InventoryObject::with(['baseUnit', 'units.unit'])
            ->when(filled($data['search'] ?? null), fn ($query) => $query->where(function ($match) use ($data) {
                foreach (['code', 'name', 'brand', 'variant', 'packaging_description', 'specification'] as $field) {
                    $match->orWhere($field, 'like', '%'.$data['search'].'%');
                }
            }))->orderBy('name')->orderBy('id')->paginate($data['per_page'] ?? 20);

        return $this->paginated($products, InventoryObjectResource::class, 'Evidence products retrieved successfully.');
    }

    public function show(Request $request, InventoryObject $inventoryObject)
    {
        $this->authorizeAbility($request, 'evidence.products.view');

        $records = $this->service->forInventoryObject($inventoryObject->id);
        $supplierObservations = $records
            ->filter(fn ($record) => $record->supplierObservation !== null)
            ->map(fn ($record) => $record->supplierObservation)
            ->values();
        $marketObservations = $records
            ->filter(fn ($record) => $record->marketObservation !== null)
            ->map(fn ($record) => $record->marketObservation)
            ->values();
        $simulatedEvents = $records
            ->filter(fn ($record) => $record->simulatedPurchaseEvent !== null)
            ->map(fn ($record) => $record->simulatedPurchaseEvent)
            ->values();
        $consumerResponses = $simulatedEvents
            ->map(fn ($event) => $event->response)
            ->filter()
            ->unique('id')
            ->values();

        $exact = $records->concat($this->reconciliation->forProduct($inventoryObject->id, ['exact']))->unique('id')->values();
        $comparable = $this->reconciliation->forProduct($inventoryObject->id, ['variant_match', 'category_comparable']);
        $inventoryObject->load(['category', 'baseUnit', 'units.unit']);

        return $this->success([
            'product' => new InventoryObjectResource($inventoryObject->load([
                'category',
                'baseUnit',
                'units.unit',
            ])),
            'supplier_evidence' => SupplierObservationResource::collection($supplierObservations),
            'market_evidence' => MarketObservationResource::collection($marketObservations),
            'consumer_evidence' => SurveyResponseResource::collection($consumerResponses),
            'simulated_purchase_events' => SimulatedPurchaseEventResource::collection($simulatedEvents),
            'evidence_records' => EvidenceRecordResource::collection($records),
            'exact_evidence' => ReconciliationEvidenceResource::collection($exact),
            'comparable_evidence' => ReconciliationEvidenceResource::collection($comparable),
            'summary' => $this->summary->build($inventoryObject, $exact, $comparable),
        ], 'Product evidence retrieved successfully.');
    }
}
