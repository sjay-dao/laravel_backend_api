<?php
namespace App\Domains\Sales\Controllers;
use App\Domains\Sales\Models\SalesOrder;
use App\Domains\Sales\Repositories\SalesOrderRepository;
use App\Domains\Sales\Requests\StoreSalesOrderRequest;
use App\Domains\Sales\Requests\UpdateSalesOrderRequest;
use App\Domains\Sales\Resources\SalesOrderResource;
use App\Domains\Sales\Services\SalesOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class SalesOrderController
{
    public function __construct(
        protected SalesOrderService $service
    ) {
    }
    
    public function index(Request $request)
    {
        $filters = $request->only([
            'search',
            'customer_id',
            'branch_id',
            'status_id',
            'order_date_from',
            'order_date_to',
        ]);
        $perPage = min((int) $request->input('per_page', 15), 100);
        $orders = app(SalesOrderRepository::class)
            ->paginate($filters, $perPage);
        return SalesOrderResource::collection($orders);
    }

    public function show(int $id): SalesOrderResource
    {
        return new SalesOrderResource(
            app(SalesOrderRepository::class)->find($id)
        );
    }
    public function store(StoreSalesOrderRequest $request): SalesOrderResource
    {
        return new SalesOrderResource(
            $this->service->create($request->validated())
        );
    }
    public function update(
        UpdateSalesOrderRequest $request,
        SalesOrder $salesOrder
    ): SalesOrderResource {
        return new SalesOrderResource(
            $this->service->update(
                $salesOrder,
                $request->validated()
            )
        );
    }
    public function destroy(SalesOrder $salesOrder): JsonResponse
    {
        $this->service->cancel($salesOrder);
        return response()->json([
            'message' => 'Sales order cancelled successfully.',
        ]);
    }
    public function confirm(SalesOrder $salesOrder): SalesOrderResource
    {
        return new SalesOrderResource(
            $this->service->confirm($salesOrder)
        );
    }
}