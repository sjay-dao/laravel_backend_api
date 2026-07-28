<?php

namespace App\Domains\Employee\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Domains\Employee\Requests\StoreSalaryContractRequest;
use App\Domains\Employee\Requests\UpdateSalaryContractRequest;

use App\Domains\Employee\Services\SalaryContractService;

use App\Domains\Employee\Resources\SalaryContractResource;

class SalaryContractController extends Controller
{
    public function __construct(
        protected SalaryContractService $service
    ) {
    }

    public function index(Request $request)
    {
        return SalaryContractResource::collection(
            $this->service->index($request->all())
        );
    }

    public function show(int $salaryContract)
    {
        return new SalaryContractResource(
            $this->service->find($salaryContract)
        );
    }

    public function store(StoreSalaryContractRequest $request)
    {
        return new SalaryContractResource(
            $this->service->store($request->validated())
        );
    }

    public function update(
        UpdateSalaryContractRequest $request,
        int $salaryContract
    ) {
        return new SalaryContractResource(
            $this->service->update(
                $salaryContract,
                $request->validated()
            )
        );
    }

    public function destroy(int $salaryContract)
    {
        $this->service->delete($salaryContract);

        return response()->json([
            'message' => 'Salary contract deleted successfully.'
        ]);
    }
}