<?php
namespace App\Domains\Reference\Controllers;
use App\Domains\Reference\Models\Supplier;
use App\Domains\Reference\Requests\StoreSupplierRequest;
use App\Domains\Reference\Requests\UpdateSupplierRequest;
use App\Domains\Reference\Resources\SupplierResource;
use App\Domains\Reference\Services\SupplierService;
use App\Domains\Shared\Controllers\BaseApiController;
use Illuminate\Http\Request;
class SupplierController extends BaseApiController
{
    public function __construct(
        protected SupplierService $service
    ) {
    }
    public function index(Request $request)
    {
        $suppliers = $this->service->paginate(
            $request->integer('per_page', 15),
            $request->search
        );
        return $this->paginated(
            $suppliers,
            SupplierResource::class,
            'Suppliers retrieved successfully.'
        );
    }
    public function show(Supplier $supplier)
    {
        return $this->resource(
            new SupplierResource(
                $supplier->load(
                    'barangay.cityMun.province.region'
                )
            )
        );
    }
    public function store(StoreSupplierRequest $request)
    {
        $supplier = $this->service->create(
            $request->validated()
        );
        return $this->created(
            new SupplierResource($supplier)
        );
    }
    public function update(
        UpdateSupplierRequest $request,
        Supplier $supplier
    ) {
        $supplier = $this->service->update(
            $supplier,
            $request->validated()
        );
        return $this->resource(
            new SupplierResource($supplier)
        );
    }
    public function destroy(Request $request, Supplier $supplier)
    {
        $this->authorizeAbility(
            $request,
            'reference.suppliers.delete'
        );
        $this->service->delete($supplier);
        return $this->deleted();
    }
    public function options()
    {
        return $this->success(
            $this->service->options()
        );
    }
}