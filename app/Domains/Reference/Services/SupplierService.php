<?php
namespace App\Domains\Reference\Services;
use App\Domains\Shared\Services\BaseCrudService;
use App\Domains\Reference\Models\Supplier;
use App\Domains\Reference\Repositories\SupplierRepository;
class SupplierService extends BaseCrudService
{
    public function __construct(
        protected SupplierRepository $supplierRepository
    ) {
        $this->repository = $supplierRepository;
    }
    public function paginate(
        int $perPage = 15,
        ?string $search = null
    ) {
        return $this->supplierRepository->paginate(
            $perPage,
            $search
        );
    }
    public function options()
    {
        return $this->supplierRepository->options();
    }
    public function create(array $data): Supplier
    {
        return $this->repository->create($data);
    }
    public function update(
        Supplier $supplier,
        array $data
    ): Supplier {
        return $this->repository->update(
            $supplier,
            $data
        );
    }

    public function toggleStatus(Supplier $supplier): Supplier
    {
        return $this->repository->update($supplier, [
            'is_active' => !$supplier->is_active,
        ]);
    }
}