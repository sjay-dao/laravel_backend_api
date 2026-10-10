<?php

namespace App\Domains\Evidence\Controllers;

use App\Domains\Evidence\Requests\StoreMarketObservationRequest;
use App\Domains\Evidence\Resources\MarketObservationResource;
use App\Domains\Evidence\Services\EvidenceService;
use App\Domains\Shared\Controllers\BaseApiController;
use Illuminate\Http\Request;

class MarketObservationController extends BaseApiController
{
    public function __construct(protected EvidenceService $service)
    {
    }

    public function index(Request $request)
    {
        $this->authorizeAbility($request, 'evidence.market_observations.view');

        return $this->paginated(
            $this->service->marketObservations(
                $request->filled('inventory_object_id')
                    ? $request->integer('inventory_object_id')
                    : null,
                $request->string('channel')->toString() ?: null,
                min(max($request->integer('per_page', 15), 1), 100)
            ),
            MarketObservationResource::class,
            'Market observations retrieved successfully.'
        );
    }

    public function store(StoreMarketObservationRequest $request)
    {
        $observation = $this->service->recordMarketObservation(
            $request->validated(),
            $request->user()->id
        );

        return $this->created(
            new MarketObservationResource($observation->load([
                'evidence.collector',
                'inventoryObjectUnit.inventoryObject',
                'inventoryObjectUnit.unit',
            ])),
            'Market observation recorded successfully.'
        );
    }
}
