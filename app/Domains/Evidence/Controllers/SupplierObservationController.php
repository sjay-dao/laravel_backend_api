<?php

namespace App\Domains\Evidence\Controllers;

use App\Domains\Evidence\Requests\StoreSupplierObservationRequest;
use App\Domains\Evidence\Resources\SupplierObservationResource;
use App\Domains\Evidence\Services\EvidenceService;
use App\Domains\Shared\Controllers\BaseApiController;
use Illuminate\Http\Request;

class SupplierObservationController extends BaseApiController
{
    public function __construct(protected EvidenceService $service)
    {
    }

    public function index(Request $request)
    {
        $this->authorizeAbility($request, 'evidence.supplier_observations.view');

        return $this->paginated(
            $this->service->supplierObservations(
                $request->filled('inventory_object_id')
                    ? $request->integer('inventory_object_id')
                    : null,
                $request->filled('supplier_id') ? $request->integer('supplier_id') : null,
                min(max($request->integer('per_page', 15), 1), 100)
            ),
            SupplierObservationResource::class,
            'Supplier observations retrieved successfully.'
        );
    }

    public function store(StoreSupplierObservationRequest $request)
    {
        $observation = $this->service->recordSupplierObservation(
            $request->validated(),
            $request->user()->id
        );

        return $this->created(
            new SupplierObservationResource($observation->load([
                'evidence.collector',
                'supplier',
                'inventoryObjectUnit.inventoryObject',
                'inventoryObjectUnit.unit',
            ])),
            'Supplier observation recorded successfully.'
        );
    }
}
