<?php

namespace App\Domains\Employee\Controllers;

use App\Domains\Employee\Requests\StoreSalaryContractRequest;
use App\Domains\Employee\Requests\UpdateSalaryContractRequest;
use App\Domains\Employee\Resources\SalaryContractResource;
use App\Domains\Employee\Services\SalaryContractService;
use App\Domains\Shared\Controllers\BaseApiController;
use Illuminate\Http\Request;

class SalaryContractController extends BaseApiController
{
    public function __construct(protected SalaryContractService $service) {}

    public function index(Request $request)
    {
        $this->authorizeAbility($request, 'employee.salary-contracts.view');

        return $this->success(
            SalaryContractResource::collection(
                $this->service->index($request->all())
            )
        );
    }

    public function show(Request $request, int $salaryContract)
    {
        $this->authorizeAbility($request, 'employee.salary-contracts.view');

        return $this->resource(
            new SalaryContractResource(
                $this->service->find($salaryContract)
            )
        );
    }

    public function store(StoreSalaryContractRequest $request)
    {
        $this->authorizeAbility($request, 'employee.salary-contracts.create');

        return $this->created(
            new SalaryContractResource(
                $this->service->store($request->validated())
            )
        );
    }

    public function update(UpdateSalaryContractRequest $request, int $salaryContract)
    {
        $this->authorizeAbility($request, 'employee.salary-contracts.update');

        return $this->resource(
            new SalaryContractResource(
                $this->service->update(
                    $salaryContract,
                    $request->validated()
                )
            ),
            'Salary contract updated successfully.'
        );
    }

    public function destroy(Request $request, int $salaryContract)
    {
        $this->authorizeAbility($request, 'employee.salary-contracts.delete');

        $this->service->delete($salaryContract);

        return $this->deleted(
            'Salary contract deleted successfully.'
        );
    }
}