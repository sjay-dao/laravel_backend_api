<?php
namespace App\Domains\Reference\Services;
use App\Domains\Reference\Models\Customer;
use App\Domains\Reference\Repositories\CustomerRepository;
use App\Domains\Shared\Services\BaseCrudService;
class CustomerService extends BaseCrudService
{
    public function __construct(
        protected CustomerRepository $customerRepository
    ) {
        $this->repository = $customerRepository;
    }
    public function create(array $data): Customer
    {
        return $this->repository
            ->create($data)
            ->load('barangay.cityMun.province.region');
    }
    public function update(
        Customer $customer,
        array $data
    ): Customer {
        return $this->repository
            ->update($customer, $data)
            ->load('barangay.cityMun.province.region');
    }
    public function paginate(
        int $perPage = 15,
        ?string $search = null
    ) {
        return $this->customerRepository->paginate(
            $perPage,
            $search
        );
    }
    public function options()
    {
        return $this->customerRepository->options();
    }
    public function toggleStatus(
        Customer $customer
    ): Customer {
        return $this->repository->update($customer, [
            'is_active' => !$customer->is_active,
        ]);
    }
}