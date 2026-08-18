<?php
namespace App\Domains\Reference\Controllers;
use App\Domains\Reference\Models\Customer;
use App\Domains\Reference\Requests\StoreCustomerRequest;
use App\Domains\Reference\Requests\UpdateCustomerRequest;
use App\Domains\Reference\Resources\CustomerResource;
use App\Domains\Reference\Services\CustomerService;
use App\Domains\Shared\Controllers\BaseApiController;
use Illuminate\Http\Request;
class CustomerController extends BaseApiController
{
    public function __construct(
        protected CustomerService $service
    ) {
    }
    public function index(Request $request)
    {
        $this->authorizeAbility(
            $request,
            'reference.customers.view'
        );
        $customers = $this->service->paginate(
            $request->integer('per_page', 15),
            $request->search
        );
        return $this->paginated(
            $customers,
            CustomerResource::class,
            'Customers retrieved successfully.'
        );
    }
    public function show(
        Request $request,
        Customer $customer
    ) {
        $this->authorizeAbility(
            $request,
            'reference.customers.view'
        );
        return $this->resource(
            new CustomerResource(
                $customer->load(
                    'barangay.cityMun.province.region'
                )
            )
        );
    }
    public function store(
        StoreCustomerRequest $request
    ) {
        $customer = $this->service->create(
            $request->validated()
        );
        return $this->created(
            new CustomerResource($customer)
        );
    }
    public function update(
        UpdateCustomerRequest $request,
        Customer $customer
    ) {
        $customer = $this->service->update(
            $customer,
            $request->validated()
        );
        return $this->resource(
            new CustomerResource($customer)
        );
    }
    public function destroy(
        Request $request,
        Customer $customer
    ) {
        $this->authorizeAbility(
            $request,
            'reference.customers.delete'
        );
        $this->service->delete($customer);
        return $this->deleted();
    }
    public function options(Request $request)
    {
        $this->authorizeAbility(
            $request,
            'reference.customers.view'
        );
        return $this->success(
            $this->service->options()
        );
    }
}