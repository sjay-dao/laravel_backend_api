<?php

namespace App\Domains\Reference\Services;

use App\Domains\Reference\Models\Branch;
use App\Domains\Reference\Repositories\BranchRepository;
use App\Domains\Shared\Services\BaseCrudService;

class BranchService extends BaseCrudService
{
    public function __construct(
        protected BranchRepository $branchRepository
    ) {
        $this->repository = $branchRepository;
    }

    public function create(array $data): Branch
    {
        return $this->repository
            ->create($data)
            ->load('barangay.cityMun.province.region');
    }

    public function update(
        Branch $branch,
        array $data
    ): Branch {
        return $this->repository
            ->update($branch, $data)
            ->load('barangay.cityMun.province.region');
    }

    public function paginate(
        int $perPage = 15,
        ?string $search = null
    ) {
        return $this->branchRepository->paginate(
            $perPage,
            $search
        );
    }

    public function options()
    {
        return $this->branchRepository->options();
    }

    public function toggleStatus(
        Branch $branch
    ): Branch {
        return $this->repository->update($branch, [
            'is_active' => !$branch->is_active,
        ]);
    }
}